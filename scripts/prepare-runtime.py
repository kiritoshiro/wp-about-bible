#!/usr/bin/env python3
"""Extract only our verified package layout into an isolated runtime directory."""
from pathlib import Path
import zipfile

root = Path(__file__).resolve().parents[1]
target = (root / 'dist/runtime').resolve()
target.mkdir(parents=True, exist_ok=True)
with zipfile.ZipFile(root / 'dist/wp-about-bible.zip') as archive:
    for item in archive.infolist():
        destination = (target / item.filename).resolve()
        assert destination.is_relative_to(target / 'wp-about-bible'), 'Unsafe ZIP entry'
    archive.extractall(target)
