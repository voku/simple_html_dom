#!/usr/bin/env python3

from __future__ import annotations

import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parent.parent
BRANCH = 'claude/php-8.4-dom-htmldocument-vronm4'
WORKFLOW = ROOT / '.github/workflows/pr150-maintenance.yml'
SCRIPT = ROOT / '.github/pr150-maintenance.py'


def run(*args: str) -> None:
    print('+', ' '.join(args), flush=True)
    subprocess.run(args, cwd=ROOT, check=True)


def output(*args: str) -> str:
    return subprocess.check_output(args, cwd=ROOT, text=True).rstrip('\n')


def replace_once(text: str, old: str, new: str, label: str) -> str:
    if text.count(old) != 1:
        raise RuntimeError(f'{label}: expected pattern exactly once, found {text.count(old)}')
    return text.replace(old, new, 1)


def patch_parser() -> None:
    path = ROOT / 'src/voku/helper/HtmlDomParser.php'
    text = path.read_text()
    text = replace_once(
        text,
        """            $hasXmlnsAttributes = \\stripos($html, 'xmlns') !== false
                                  &&
                                  $this->parkXmlnsAttributes($html5Document);
""",
        """            $xmlnsHelper = \\stripos($html, 'xmlns') !== false
                ? $this->parkXmlnsAttributes($html5Document)
                : null;
""",
        'xmlns call site',
    )
    text = replace_once(
        text,
        """        if ($hasXmlnsAttributes) {
            $this->restoreXmlnsAttributes($document);
        }
""",
        """        if ($xmlnsHelper !== null) {
            $this->restoreXmlnsAttributes($document, $xmlnsHelper);
        }
""",
        'xmlns restore call site',
    )

    start_marker = '    /**\n     * Rename every "xmlns" attribute of an HTML5-parsed document to a placeholder name.'
    end_marker = '    /**\n     * Check if the HTML5 parser of PHP >= 8.4 can be used on this runtime.'
    start = text.index(start_marker)
    end = text.index(end_marker, start)
    replacement = '''    /**
     * Rename every "xmlns" attribute of an HTML5-parsed document to a collision-free placeholder name.
     *
     * @param object $html5Document <p>A "\\Dom\\HTMLDocument" of PHP >= 8.4.</p>
     *
     * @return string|null <p>The placeholder name, or NULL when no "xmlns" attribute exists.</p>
     */
    private function parkXmlnsAttributes($html5Document): ?string
    {
        /** @phpstan-ignore class.notFound, argument.type (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
        $xPath = new \\Dom\\XPath($html5Document);
        $elements = $xPath->query('//*[@xmlns]');

        if ($elements->length === 0) {
            return null;
        }

        $helper = self::$domHtmlXmlnsHelper;
        $suffix = 0;
        while ($xPath->query('//*[@' . $helper . ']')->length > 0) {
            $helper = self::$domHtmlXmlnsHelper . '-' . ++$suffix;
        }

        foreach ($elements as $element) {
            /** @phpstan-ignore method.notFound, method.notFound (\\Dom\\Element of PHP >= 8.4) */
            $element->setAttribute($helper, $element->getAttribute('xmlns'));
            /** @phpstan-ignore method.notFound (\\Dom\\Element of PHP >= 8.4) */
            $element->removeAttribute('xmlns');
        }

        return $helper;
    }

    /**
     * Restore the "xmlns" attributes that parkXmlnsAttributes() renamed.
     *
     * @param \\DOMDocument $document
     * @param string       $helper
     *
     * @return void
     */
    private function restoreXmlnsAttributes(\\DOMDocument $document, string $helper)
    {
        $xPath = new \\DOMXPath($document);
        $elements = $xPath->query('//*[@' . $helper . ']');

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (!$element instanceof \\DOMElement) {
                continue;
            }

            $element->setAttribute('xmlns', $element->getAttribute($helper));
            $element->removeAttribute($helper);
        }
    }

'''
    path.write_text(text[:start] + replacement + text[end:])


def patch_test() -> None:
    path = ROOT / 'tests/HtmlDomParserHtml5Test.php'
    text = path.read_text()
    anchor = '    public function testDocumentStaysALegacyDomDocument()\n'
    test = '''    public function testXmlnsBridgePreservesCallerOwnedTransportAttribute()
    {
        $dom = $this->html5('<div xmlns="urn:example" data-simplevokuxmlns="caller-value"><span>x</span></div>');
        $div = $dom->findOne('div');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('urn:example', $div->getAttribute('xmlns'));
        static::assertSame('caller-value', $div->getAttribute('data-simplevokuxmlns'));
        static::assertSame('', $div->getNode()->namespaceURI ?? '');
        static::assertSame('x', $dom->findOne('div span')->text());
    }

'''
    path.write_text(replace_once(text, anchor, test + anchor, 'HTML5 test anchor'))


