"""Dependency-free PO validation / MO build for a candidate-source-bound catalog.

Extract the reference POT with WP-CLI i18n make-pot from the exact candidate.
This tool does not translate text, call a network service, or edit source files.
Compilation refuses an existing output path. GNU msgfmt remains an independent QA gate.
"""
import argparse
import ast
from collections import Counter
import gettext
import hashlib
import io
import json
from pathlib import Path
import re
import struct

TOKEN = re.compile(r"%\d+\$[a-z_]+%|%[A-Za-z_][A-Za-z_0-9]*%|%%|%(?:\d+\$)?[-+0 #]*(?:\d+)?(?:\.\d+)?[bcdeEufFgGosxX]")
MARKUP = re.compile(r"</?[A-Za-z][^>]*>|&(?:[A-Za-z][A-Za-z0-9]*|#\d+|#x[0-9a-fA-F]+);")
PROTECTED = re.compile(r"\[[^\]]+\]|https?://[^\s<>]+|\b(?:wcj_[A-Za-z_0-9]+|field_id|meta_key)\b")


def read_po(path):
    """Read standard quoted gettext fields; reject unsupported/malformed syntax."""
    entries = []
    entry = {}
    field = None
    for number, raw in enumerate(Path(path).read_text('utf-8-sig').splitlines() + [''], 1):
        line = raw.strip()
        if not line:
            if 'msgid' in entry:
                entries.append(entry)
            entry, field = {}, None
            continue
        if line.startswith('#~'):
            field = None
            continue
        if line.startswith('#,'):
            entry['fuzzy'] = 'fuzzy' in {flag.strip() for flag in line[2:].split(',')}
            continue
        if line.startswith('#'):
            continue
        match = re.fullmatch(r'(msgctxt|msgid_plural|msgid|msgstr(?:\[\d+\])?)\s+(".*")', line)
        if match:
            field, value = match.groups()
            if field in entry:
                raise ValueError(f'{path}:{number}: duplicate field {field}')
            entry[field] = ast.literal_eval(value)
        elif line.startswith('"') and field:
            entry[field] += ast.literal_eval(line)
        else:
            raise ValueError(f'{path}:{number}: unsupported PO syntax')
        if field and not isinstance(entry[field], str):
            raise ValueError(f'{path}:{number}: expected quoted string')
    return entries


def key(entry):
    return (entry.get('msgctxt'), entry['msgid'], entry.get('msgid_plural'))


def compile_mo(entries):
    messages = {}
    for entry in entries:
        original = ((entry['msgctxt'] + '\4') if 'msgctxt' in entry else '') + entry['msgid']
        if 'msgid_plural' in entry:
            original += '\0' + entry['msgid_plural']
            translated = entry['msgstr[0]'] + '\0' + entry['msgstr[1]']
        else:
            translated = entry.get('msgstr', '')
        if translated:
            messages[original] = translated
    items = sorted((k.encode('utf8'), v.encode('utf8')) for k, v in messages.items())
    n = len(items)
    start = 28 + 16 * n
    originals = b''
    translations = b''
    oi, ti = [], []
    for k, v in items:
        oi.append((len(k), start + len(originals)))
        originals += k + b'\0'
    start += len(originals)
    for k, v in items:
        ti.append((len(v), start + len(translations)))
        translations += v + b'\0'
    return struct.pack('<7I', 0x950412de, 0, n, 28, 28 + 8*n, 0, 0) + b''.join(struct.pack('<2I', *x) for x in oi + ti) + originals + translations


