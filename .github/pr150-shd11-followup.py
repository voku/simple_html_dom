#!/usr/bin/env python3

from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parent.parent


def read(path: str) -> str:
    return (ROOT / path).read_text()


def write(path: str, content: str) -> None:
    (ROOT / path).write_text(content)


def replace_once(path: str, old: str, new: str) -> None:
    content = read(path)
    count = content.count(old)
    if count != 1:
        raise RuntimeError(f'{path}: expected one exact match, found {count}')
    write(path, content.replace(old, new, 1))


def regex_once(path: str, pattern: str, replacement: str) -> None:
    content = read(path)
    updated, count = re.subn(pattern, lambda _match: replacement, content, count=1, flags=re.S)
    if count != 1:
        raise RuntimeError(f'{path}: expected one regex match, found {count}')
    write(path, updated)


html_parser = 'src/voku/helper/HtmlDomParser.php'
html5_parser = 'src/voku/helper/Html5DomParser.php'

# The protection and inverse transformation are one contract. Run protection before backend
# selection so every backend that later uses shared output cleanup receives protected input.
anchor = '''        // INFO: a subclass may parse the prepared HTML with a different backend, see
        //          "Html5DomParser". Everything above this point - the input repairs and the
        //          flags that shape the output - is shared, everything below is the libxml
        //          parser of this class.
'''
replace_once(
    html_parser,
    anchor,
    '''        // Protect syntax that shared output normalization later decodes/restores. This
        // transformation must happen before backend selection; otherwise Html5DomParser would
        // participate in the inverse cleanup without having received the matching protection.
        $html = self::replaceToPreserveHtmlEntities($html);

''' + anchor,
)

# Remove the old libxml-only placement of the same transform.
old_after_hook = '''        $html = self::replaceToPreserveHtmlEntities($html);

        $documentFound = false;
'''
replace_once(html_parser, old_after_hook, '''        $documentFound = false;
''')

# The first finalizer introduced a backend-specific output hook. With the transformation moved
# to shared preprocessing that indirection is unnecessary and, worse, would leave generated
# SHDOM_* tokens behind after DOM mutations. Restore the proven common inverse path.
replace_once(
    html_parser,
    '        return $this->restoreOutputPlaceholders($content, $putBrokenReplacedBack);',
    '        return self::putReplacedBackToPreserveHtmlEntities($content, $putBrokenReplacedBack);',
)
replace_once(
    html_parser,
    '            $return = $this->restoreOutputPlaceholders($xml, true);',
    '            $return = self::putReplacedBackToPreserveHtmlEntities($xml);',
)
regex_once(
    html_parser,
    r'''\n    /\*\*\n     \* Restore placeholders created by this parser backend before exposing output\..*?\n    protected function restoreOutputPlaceholders\(string \$content, bool \$putBrokenReplacedBack\): string\n    \{\n        return self::putReplacedBackToPreserveHtmlEntities\(\$content, \$putBrokenReplacedBack\);\n    \}\n''',
    '\n',
)

# Likewise remove the HTML5-specific partial inverse. The shared transform now gives us exact
# pairing, so the existing common cleanup is both smaller and correct.
regex_once(
    html5_parser,
    r'''\n    /\*\*\n     \* Restore only placeholders that the HTML5 path actually created\..*?\n    protected function restoreOutputPlaceholders\(string \$content, bool \$putBrokenReplacedBack\): string\n    \{.*?\n    \}\n\n''',
    '\n',
)

for file in [html_parser, html5_parser]:
    subprocess.run(['php', '-l', file], cwd=ROOT, check=True)
subprocess.run(['git', 'diff', '--check'], cwd=ROOT, check=True)
