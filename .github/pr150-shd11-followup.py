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
compat_test = 'tests/Html5DomParserCompatibilityTest.php'

# The first finalizer introduced backend-specific output cleanup. That was the wrong boundary:
# DOM mutations can legitimately create the existing SHDOM_* tokens after parsing, so both
# parser classes must keep the proven common inverse cleanup.
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
regex_once(
    html5_parser,
    r'''\n    /\*\*\n     \* Restore only placeholders that the HTML5 path actually created\..*?\n    protected function restoreOutputPlaceholders\(string \$content, bool \$putBrokenReplacedBack\): string\n    \{.*?\n    \}\n\n''',
    '\n',
)

# Keep HtmlDomParser's historical preprocessing order untouched. Html5DomParser only needs to
# protect input that shared output normalization would otherwise destructively decode (percent
# escapes) or that cannot safely cross the XML bridge as-is (the Google AMP lightning marker).
# Reuse the existing replacement table so the normal common inverse path remains authoritative.
needle = '''        $document = $this->createDOMDocumentViaHtml5Parser($html);
        $this->isDOMDocumentCreatedWithHtml5Parser = true;
'''
replace_once(
    html5_parser,
    needle,
    '''        $html = self::protectHtml5BridgeSensitiveInput($html);

        $document = $this->createDOMDocumentViaHtml5Parser($html);
        $this->isDOMDocumentCreatedWithHtml5Parser = true;
''',
)

anchor = '''    public static function isHtml5ParserSupported(): bool
    {
'''
helper = '''    /**
     * Protect only input that the shared output cleanup would otherwise change or that the
     * XML transport cannot represent directly.
     *
     * Do not run the full libxml protection pass here: protecting ampersands would suppress
     * native HTML5 entity parsing, and moving the legacy pass before backend selection breaks
     * the existing special-script preprocessing order.
     *
     * @param string $html
     *
     * @return string
     */
    private static function protectHtml5BridgeSensitiveInput(string $html): string
    {
        $search = [];
        $replace = [];

        foreach (self::$domReplaceHelper['orig'] as $index => $original) {
            if ($original !== '%' && $original !== '<html ⚡') {
                continue;
            }

            $search[] = $original;
            $replace[] = self::$domReplaceHelper['tmp'][$index];
        }

        return \\str_replace($search, $replace, $html);
    }

'''
replace_once(html5_parser, anchor, helper + anchor)

# The copied suite is supposed to pin intentional HTML5 differences. A successful HTML5 parse
# always has the browser-created head/body children, even when the caller supplied <html> only.
replace_once(
    compat_test,
    '''        $html = $dom->find('html');
        static::assertSame('<html ⚡>foo</html>', (string) $html);
''',
    '''        $html = $dom->find('html');
        // HTML5 tree construction always creates the missing head/body children.
        static::assertSame('<html ⚡><head></head><body>foo</body></html>', (string) $html);
''',
)

# Temporary runner-only marker. It is never committed as a product change; it identifies the
# exact raw input that still cannot cross the XML bridge.
content = read(compat_test)
method_start = content.index('    public function testEditLinks()')
loop = '        foreach ($texts as $text => $expected) {'
loop_start = content.index(loop, method_start)
marker = '''        $editLinksCase = 0;
        foreach ($texts as $text => $expected) {
            \\fwrite(STDERR, 'EDIT_LINKS_CASE=' . $editLinksCase++ . ' INPUT=' . \\json_encode($text) . "\\n");'''
content = content[:loop_start] + marker + content[loop_start + len(loop):]
write(compat_test, content)

for file in [html_parser, html5_parser, compat_test]:
    subprocess.run(['php', '-l', file], cwd=ROOT, check=True)
subprocess.run(['git', 'diff', '--check'], cwd=ROOT, check=True)