def validate(catalog, source, require_complete=False):
    errors = []
    keys = set()
    source_keys = {key(e) for e in source if e['msgid']}
    translated = []
    headers = [e.get('msgstr', '') for e in catalog if not e['msgid']]
    if len(headers) != 1:
        errors.append('Exactly one catalog header is required')
    else:
        header = headers[0]
        if not re.search(r'^Language: nl_NL\s*$', header, re.M):
            errors.append('Expected explicit standard nl_NL locale')
        if 'charset=UTF-8' not in header:
            errors.append('Expected UTF-8 header')
        if not re.search(r'Plural-Forms:\s*nplurals=2;\s*plural=\(?n\s*!=\s*1\)?;', header):
            errors.append('Expected Dutch two-form n != 1 plural expression')
    for entry in catalog:
        if not entry['msgid']:
            continue
        k = key(entry)
        if k in keys:
            errors.append({'key': k, 'error': 'duplicate key'})
        keys.add(k)
        if k not in source_keys:
            errors.append({'key': k, 'error': 'absent from exact reference POT'})
        singular = entry['msgid']
        if 'msgid_plural' in entry:
            required = {'msgstr[0]', 'msgstr[1]'}
            actual = {f for f in entry if f.startswith('msgstr')}
            if actual != required:
                errors.append({'key': k, 'error': 'requires exactly plural branches 0 and 1'})
            forms = [entry.get('msgstr[0]', ''), entry.get('msgstr[1]', '')]
            sources = [singular, entry['msgid_plural']]
        else:
            forms, sources = [entry.get('msgstr', '')], [singular]
        if not any(forms):
            continue  # An untranslated key deliberately falls back; it is not compiled.
        if entry.get('fuzzy'):
            errors.append({'key': k, 'error': 'translated fuzzy entry is not production-ready'})
        for index, (original, target) in enumerate(zip(sources, forms)):
            if not target.strip():
                errors.append({'key': k, 'error': 'blank translated plural branch', 'branch': index})
            for name, rx in [('placeholder', TOKEN), ('markup/entity', MARKUP), ('protected identifier', PROTECTED)]:
                if Counter(rx.findall(original)) != Counter(rx.findall(target)):
                    errors.append({'key': k, 'error': name + ' mismatch', 'branch': index})
        translated.append(entry)
    if require_complete and {key(e) for e in translated} != source_keys:
        errors.append('Translated key set does not exactly cover reference POT')
    return errors, translated


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('catalog', type=Path)
    parser.add_argument('--source', required=True, type=Path)
    parser.add_argument('--require-complete', action='store_true')
    parser.add_argument('--compile', type=Path, help='Create a NEW MO output only after checks pass')
    parser.add_argument('--report', type=Path, help='Write a NEW evidence report (refuse overwrite)')
    args = parser.parse_args()
    catalog, source = read_po(args.catalog), read_po(args.source)
    errors, translated = validate(catalog, source, args.require_complete)
    result = {'status': 'FAIL' if errors else 'PASS_DETERMINISTIC_ONLY', 'catalog_sha256': hashlib.sha256(args.catalog.read_bytes()).hexdigest(), 'source_pot_sha256': hashlib.sha256(args.source.read_bytes()).hexdigest(), 'source_keys': sum(bool(e['msgid']) for e in source), 'translated_keys': len(translated), 'errors': errors, 'linguistic_review': 'not established by this tool', 'runtime_qa': 'not established by this tool', 'gnu_msgfmt': 'not executed by this tool'}
    if not errors:
        binary = compile_mo([e for e in catalog if not e['msgid']] + translated)
        runtime = gettext.GNUTranslations(io.BytesIO(binary))
        for entry in translated:
            if 'msgid_plural' in entry:
                for count in (0, 1, 2, 10, 101):
                    got = runtime.npgettext(entry['msgctxt'], entry['msgid'], entry['msgid_plural'], count) if 'msgctxt' in entry else runtime.ngettext(entry['msgid'], entry['msgid_plural'], count)
                    assert got == entry['msgstr[0]' if count == 1 else 'msgstr[1]']
            else:
                got = runtime.pgettext(entry['msgctxt'], entry['msgid']) if 'msgctxt' in entry else runtime.gettext(entry['msgid'])
                assert got == entry['msgstr']
        result['stdlib_gettext_roundtrip'] = 'PASS'
        result['mo_sha256'] = hashlib.sha256(binary).hexdigest()
        if args.compile:
            with args.compile.open('xb') as output:
                output.write(binary)
    if args.report:
        with args.report.open('x', encoding='utf8') as output:
            json.dump(result, output, ensure_ascii=False, indent=2)
    print(json.dumps(result, ensure_ascii=False, indent=2))
    raise SystemExit(1 if errors else 0)


if __name__ == '__main__':
    main()