def patch_ci() -> None:
    path = ROOT / '.github/workflows/ci.yml'
    text = path.read_text()
    path.write_text(replace_once(
        text,
        '        uses: coverallsapp/github-action@v2\n',
        '        uses: coverallsapp/github-action@8d6379e14d29928660c4ba802d8e85393440b329 # v2\n',
        'Coveralls action',
    ))


def repair_archived_contract() -> None:
    history = ROOT / '.agent-loop/contracts/SHD-1/history/contract.001.json'
    snapshot = json.loads(history.read_text())
    snapshot['status'] = 'approved'
    snapshot['updated_at'] = snapshot['approved_at']
    snapshot_bytes = (json.dumps(snapshot, ensure_ascii=False, indent=4) + '\n').encode()
    expected = '2063951df944d1a45c1565a8d54cf70fee3d90a95d0588c12da4b487b80507a0'
    actual = hashlib.sha256(snapshot_bytes).hexdigest()
    if actual != expected:
        raise RuntimeError(f'approved revision-1 contract digest mismatch: {actual}')

    archive = ROOT / '.agent-loop/runs/.history/SHD-1/run-SHD-1-2f8699c5565d2aa0'
    (archive / 'contract.json').write_bytes(snapshot_bytes)
    recall_path = archive / 'recall-input.json'
    recall = json.loads(recall_path.read_text())
    if recall['contract']['sha256'] != f'sha256:{expected}' or recall['contract']['revision'] != 1:
        raise RuntimeError('archived Recall envelope does not bind expected revision-1 contract')
    recall['contract']['path'] = 'contract.json'
    recall_path.write_text(json.dumps(recall, ensure_ascii=False, indent=4) + '\n')


def update_agent_loop() -> None:
    run(
        'composer', 'update', 'voku/agent-loop', '--with-all-dependencies', '--no-interaction', '--prefer-dist',
        '--working-dir=tools/agent-loop',
    )
    run('composer', 'validate', '--strict', '--working-dir=tools/agent-loop')
    lock = json.loads((ROOT / 'tools/agent-loop/composer.lock').read_text())
    versions = {package['name']: package['version'] for package in lock['packages']}
    if versions.get('voku/agent-loop') != '0.16.5':
        raise RuntimeError(f"expected voku/agent-loop 0.16.5, got {versions.get('voku/agent-loop')!r}")


def validate() -> None:
    run('php', '-l', 'src/voku/helper/HtmlDomParser.php')
    run('php', '-l', 'tests/HtmlDomParserHtml5Test.php')
    run('git', 'diff', '--check')

    allowed = {
        '.agent-loop/runs/.history/SHD-1/run-SHD-1-2f8699c5565d2aa0/contract.json',
        '.agent-loop/runs/.history/SHD-1/run-SHD-1-2f8699c5565d2aa0/recall-input.json',
        '.github/workflows/ci.yml',
        'src/voku/helper/HtmlDomParser.php',
        'tests/HtmlDomParserHtml5Test.php',
        'tools/agent-loop/composer.lock',
    }
    changed: set[str] = set()
    for line in output('git', 'status', '--porcelain').splitlines():
        if not line:
            continue
        path = line[3:]
        if ' -> ' in path:
            path = path.split(' -> ', 1)[1]
        changed.add(path)
    unexpected = changed - allowed
    missing = allowed - changed
    if unexpected or missing:
        raise RuntimeError(f'unexpected change set: unexpected={sorted(unexpected)}, missing={sorted(missing)}')


def commit() -> None:
    run('git', 'config', 'user.name', 'github-actions[bot]')
    run('git', 'config', 'user.email', '41898282+github-actions[bot]@users.noreply.github.com')
    WORKFLOW.unlink()
    SCRIPT.unlink()
    run('git', 'add', '-A')
    run('git', 'commit', '-m', 'Fix bounded PR review findings')
    run('git', 'push', 'origin', f'HEAD:{BRANCH}')


def main() -> int:
    os.chdir(ROOT)
    run('php', '-r', 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);')
    run('composer', '--version')
    patch_parser()
    patch_test()
    patch_ci()
    repair_archived_contract()
    update_agent_loop()
    validate()
    commit()
    return 0


if __name__ == '__main__':
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(f'PR 150 maintenance failed: {exc}', file=sys.stderr)
        raise
