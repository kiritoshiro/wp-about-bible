#!/usr/bin/env python3
"""Build an allowlisted deterministic WordPress ZIP and release manifest."""
import argparse
import hashlib
import json
import re
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def build(tag=None):
    source = (ROOT / 'wp-about-bible.php').read_text(encoding='utf-8')
    version = re.search(r' \* Version: (\d+\.\d+\.\d+)\n', source).group(1)
    assert f"define( 'ABIBLE_VERSION', '{version}' );" in source, 'Constant/header mismatch'
    assert f'Stable tag: {version}\n' in (ROOT / 'readme.txt').read_text(encoding='utf-8'), 'Stable tag mismatch'
    if tag:
        assert tag == 'v' + version, 'Tag/header mismatch'
    requirements = {key: re.search(pattern, source).group(1) for key, pattern in {
        'wordpress': r'Requires at least: ([\d.]+)', 'php': r'Requires PHP: ([\d.]+)'
    }.items()}
    names = ['wp-about-bible.php', 'uninstall.php', 'readme.txt', 'LICENSE']
    for directory in ('includes', 'assets', 'templates'):
        names.extend(p.relative_to(ROOT).as_posix() for p in (ROOT / directory).rglob('*') if p.is_file())
    allowed = {'.php', '.css', '.js', '.json', '.svg'}
    for name in names:
        path = ROOT / name
        assert not path.is_symlink(), 'Symlinks are forbidden'
        if name.startswith(('includes/', 'assets/', 'templates/')):
            assert path.suffix in allowed, f'Unexpected package file: {name}'
    dist = ROOT / 'dist'
    dist.mkdir(exist_ok=True)
    package = dist / 'wp-about-bible.zip'
    with zipfile.ZipFile(package, 'w', zipfile.ZIP_DEFLATED) as archive:
        for name in sorted(names):
            entry = zipfile.ZipInfo('wp-about-bible/' + name, (2026, 1, 1, 0, 0, 0))
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, (ROOT / name).read_bytes())
    with zipfile.ZipFile(package) as archive:
        assert archive.testzip() is None
        assert set(archive.namelist()) == {'wp-about-bible/' + n for n in names}
    digest = hashlib.sha256(package.read_bytes()).hexdigest()
    (dist / 'wp-about-bible.zip.sha256').write_text(f'{digest}  wp-about-bible.zip\n', encoding='utf-8')
    revision = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
    manifest = {'version': version, 'commit': revision, 'sha256': digest, 'files': len(names), 'requirements': requirements}
    (dist / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
    notes = ('Initial Bible library/editor foundation. See README for delivered scope and remaining roadmap.\n\n'
             f'Commit: `{revision}`\n\nSHA-256: `{digest}`\n\n'
             '<!-- abible-requirements:' + json.dumps(requirements, separators=(',', ':')) + ' -->\n')
    (dist / 'release-notes.md').write_text(notes, encoding='utf-8')
    print(json.dumps(manifest, indent=2))
    return package


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--tag')
    build(parser.parse_args().tag)
