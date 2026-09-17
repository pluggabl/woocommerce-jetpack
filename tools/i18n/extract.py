"""Extract an independently hashed native-filesystem source snapshot with WP-CLI.

Never install/download tools. Never extract directly from a Windows Docker bind
mount: PHP SPL can silently omit entries there while make-pot exits successfully.
The required host manifest and PHP enumeration/hash preflight make omissions fatal.
"""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess

ROOT_EXCLUDE = ('includes/lib', 'includes/plus/lib', 'tests', 'tools', 'node_modules', 'vendor', '.git')
# WP-CLI patterns match nested directory names: bare tools wrongly skips includes/tools.
EXCLUDE = ('includes/lib', 'includes/plus/lib', 'tests', 'tools/i18n', 'node_modules', 'vendor', '.git')


def collect_files(root):
    files = []
    for path in sorted(root.rglob('*')):
        relative = path.relative_to(root).as_posix()
        if not path.is_file() or any(relative == x or relative.startswith(x + '/') for x in ROOT_EXCLUDE):
            continue
        if path.suffix in ('.php', '.js') or path.name in ('block.json', 'theme.json'):
            files.append({'path': relative, 'sha256': hashlib.sha256(path.read_bytes()).hexdigest()})
    return sorted(files, key=lambda f: f['path'])


def manifest_files(path):
    data = json.loads(path.read_text('utf-8-sig'))
    files = data['source_files'] if isinstance(data, dict) else data
    result = sorted(({'path': f['path'], 'sha256': f['sha256']} for f in files), key=lambda f: f['path'])
    if not result or len({f['path'] for f in result}) != len(result):
        raise ValueError('Empty or duplicate source manifest')
    for f in result:
        p = Path(f['path'])
        if p.is_absolute() or '..' in p.parts or '\\' in f['path']:
            raise ValueError('Manifest paths must be safe repository-relative paths')
    return result


PHP_ENUMERATION = r'''
$root=rtrim($argv[1],'/\\');$exclude=json_decode($argv[2],true);$files=[];
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $f){
 if(!$f->isFile())continue;$rel=str_replace('\\','/',substr($f->getPathname(),strlen($root)+1));$skip=false;
 foreach($exclude as $x){if($rel===$x||strpos($rel,$x.'/')===0){$skip=true;break;}}
 if($skip)continue;
 if(in_array($f->getExtension(),['php','js'],true)||in_array($f->getFilename(),['block.json','theme.json'],true))
  $files[]=['path'=>$rel,'sha256'=>hash_file('sha256',$f->getPathname())];
}
usort($files,static function($a,$b){return strcmp($a['path'],$b['path']);});echo json_encode($files,JSON_UNESCAPED_SLASHES);
'''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--php', required=True, help='Existing PHP executable')
    parser.add_argument('--wp-cli', required=True, type=Path, help='Existing official WP-CLI PHAR')
    parser.add_argument('--source-root', required=True, type=Path, help='Native-filesystem staged exact-source snapshot, not Windows Docker bind mount')
    parser.add_argument('--source-manifest', required=True, type=Path, help='Independent host-enumerated JSON with source_files path/sha256 records')
    parser.add_argument('--output', required=True, type=Path, help='New candidate POT path')
    parser.add_argument('--manifest', required=True, type=Path, help='New extraction evidence JSON path')
    args = parser.parse_args()
    if args.output.exists() or args.manifest.exists():
        raise SystemExit('Refusing to overwrite an existing POT or extraction manifest.')
    if not args.wp_cli.is_file():
        raise SystemExit('WP-CLI is absent; supply an existing tool, do not auto-install.')
    root = args.source_root.resolve()
    expected = manifest_files(args.source_manifest)
    actual = collect_files(root)
    if actual != expected:
        raise SystemExit('Native snapshot differs from independent host source manifest; extraction aborted.')
    preflight = subprocess.run([args.php, '-r', PHP_ENUMERATION, str(root), json.dumps(ROOT_EXCLUDE)], text=True, encoding='utf8', errors='replace', capture_output=True)
    try:
        php_files = json.loads(preflight.stdout) if preflight.returncode == 0 else None
    except json.JSONDecodeError:
        php_files = None
    if php_files != expected:
        raise SystemExit('PHP SPL enumeration/hash differs from host manifest; stage onto native filesystem and retry. Do not trust make-pot exit 0.')
    digest = hashlib.sha256(json.dumps(expected, sort_keys=True, separators=(',', ':')).encode('utf8')).hexdigest()
    headers = json.dumps({'X-Booster-Source-SHA256': digest})
    command = [args.php, str(args.wp_cli.resolve()), 'i18n', 'make-pot', str(root), str(args.output.resolve()), '--domain=woocommerce-jetpack', '--exclude=' + ','.join(EXCLUDE), '--headers=' + headers]
    result = subprocess.run(command, cwd=root, text=True, encoding='utf8', errors='replace', capture_output=True)
    unchanged = collect_files(root) == expected
    passed = result.returncode == 0 and unchanged and args.output.is_file()
    evidence = {'status': 'PASS_EXTRACTION_ONLY' if passed else 'FAIL', 'command': command, 'source_root': str(root), 'host_manifest_sha256': hashlib.sha256(args.source_manifest.read_bytes()).hexdigest(), 'php_spl_byte_enumeration_preflight': True, 'source_unchanged_after': unchanged, 'source_tree_sha256': digest, 'source_files': expected, 'exit_code': result.returncode, 'stdout': result.stdout, 'stderr': result.stderr, 'pot_sha256': hashlib.sha256(args.output.read_bytes()).hexdigest() if args.output.is_file() else None}
    with args.manifest.open('x', encoding='utf8') as output:
        json.dump(evidence, output, ensure_ascii=False, indent=2)
    print(json.dumps({key: value for key, value in evidence.items() if key != 'source_files'}, ensure_ascii=False, indent=2))
    raise SystemExit(0 if passed else 1)


if __name__ == '__main__':
    main()

