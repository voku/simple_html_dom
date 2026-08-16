#!/usr/bin/env python3

from pathlib import Path
import subprocess

root = Path(__file__).resolve().parent.parent
path = root / 'src/voku/helper/HtmlDomParser.php'
text = path.read_text()
old = '/** @var \\DOMNodeList $elements */'
new = '/** @var \\DOMNodeList<\\DOMElement> $elements */'
if text.count(old) != 1:
    raise SystemExit(f'expected DOMNodeList annotation once, found {text.count(old)}')
path.write_text(text.replace(old, new, 1))
subprocess.run(['php', '-l', str(path)], cwd=root, check=True)
subprocess.run(['git', 'diff', '--check'], cwd=root, check=True)
subprocess.run(['git', 'config', 'user.name', 'github-actions[bot]'], cwd=root, check=True)
subprocess.run(['git', 'config', 'user.email', '41898282+github-actions[bot]@users.noreply.github.com'], cwd=root, check=True)
subprocess.run(['git', 'add', str(path.relative_to(root))], cwd=root, check=True)
subprocess.run(['git', 'commit', '-m', 'Specify xmlns restore node type'], cwd=root, check=True)
subprocess.run(['git', 'push', 'origin', 'HEAD:claude/php-8.4-dom-htmldocument-vronm4'], cwd=root, check=True)
