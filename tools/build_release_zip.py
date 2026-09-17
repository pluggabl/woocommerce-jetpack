"""Build a deterministic customer ZIP from a full, clean Git commit.

Development tools, tests and documentation remain tracked but are not shipped.
No network, tag, release, repository cleanup or deployment operation is performed.
"""
import argparse
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
import tarfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
RUNTIME_DIRS = {'assets', 'includes', 'langs', 'tracking'}
RUNTIME_FILES = {'readme.txt', 'changelog.txt', 'version-details.json', 'wpml-config.xml'}
SLUGS = {'woocommerce-jetpack', 'booster-plus-for-woocommerce', 'booster-elite-for-woocommerce'}


def git(*args):
    return subprocess.check_output(['git', '-C', str(ROOT), *args])


def build(commit, output):
    if not re.fullmatch('[a-f0-9]{40}', commit):
        raise ValueError('Supply the full source commit, not a moving branch')
    if git('rev-parse', 'HEAD').decode().strip() != commit:
        raise ValueError('HEAD must match the requested source identity')
    if git('status', '--porcelain', '--untracked-files=no').strip():
        raise ValueError('Tracked worktree/index changes must be committed first')
    output = output.resolve()
    if output.exists() or output.with_suffix('.json').exists():
        raise ValueError('Refusing to overwrite a candidate generation')
    if output.suffix != '.zip' or ROOT == output.parent or ROOT in output.parents:
        raise ValueError('Write the ZIP outside the source worktree')
    snapshot = git('archive', '--format=tar', commit)
    with tarfile.open(fileobj=io.BytesIO(snapshot)) as archive:
        members = archive.getmembers()
        slugs = [s for s in SLUGS if any(m.name == s + '.php' for m in members)]
        if len(slugs) != 1:
            raise ValueError('Expected exactly one edition entry point')
        slug = slugs[0]
        allowed_files = RUNTIME_FILES | {slug + '.php'}
        files, excluded = [], []
        for member in members:
            if not member.isfile():
                continue
            path = PurePosixPath(member.name)
            if path.is_absolute() or '..' in path.parts:
                raise ValueError('Unsafe archive path')
            if path.parts[0] not in RUNTIME_DIRS and member.name not in allowed_files:
                excluded.append(member.name)
                continue
            if path.suffix.lower() in {'.zip', '.log', '.env', '.pem', '.key', '.bak', '.pyc'} or '__pycache__' in path.parts:
                raise ValueError('Unexpected private/development file in runtime path: ' + member.name)
            data = archive.extractfile(member).read()
            files.append((member.name, data))
    names = {name for name, _ in files}
    if not {slug + '.php', 'readme.txt', 'langs/woocommerce-jetpack-nl_NL.mo'}.issubset(names):
        raise ValueError('Required runtime content missing')
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open('xb') as raw, zipfile.ZipFile(raw, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as package:
        for name, data in sorted(files):
            info = zipfile.ZipInfo(slug + '/' + name, (2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            package.writestr(info, data)
    with zipfile.ZipFile(output) as package:
        assert package.testzip() is None
        assert len(package.namelist()) == len(files)
        for name, data in files:
            assert package.read(slug + '/' + name) == data
    record = {'source_commit': commit, 'slug': slug, 'zip_path': str(output),
              'zip_sha256': hashlib.sha256(output.read_bytes()).hexdigest(),
              'packaging_script_sha256': hashlib.sha256(Path(__file__).read_bytes()).hexdigest(),
              'source_archive_sha256': hashlib.sha256(snapshot).hexdigest(),
              'files': [{'path': name, 'sha256': hashlib.sha256(data).hexdigest(), 'bytes': len(data)} for name, data in sorted(files)],
              'excluded_development_paths': sorted(excluded), 'zip_integrity': 'PASS',
              'runtime_qa': 'not established by packaging'}
    output.with_suffix('.json').write_text(json.dumps(record, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({key: record[key] for key in ['source_commit', 'zip_path', 'zip_sha256']}))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--commit', required=True)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    build(args.commit, args.output)
