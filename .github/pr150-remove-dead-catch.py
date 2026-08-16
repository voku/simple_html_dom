#!/usr/bin/env python3

from pathlib import Path
import subprocess

root = Path(__file__).resolve().parent.parent
path = root / 'src/voku/helper/HtmlDomParser.php'
text = path.read_text()
old = '''        try {
            /** @phpstan-ignore class.notFound, classConstant.notFound (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
            $html5Document = \\Dom\\HTMLDocument::createFromString(
                $html,
                \\LIBXML_NOERROR | \\Dom\\HTML_NO_DEFAULT_NS,
                $overrideEncoding
            );

            // INFO: in HTML an "xmlns" attribute is just an attribute, but the XML transport
            //          used below would turn it into a real namespace declaration and every
            //          generated XPath query of this library would stop matching. It is
            //          parked under a placeholder name and restored after the transport.
            $xmlnsHelper = \\stripos($html, 'xmlns') !== false
                ? $this->parkXmlnsAttributes($html5Document)
                : null;

            $xml = $html5Document->saveXml();
        } catch (\\Throwable $throwable) {
            return null;
        }
'''
new = '''        /** @phpstan-ignore class.notFound, classConstant.notFound (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
        $html5Document = \\Dom\\HTMLDocument::createFromString(
            $html,
            \\LIBXML_NOERROR | \\Dom\\HTML_NO_DEFAULT_NS,
            $overrideEncoding
        );

        // INFO: in HTML an "xmlns" attribute is just an attribute, but the XML transport
        //          used below would turn it into a real namespace declaration and every
        //          generated XPath query of this library would stop matching. It is
        //          parked under a placeholder name and restored after the transport.
        $xmlnsHelper = \\stripos($html, 'xmlns') !== false
            ? $this->parkXmlnsAttributes($html5Document)
            : null;

        $xml = $html5Document->saveXml();
'''
if text.count(old) != 1:
    raise SystemExit(f'expected HTML5 try/catch once, found {text.count(old)}')
path.write_text(text.replace(old, new, 1))
subprocess.run(['php', '-l', str(path)], cwd=root, check=True)
subprocess.run(['git', 'diff', '--check'], cwd=root, check=True)
subprocess.run(['git', 'config', 'user.name', 'github-actions[bot]'], cwd=root, check=True)
subprocess.run(['git', 'config', 'user.email', '41898282+github-actions[bot]@users.noreply.github.com'], cwd=root, check=True)
subprocess.run(['git', 'add', str(path.relative_to(root))], cwd=root, check=True)
subprocess.run(['git', 'commit', '-m', 'Remove unreachable HTML5 parser catch'], cwd=root, check=True)
subprocess.run(['git', 'push', 'origin', 'HEAD:claude/php-8.4-dom-htmldocument-vronm4'], cwd=root, check=True)
