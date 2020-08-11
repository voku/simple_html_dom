<?php

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomInterface;
use voku\helper\SimpleHtmlDomNode;
use voku\helper\SimpleHtmlDomNodeInterface;

/**
 * @internal
 */
final class HtmlDomParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $filename
     *
     * @return string|null
     */
    protected function loadFixture($filename)
    {
        $path = __DIR__ . '/fixtures/' . $filename;
        if (\file_exists($path)) {
            return \file_get_contents($path);
        }

        return null;
    }

    public function testConstructWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        new HtmlDomParser(['foo']);
    }

    public function testLoadHtmlWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->loadHtml(['foo']);
    }

    public function testLoadWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->load(['foo']);
    }

    public function testLoadHtmlFileWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile(['foo']);
    }

    public function testLoadFileWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->load_file(['foo']);
    }

    public function testLoadHtmlFileWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile('/path/to/file');
    }

    public function testLoadHtmlFileWithNotLoadFile()
    {
        $this->expectException(\RuntimeException::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile('http://fobar');
    }

    public function testLoadHtmlUrl()
    {
        $dom = HtmlDomParser::file_get_html(__DIR__ . '/fixtures/test_template_js.html');
        $headerSearchTemplateDom = $dom->findOneOrFalse('#headerSearchTemplate');
        $headerSearchTemplateHtml = $headerSearchTemplateDom->innerHtml();

        $domInner = HtmlDomParser::str_get_html($headerSearchTemplateHtml);
        $h1 = $domInner->findOneOrFalse('h1');
        static::assertSame(
            '<h1 class="hd"><a href="http://www.11st.co.kr" data-ga-event-category="PC_GNB" data-ga-event-action="»ó´Ü¿µ¿ª_·Î°í" data-ga-event-label="">11¹ø°¡</a></h1>',
            $h1->html()
        );
    }

    public function testMethodNotExist()
    {
        $this->expectException(\BadMethodCallException::class);

        $document = new HtmlDomParser();
        /** @noinspection PhpUndefinedMethodInspection */
        $document->bar();
    }

    public function testStaticMethodNotExist()
    {
        $this->expectException(\BadMethodCallException::class);

        /** @noinspection PhpUndefinedMethodInspection */
        HtmlDomParser::bar();
    }

    public function testNotExistProperty()
    {
        $document = new HtmlDomParser();

        /** @noinspection PhpUndefinedFieldInspection */
        static::assertNull($document->foo);
    }

    public function testConstruct()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        static::assertSame($html, $element->outertext);
    }

    public function testWebComponent()
    {
        $html = '<button is="shopping-cart">Add to cart</button>';
        $dom = HtmlDomParser::str_get_html($html);

        static::assertSame($html, $dom->outertext);
    }

    public function testWindows1252()
    {
        $file = __DIR__ . '/fixtures/windows-1252-example.html';
        $document = new HtmlDomParser();

        $document->loadHtmlFile($file);
        static::assertNotNull(\count($document('li')));

        $document->load_file($file);
        static::assertNotNull(\count($document('li')));

        $document = HtmlDomParser::file_get_html($file);
        static::assertNotNull(\count($document('li')));

        // ---

        // this only works with "UTF8"-helpers
        if (\class_exists('\voku\helper\UTF8')) {
            static::assertSame(['ÅÄÖ', 'åäö'], $document->find('li')->text());
        }
    }

    public function testLoadHtmlFile()
    {
        $file = __DIR__ . '/fixtures/test_page.html';
        $document = new HtmlDomParser();

        $document->loadHtmlFile($file);
        static::assertNotNull(\count($document('div')));

        $document->load_file($file);
        static::assertNotNull(\count($document('div')));

        $document = HtmlDomParser::file_get_html($file);
        static::assertNotNull(\count($document('div')));
    }

    public function testLoadHtml()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser();

        $document->loadHtml($html);
        static::assertNotNull(\count($document('div')));

        $document->load($html);
        static::assertNotNull(\count($document('div')));

        $document = HtmlDomParser::str_get_html($html);
        static::assertNotNull(\count($document('div')));
    }

    public function testGetDocument()
    {
        $document = new HtmlDomParser();
        static::assertInstanceOf(\DOMDocument::class, $document->getDocument());
    }

    /**
     * @dataProvider findTests
     *
     * @param $html
     * @param $selector
     * @param $count
     */
    public function testFind($html, $selector, $count)
    {
        $document = new HtmlDomParser($html);
        $elements = $document->find($selector);

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNodeInterface::class, $elements);
        static::assertCount($count, $elements);

        foreach ($elements as $element) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDomInterface::class, $element);
        }

        if ($count !== 0) {
            $element = $document->find($selector, -1);
            static::assertInstanceOf(voku\helper\SimpleHtmlDomInterface::class, $element);
        }
    }

    /**
     * @return array
     */
    public function findTests()
    {
        $html = $this->loadFixture('test_page.html');

        return [
            [$html, '.fake h2', 0],
            [$html, 'article', 16],
            [$html, '.radio', 3],
            [$html, 'input.radio', 3],
            [$html, 'ul li', 35],
            [$html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6],
            [$html, 'input[id]', 23],
            [$html, 'input[id=in]', 1],
            [$html, '#in', 1],
            [$html, '*[id]', 52],
            [$html, 'text', 640],
            [$html, 'comment', 3],
        ];
    }

    public function testHtml()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser($html);

        $htmlTmp = $document->html();
        static::assertInternalType('string', $htmlTmp);

        $xmlTmp = $document->xml();
        static::assertInternalType('string', $xmlTmp);

        static::assertInternalType('string', $document->outertext);
        static::assertTrue(\strlen($document) > 0);

        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        static::assertSame($html, $document->html());
        static::assertSame($html, $document->outertext);
        static::assertSame($html, (string) $document);
    }

    public function testInnerHtml()
    {
        $html = '<div><div>foo</div></div>';
        $document = new HtmlDomParser($html);

        static::assertSame('<div>foo</div>', $document->innerHtml());
        static::assertSame('<div>foo</div>', $document->innerText());
        static::assertSame('<div>foo</div>', $document->innertext);
    }

    public function testText()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        static::assertSame('foo', $document->text());
        static::assertSame('foo', $document->plaintext);
    }

    public function testSave()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser($html);

        static::assertInternalType('string', $document->save());
    }

    public function testSaveIssue42()
    {
        $html = '<div><p>p1</p></div>';
        $document = new HtmlDomParser($html);

        static::assertSame('<div><p>p1</p></div>', $document->save());
    }

    public function testSaveAsFile()
    {
        $html = '<div><p>p1</p></div>';
        $document = new HtmlDomParser($html);

        $filePathTmp = self::tmpdir() . '/' . \uniqid(static::class, true);
        static::assertSame('<div><p>p1</p></div>', $document->save($filePathTmp));

        $htmlTmp = \file_get_contents($filePathTmp);
        static::assertSame('<div><p>p1</p></div>', $htmlTmp);
    }

    /**
     * @return string
     */
    public static function tmpdir()
    {
        if (\strpos(\PHP_OS, 'WIN') !== false) {
            $var = \getenv('TMP') ? \getenv('TMP') : \getenv('TEMP');
            if ($var) {
                return $var;
            }

            if (\is_dir('/temp') || \mkdir('/temp')) {
                return \realpath('/temp');
            }

            return false;
        }

        $var = \getenv('TMPDIR');
        if ($var) {
            return $var;
        }

        return \realpath('/tmp');
    }

    public function testClear()
    {
        $document = new HtmlDomParser();

        static::assertTrue($document->clear());
    }

    public function testStrGetHtml()
    {
        $str = <<<'HTML'
中

<form name="form1" method="post" action="">
    <input type="checkbox" name="checkbox1" value="checkbox1" checked>abc-1<br>
    <input type="checkbox" name="checkbox2" value="checkbox2">öäü-2<br>
    <input type="checkbox" name="checkbox3" value="checkbox3" checked>中文空白-3<br>
</form>
HTML;

        $html = HtmlDomParser::str_get_html($str);
        $checkboxArray = [];
        foreach ($html->find('input[type=checkbox]') as $checkbox) {
            if ($checkbox->checked) {
                $checkboxArray[(string) $checkbox->name] = 'checked';
            } else {
                $checkboxArray[(string) $checkbox->name] = 'not checked';
            }
        }

        static::assertCount(3, $checkboxArray);
        static::assertSame('checked', $checkboxArray['checkbox1']);
        static::assertSame('not checked', $checkboxArray['checkbox2']);
        static::assertSame('checked', $checkboxArray['checkbox3']);
    }

    public function testOutertext()
    {
        $str = <<<'HTML'
<form name="form1" method="post" action=""><input type="checkbox" name="checkbox1" value="checkbox1" checked>中文空白</form>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        foreach ($html->findMulti('input') as $e) {
            $e->outertext = '[INPUT]';
        }

        static::assertSame('<form name="form1" method="post" action="">' . "\n" . '[INPUT]中文空白</form>', (string) $html);
    }

    public function testInnertextWithHtmlHeadTag()
    {
        $str = <<<'HTML'
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('head', 0)->innerText = '<meta http-equiv="Content-Type" content="text/html; charset=utf-7">';

        static::assertSame(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-7"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>',
            \str_replace(
                [
                    "\r\n",
                    "\r",
                    "\n",
                ],
                '',
                (string) $html
            )
        );
    }

    public function testInnertextWithHtml()
    {
        $str = <<<'HTML'
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = '<foo>bar</foo>';

        static::assertSame(
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello"><foo>bar</foo></div><div id="world" class="bar">World</div></body></html>',
            \str_replace(
                [
                    "\r\n",
                    "\r",
                    "\n",
                ],
                '',
                (string) $html
            )
        );
    }

    public function testInnertext()
    {
        $str = <<<'HTML'
<div id="hello">Hello</div><div id="world">World</div>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);
    }

    public function testMail2()
    {
        $filename = __DIR__ . '/fixtures/test_mail.html';
        $filenameExpected = __DIR__ . '/fixtures/test_mail_expected.html';

        $html = HtmlDomParser::file_get_html($filename);
        $htmlExpected = \str_replace(["\r\n", "\r", "\n"], "\n", \file_get_contents($filenameExpected));

        // object to sting
        static::assertSame(
            $htmlExpected,
            \str_replace(["\r\n", "\r", "\n"], "\n", (string) $html)
        );

        $preHeaderContentArray = $html->findMulti('.preheaderContent');

        static::assertSame('padding-top:10px; padding-right:20px; padding-bottom:10px; padding-left:20px;', $preHeaderContentArray[0]->style);
        static::assertSame('top', $preHeaderContentArray[0]->valign);
    }

    public function testMail()
    {
        $str = <<<HTML
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title></title>
</head>
<body bgcolor="#FF9900" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<center>
  <style type="text/css">
    body {
      background: #f2f2f2;
      margin: 0;
      padding: 0;
    }

    td, p, span {
      font-family: verdana, arial, sans-serif;
      font-size: 14px;
      line-height: 16px;
      color: #666;
    }

    a {
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
      <td bgcolor="#FF9900">
        <img src="/images/nl/transparent.gif" alt="" width="5" height="3" border="0"></td>
    </tr>
    </tbody>
  </table>
  <table width="620" border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
      <td>
        <!-- HEADER -->
        <table width="620" border="0" cellspacing="0" cellpadding="0">
          <tbody>
          <tr>
            <td bgcolor="#ffffff">
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="43" border="0">
                  </td>
                  <td width="298" align="left" valign="middle">
                    <font style="font-family:verdana,arial,sans-serif; font-size:12px; color:#666666;" face="verdana,arial,helvetica,sans-serif" size="2" color="#666666"></font>
                  </td>
                  <td width="298" align="right" valign="middle">
                    <font style="font-family:verdana,arial,helvetica,sans-serif; font-size:18px; color:#FF9900;" face="verdana,arial,helvetica,sans-serif" size="3" color="#FF9900">test</font></td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="43" border="0">
                  </td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <a href="test" target="_blank"><img src="/images/nl/default_header_visual2.jpg" width="620" alt="test" border="0"></a>
            </td>
          </tr>
          <tr>
            <td bgcolor="#FF9900">
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>
                  <td width="300" align="left">
                    <font style="font-family:verdana,arial,sans-serif; font-size:14px; line-height:16px; color:#ffffff;" face="verdana,arial,helvetica,sans-serif" size="2" color="#ffffff">


                      <b>this is a test öäü ... foobar ... <span class="utf8">דיעס איז אַ פּרובירן!</span>span></b>
test3Html.html                      <foo id="foo">bar</foo>
                      <test_>lall</test_>
                      <br/><br/>
                      <br/><br/>

                      Lorem ipsum dolor sit amet, consectetur adipisicing elit. At commodi doloribus, esse inventore ipsam itaque laboriosam molestias nesciunt nihil reiciendis rem rerum? Aliquam aperiam doloremque ea harum laborum nam neque nostrum perferendis quas reiciendis. Ab accusamus, alias facilis labore minima molestiae nihil omnis quae quidem, reiciendis sint sit velit voluptatem!

                      <br/><br/>
                      <a href="test" style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:22px; line-height:24px; color:#ffffff;" target="_blank"><img src="/images/nl/button_entdecken_de.jpg" border="0"></a>
                      <br/><br/>
                      Ihr Team
                    </font></td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>

                </tr>
                <tr>
                  <td colspan="3">
                    <img src="/images/nl/transparent.gif" alt="" width="5" height="30" border="0"></td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" valign="top">
              <img src="/images/nl/teaser_shadow.jpg" alt="" width="620" height="16" border="0"></td>
          </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td>
        <!-- FOOTER -->
        <table width="620" border="0" cellspacing="0" cellpadding="0">
          <tbody>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="" width="5" height="25" border="0"></td>
          </tr>
          <tr>
            <td align="center">
              <font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#cc0000;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#cc0000">
                <a href="test" target="_blank" style="color:#666666;"><font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#666666;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#666666">IMPRESSUM &amp; RECHTLICHES</font></a>
              </font></td>
          </tr>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="" width="5" height="10" border="0"></td>
          </tr>
          <tr>
            <td align="center" valign="top">
              <img src="/images/nl/footer_shadow.jpg" alt="" width="620" height="14" border="0"></td>
          </tr>
          <tr>
            <td><img src="/images/i/nl/transparent.gif" alt="" width="5" height="10" border="0"></td>
          </tr>
          <tr>
            <td>
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="358" align="right" valign="middle">
                    <font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#666666;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#666666">© 2015 Test AG &amp; Co. KGaA</font>
                  </td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>
                  <td width="250" align="left" valign="middle">
                    <a href="test" target="_blank"><img src="/nl/footer_logo.jpg" alt="test" width="60" height="34" border="0"></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="○●◎ earth 中文空白" width="5" height="20" border="0"></td>
          </tr>
          </tbody>
        </table>
      </td>
    </tr>
    </tbody>
  </table>
</center>

</body>
</html>
HTML;

        $htmlTmp = HtmlDomParser::str_get_html($str);
        static::assertInstanceOf(voku\helper\HtmlDomParser::class, $htmlTmp);

        // replace all images with "foobar"
        $tmpArray = [];
        foreach ($htmlTmp->findMulti('img') as $e) {
            if ($e->src !== '') {
                $tmpArray[] = $e->src;

                $e->src = 'foobar';
            }
        }

        $testString = false;
        $tmpCounter = 0;
        foreach ($htmlTmp->findMulti('table tr td img') as $e) {
            if ($e->alt === '○●◎ earth 中文空白') {
                $testString = $e->alt;

                break;
            }
            ++$tmpCounter;
        }
        static::assertSame(15, $tmpCounter);
        static::assertSame('○●◎ earth 中文空白', $testString);

        // get the content from the css-selector

        $testStringUtf8_v1 = $htmlTmp->find('html .utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v1[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v1[0]->html(true));

        $testStringUtf8_v2 = $htmlTmp->find('span.utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v2[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v2[0]->html(true));

        $testStringUtf8_v3 = $htmlTmp->find('.utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v3[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v3[0]->html(true));

        $testStringUtf8_v4 = $htmlTmp->find('foo');
        static::assertSame('bar', $testStringUtf8_v4[0]->innertext);
        static::assertSame('<foo id="foo">bar</foo>', $testStringUtf8_v4[0]->html(true));

        $testStringUtf8_v5 = $htmlTmp->find('#foo');
        static::assertSame('bar', $testStringUtf8_v5[0]->innertext);
        static::assertSame('<foo id="foo">bar</foo>', $testStringUtf8_v5[0]->outertext);

        $testStringUtf8_v6 = $htmlTmp->find('test_');
        static::assertSame('lall', $testStringUtf8_v6[0]->innertext);
        static::assertSame('<test_>lall</test_>', $testStringUtf8_v6[0]->outertext);

        $testStringUtf8_v7 = $htmlTmp->getElementById('foo');
        static::assertSame('bar', $testStringUtf8_v7->innertext);

        $testStringUtf8_v8 = $htmlTmp->getElementByTagName('foo');
        static::assertSame('bar', $testStringUtf8_v8->innertext);

        $testStringUtf8_v9 = $htmlTmp->getElementsByTagName('img', 15);
        static::assertSame('○●◎ earth 中文空白', $testStringUtf8_v9->alt);
        static::assertSame('', $testStringUtf8_v9->innertext);
        static::assertSame('<img src="foobar" alt="○●◎ earth 中文空白" width="5" height="20" border="0">', $testStringUtf8_v9->html(true));

        // test toString
        $htmlTmp = (string) $htmlTmp;
        static::assertCount(16, $tmpArray);
        static::assertContains('<img src="foobar" alt="" width="5" height="3" border="0">', $htmlTmp);
        static::assertContains('© 2015 Test', $htmlTmp);
    }

    public function testContentBeforeHtmlStart()
    {
        $html = '<html> a';
        $dom = HtmlDomParser::str_get_html($html);

        static::assertSame(
            '<html> a</html>',
            $dom->html()
        );
    }

    public function testSetAttr()
    {
        $html = '<html><script type="application/ld+json"></script><p></p><div id="p1" class="post">foo</div><div class="post" id="p2">bar</div></html>';
        $expected = '<html><script type="application/ld+json"></script><p></p><div class="post" id="p1">foo</div><div class="post" id="p2">bar</div></html>';

        $document = new HtmlDomParser($html);

        foreach ($document->find('div') as $e) {
            $attrs = [];
            foreach ($e->getAllAttributes() as $attrKey => $attrValue) {
                $attrs[$attrKey] = $attrValue;
                $e->{$attrKey} = null;
            }

            \ksort($attrs);

            foreach ($attrs as $attrKey => $attrValue) {
                $e->{$attrKey} = $attrValue;
            }
        }

        static::assertSame($expected, $document->html());
    }

    public function testEditLinks()
    {
        $texts = [
            '<a href="http://foobar.de" class="  more  "  >Mehr</a><a href="http://foobar.de" class="  more  "  >Mehr</a>'                                                                                                                                                                                                                                                                              => '<a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a><a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a>',
            ' <p><a href="http://foobar.de" class="  more  "  >Mehr</a></p>'                                                                                                                                                                                                                                                                                                                            => '<p><a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a></p>',
            '<a <a href="http://foobar.de">foo</a><div></div>'                                                                                                                                                                                                                                                                                                                                          => '<a href="http://foobar.de" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">foo</a><div></div>',
            ' <p></p>'                                                                                                                                                                                                                                                                                                                                                                                  => '<p></p>',
            ' <p>'                                                                                                                                                                                                                                                                                                                                                                                      => '<p></p>',
            'p>'                                                                                                                                                                                                                                                                                                                                                                                        => 'p>',
            'p'                                                                                                                                                                                                                                                                                                                                                                                         => 'p',
            'Google+ && Twitter || Lînux'                                                                                                                                                                                                                                                                                                                                                               => 'Google+ && Twitter || Lînux',
            '<p>Google+ && Twitter || Lînux</p>'                                                                                                                                                                                                                                                                                                                                                        => '<p>Google+ && Twitter || Lînux</p>',
            '<p>Google+ && Twitter ||&nbsp;Lînux</p>'                                                                                                                                                                                                                                                                                                                                                   => '<p>Google+ && Twitter ||&nbsp;Lînux</p>',
            '<a href="http://foobar.de[[foo]]&{{foobar}}&lall=1">foo</a>'                                                                                                                                                                                                                                                                                                                               => '<a href="http://foobar.de[[foo]]&{{foobar}}&lall=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de[[foo]]&{{foobar}}&lall=1\');">foo</a>',
            '<div><a href="http://foobar.de[[foo]]&{{foobar}}&lall=1">foo</a>'                                                                                                                                                                                                                                                                                                                          => '<div><a href="http://foobar.de[[foo]]&{{foobar}}&lall=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de[[foo]]&{{foobar}}&lall=1\');">foo</a></div>',
            ''                                                                                                                                                                                                                                                                                                                                                                                          => '',
            '<a href=""><span>lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}</span><img src="http://foobar?lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}" style="max-width:600px;" alt="Ihr Unternehmen in den wichtigsten Online-Verzeichnissen" class="headerImage" mc:label="header_image" mc:edit="header_image" mc:allowdesigner mc:allowtext /></a>' => '<a href="" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=\');"><span>lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}</span><img src="http://foobar?lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}" style="max-width:600px;" alt="Ihr Unternehmen in den wichtigsten Online-Verzeichnissen" class="headerImage" mc:label="header_image" mc:edit="header_image" mc:allowdesigner mc:allowtext></a>',
            'this is a test <a href="http://menadwork.com/test/?foo=1">test1</a> lall <a href="http://menadwork.com/test/?foo=1&lall=2">test2</a> ... <a href="http://menadwork.com">test3</a>'                                                                                                                                                                                                         => 'this is a test <a href="http://menadwork.com/test/?foo=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test1</a> lall <a href="http://menadwork.com/test/?foo=1&lall=2" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test2</a> ... <a href="http://menadwork.com" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test3</a>',
        ];

        foreach ($texts as $text => $expected) {
            $dom = HtmlDomParser::str_get_html($text);

            foreach ($dom->find('a') as $item) {
                $href = $item->getAttribute('href');
                $dataUrlParse = $item->getAttribute('data-url-parse');

                if ($dataUrlParse) {
                    continue;
                }

                $parseLink = \parse_url($href);
                $domain = ($parseLink['host'] ?? '');

                $item->setAttribute('data-url-parse', 'done');
                $item->setAttribute('onClick', '$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=' . \urlencode($domain) . '\');');
            }

            static::assertSame($expected, $dom->html(true), 'tested: ' . $text);
        }
    }

    public function testWithUTF8()
    {
        $str = '<p>イリノイ州シカゴにて</p>';

        $html = HtmlDomParser::str_get_html($str);

        $html->find('p', 1)->class = 'bar';

        static::assertSame(
            '<p>イリノイ州シカゴにて</p>',
            $html->html()
        );

        static::assertSame(
            'イリノイ州シカゴにて',
            $html->text()
        );

        // ---

        $str = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF8"><title>jusqu’à 51% de rabais!</title></head><body></body></html>';

        $html = HtmlDomParser::str_get_html($str);

        $title = $html->find('title', 0);

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerHtml
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerHtml()
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerText
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerText()
        );
    }

    public function testWithExtraXmlOptions()
    {
        $str = <<<'HTML'
<div id="hello">Hello</div><div id="world">World</div><strong></strong>
HTML;

        $html = HtmlDomParser::str_get_html($str, \LIBXML_NOERROR);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';
        $html->findOne('div[id=hello]')->innertext = 'foo';

        static::assertSame(
            '<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>' . "\n" . '<strong></strong>',
            $html->html()
        );

        // -------------

        $html->find('div[id=fail]', 0)->innertext = 'foobar';

        static::assertSame(
            '<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>' . "\n" . '<strong></strong>',
            (string) $html
        );
    }

    public function testEditInnerText()
    {
        $str = <<<'HTML'
<div id="hello">Hello</div><div id="world">World</div>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);

        // -------------

        $html->find('div[id=fail]', 0)->innertext = 'foobar';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);
    }

    public function testLoad()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $div = $dom->find('div', 0);
        static::assertSame(
            '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>',
            $div->outertext
        );
    }

    public function testNotLoaded()
    {
        $dom = new HtmlDomParser();
        $div = $dom->find('div', 0);

        static::assertSame('', $div->plaintext);
    }

    public function testIncorrectAccess()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $div = $dom->find('div', 0);
        static::assertSame('', $div->foo);
    }

    public function testLoadSelfclosingAttr()
    {
        $dom = new HtmlDomParser();
        $dom->load("<div class='all'><br  foo  bar  />baz</div>");
        $br = $dom->find('br', 0);
        static::assertSame('<br foo bar>', $br->outerHtml);
    }

    public function testLoadSelfclosingAttrToString()
    {
        $dom = new HtmlDomParser();
        $dom->load("<div class='all'><br  foo  bar  />baz</div>");
        $br = $dom->find('br', 0);
        static::assertSame('<br foo bar>', (string) $br);
    }

    public function testBrokenHtmlAtTheBeginOfTheInput()
    {
        $dom = new HtmlDomParser();
        $dom->useKeepBrokenHtml(true);
        /* @noinspection JSUnresolvedVariable */
        /* @noinspection UnterminatedStatementJS */
        /* @noinspection BadExpressionStatementJS */
        /* @noinspection JSUndeclaredVariable */
        $html = '</script><script async src="cdnjs"></script>';
        $dom->load($html);
        static::assertSame('</script><script async src="cdnjs"></script>', $dom->innerHtml);
    }

    public function testBrokenHtmlInTheMiddleOfTheInput()
    {
        $dom = new HtmlDomParser();
        $dom->useKeepBrokenHtml(true);
        /* @noinspection JSUnresolvedVariable */
        /* @noinspection UnterminatedStatementJS */
        /* @noinspection BadExpressionStatementJS */
        /* @noinspection JSUndeclaredVariable */
        $html = '<script async src="cdnjs"></script></borken foo="lall"><p>some text ...</p>';
        $dom->load($html);
        static::assertSame('<script async src="cdnjs"></script></borken foo="lall"><p>some text ...</p>', $dom->innerHtml);
    }

    public function testLoadNoOpeningTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><font color="red"><strong>PR Manager</strong></font></b><div class="content">content</div></div>');
        static::assertSame('content', $dom->find('.content', 0)->text);
    }

    public function testLoadNoClosingTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a></div>');
        $root = $dom->find('div', 0);
        static::assertSame('<div class="all"><p>Hey bro, <a href="google.com">click here</a></p></div>', $root->outerHtml);
    }

    public function testLoadAttributeOnSelfClosing()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a></div><br class="both" />');
        $br = $dom->find('br', 0);
        static::assertSame('both', $br->getAttribute('class'));
    }

    public function testLoadClosingTagOnSelfClosing()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><br><p>Hey bro, <a href="google.com">click here</a></br></div>');
        static::assertSame('<br><p>Hey bro, <a href="google.com">click here</a></p>', $dom->find('div', 0)->innerHtml);
    }

    public function testScriptWithoutScriptTag()
    {
        $test = 'window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')';
        $dom = new HtmlDomParser();
        $dom->load($test);
        static::assertSame(
            'window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')',
            $dom->html()
        );
    }

    public function testScriptInHeadScript()
    {
        $dom = new HtmlDomParser();
        $dom->load(
            '
              <!DOCTYPE html>
              <html>
              <head>
                  <meta name="robots" content="noindex, follow">
                  <style>
                      /** quick fix because bootstrap <pre> has a background-color. */
                      pre code { background-color: inherit; }
                  </style>
              </head>
              <body class="blog">
              <header>
                  <nav>
                  </nav>
              </header>
              <script>window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>
              </body>
              </html>
              '
        );
        static::assertSame(
            '<script>window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>',
            $dom->findOne('script')->html()
        );

        // ---

        $script = $dom->findOne('script');
        $script->outerHtml = '<script>window.jQuery||document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>';

        static::assertSame(
            '<script>window.jQuery||document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>',
            $dom->findOne('script')->html()
        );
    }

    public function testLoadNoValueAttribute()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="content"><div class="grid-container" ui-view>Main content here</div></div>');
        static::assertSame('<div class="content"><div class="grid-container" ui-view>Main content here</div></div>', $dom->innerHtml);
    }

    public function testLoadNoValueAttributeBefore()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="content"><div ui-view class="grid-container">Main content here</div></div>');
        static::assertSame('<div class="content"><div ui-view class="grid-container">Main content here</div></div>', $dom->innerHtml);
    }

    public function testSimpleHtmlViaSimpleXmlLoadString()
    {
        $html = (new HtmlDomParser())->load('<span>&lt;</span>');

        $expected = '<span>&lt;</span>';

        static::assertSame($expected, $html->xml());
        static::assertSame($expected, $html->html(false));
        static::assertSame($expected, $html->html(true));
    }

    public function testLoadUpperCase()
    {
        $dom = new HtmlDomParser();
        $dom->load('<DIV CLASS="ALL"><BR><P>hEY BRO, <A HREF="GOOGLE.COM">click here</A></BR></DIV>');
        static::assertSame('<br><p>hEY BRO, <a href="GOOGLE.COM">click here</a></p>', $dom->find('div', 0)->innerHtml);
    }

    public function testLoadWithFile()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-user font', 0)->text);
    }

    public function testLoadFromFile()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-user font', 0)->text);
    }

    public function testLoadFromFileFind()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-row div .post-user font', 0)->text);
    }

    public function testLoadUtf8()
    {
        $dom = new HtmlDomParser();
        $dom->load('<p>Dzień</p>');
        static::assertSame('Dzień', $dom->find('p', 0)->text);
    }

    public function testLoadFileBigTwice()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtmlFile(__DIR__ . '/fixtures/big.html');
        $post = $dom->find('.post-row', 0);
        static::assertSame('<p>Журчанье воды<br>' . "\n" . 'Черно-белые тени<br>' . "\n" . 'Вновь на фонтане</p>', $post->find('.post-message', 0)->innerHtml);
    }

    public function testToStringMagic()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        static::assertSame('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>', (string) $dom);
    }

    public function testGetMagic()
    {
        $dom = new HtmlDomParser();

        $html = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>';
        $expected = '<p>Hey bro, <a href="google.com">click here</a><br> :)</p>';

        $dom->load($html);
        static::assertSame($expected, $dom->innerHtml);

        // ---

        $dom = new HtmlDomParser();

        $html = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>';
        $expected = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>';

        $dom->load($html);
        static::assertSame($expected, $dom->html());
    }

    public function testGetElementById()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        static::assertSame('<a href="google.com" id="78">click here</a>', $dom->getElementById('78')->outerHtml);
    }

    public function testHtmlAndCssEdgeCase()
    {
        $dom = new HtmlDomParser();

        $html = '<p>lall</p><style><!--
h1 {
    color: red;
}
--></style><span>foo</span>';

        $dom->load($html);

        $elm = $dom->getElementsByTagName('style');
        static::assertSame(
            '<!--
h1 {
    color: red;
}
-->',
            $elm[0]->innerhtml
        );
    }

    public function testTextContent()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div><p>Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class .= 'lall';

        static::assertSame('<p class="lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);

        // ---

        $dom = new HtmlDomParser();
        $dom->load('<div><p>Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class = 'lall';

        static::assertSame('<p class="lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);

        // ---

        $dom = new HtmlDomParser();
        $dom->load('<div><p class="">Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class .= 'lall';

        static::assertSame('<p class="lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);

        // ---

        $dom = new HtmlDomParser();
        $dom->load('<div><p class="">Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class = 'lall';

        static::assertSame('<p class="lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);

        // ---

        $dom = new HtmlDomParser();
        $dom->load('<div><p class="foo">Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class .= ' lall';

        static::assertSame('<p class="foo lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);

        // ---

        $dom = new HtmlDomParser();
        $dom->load('<div><p class="foo">Proton Power & Light</p></div>');

        $p = $dom->findOne('p');
        $p->class = 'lall';

        static::assertSame('<p class="lall">Proton Power & Light</p>', $p->outerHtml());
        static::assertSame('Proton Power & Light', $p->textContent);
    }

    public function testTagExists()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div><p>lall</p></div>');

        $p = $dom->find('p');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $p);
        if (\count($p)) {
            $exists = true;
        } else {
            $exists = false;
        }
        static::assertTrue($exists);

        $span = $dom->find('span');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $span);
        if (\count($span)) {
            $exists = true;
        } else {
            $exists = false;
        }
        static::assertFalse($exists);

        // --

        $p = $dom->findMulti('p');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $p);
        if (\count($p)) {
            $exists = true;
        } else {
            $exists = false;
        }
        static::assertTrue($exists);

        $span = $dom->findMulti('span');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $span);
        if (\count($span)) {
            $exists = true;
        } else {
            $exists = false;
        }
        static::assertFalse($exists);

        // ---

        $p = $dom->findMultiOrFalse('p');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $p);
        if (\count($p)) {
            $exists = true;
        } else {
            $exists = false;
        }
        static::assertTrue($exists);

        $span = $dom->findMultiOrFalse('span');
        static::assertFalse($span);

        // ---

        $p = $dom->find('p', 0);
        static::assertInstanceOf(SimpleHtmlDomInterface::class, $p);

        $p = $dom->find('span', 0);
        static::assertInstanceOf(SimpleHtmlDomInterface::class, $p);

        // ---

        $p = $dom->findOne('p');
        static::assertInstanceOf(SimpleHtmlDomInterface::class, $p);

        $p = $dom->findOne('span');
        static::assertInstanceOf(SimpleHtmlDomInterface::class, $p);

        // ---

        $p = $dom->findOneOrFalse('p');
        static::assertInstanceOf(SimpleHtmlDomInterface::class, $p);

        $p = $dom->findOneOrFalse('span');
        static::assertFalse($p);
    }

    public function testGetElementsByTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        $elm = $dom->getElementsByTagName('p');
        static::assertSame(
            '<p>Hey bro, <a href="google.com" id="78">click here</a></p>',
            $elm[0]->outerHtml
        );
    }

    public function testGetElementsByClass()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        $elm = $dom->find('.all');
        static::assertSame(
            '<p>Hey bro, <a href="google.com" id="78">click here</a></p>',
            $elm[0]->innerHtml
        );
    }

    public function testInnerTextIssue()
    {
        $txt = <<<'___'
<div class="detail J_tab" id="tab_show_1">
    <h3 class="new-tit">
        <span class="name">product detail</span>
    </h3>
    <div class="detail-tit"></div>
    <table width="100%" cellpadding="0" cellspacing="0" class="detail-table">
        <tbody>
            <tr>
                <td>aaaaa</td>
                <td class="tc">bbbb</td>
                <td class="tc">ccccc</td>
            </tr>
        </tbody>
    </table>
    <div>
        <p>
            <b>[aaaaa]</b>
        </p>
        <p></p>
        <div>bbbbbb</div>
        <div>ccccccccccccccc</div>
        <div>
            <br>
        </div>
        <p></p>
        <p>
            <b>[ddddd]</b>
        </p>
    </div>
</div>
___;
        $expected = '<p>
            <b>[aaaaa]</b>
        </p>
        <p></p>
        <div>bbbbbb</div>
        <div>ccccccccccccccc</div>
        <div>
            <br>
        </div>
        <p></p>
        <p>
            <b>[ddddd]</b>
        </p>';

        $html_meal = HtmlDomParser::str_get_html($txt);
        $result = $html_meal->findOne('#tab_show_1 table')->nextNonWhitespaceSibling()->innertext;

        static::assertSame($expected, $result);
    }

    public function testGetHtmlInner()
    {
        $dom = new HtmlDomParser();
        $dom->load('
        <span class="main">
          <span class="old">
            Price&nbsp;<em>$</em>2188
          </span>
        </span>
        ');

        $innerHtml = $dom->findOneOrFalse('.main .old');
        static::assertNotFalse($innerHtml);

        static::assertSame(
            'Price&nbsp;<em>$</em>2188',
            $innerHtml->innerHtml()
        );

        static::assertSame(
            '2188',
            \preg_replace('/.*<\/em>/ius', '', $innerHtml->innerHtml())
        );
    }

    public function testUtf8AndBrokenHtmlEncoding()
    {
        $dom = new HtmlDomParser();
        $dom->load('hi سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hi سلام<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );

        // ---

        $dom = new HtmlDomParser();
        $dom->load('hi</b>سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hiسلام<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );

        // ---

        $dom = new HtmlDomParser();
        $dom->load('hi</b><p>سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hi<p>سلام' . "\n" . '<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );
    }

    public function testEnforceEncoding()
    {
        $dom = new HtmlDomParser();
        $dom->load('tests/files/horrible.html');

        static::assertNotSame('<input type="submit" tabindex="0" name="submit" value="Информации" />', $dom->find('table input', 1)->outerHtml);
    }

    public function testReplaceToPreserveHtmlEntities()
    {
        $tests = [
            // non url && non dom special chars -> no changes
            '' => '',
            // non url && non dom special chars -> no changes
            ' ' => ' ',
            // non url && non dom special chars -> no changes
            'abc' => 'abc',
            // non url && non dom special chars -> no changes
            'öäü' => 'öäü',
            // non url && non dom special chars -> no changes
            '`?/=()=$"?#![{`' => '`?/=()=$"?#![{`',
            // non url && non dom special chars -> no changes
            '{{foo}}' => '{{foo}}',
            // dom special chars -> changes
            '`?/=()=$&,|,+,%"?#![{@`' => '`?/=()=$____SIMPLE_HTML_DOM__VOKU__AMP____,____SIMPLE_HTML_DOM__VOKU__PIPE____,____SIMPLE_HTML_DOM__VOKU__PLUS____,____SIMPLE_HTML_DOM__VOKU__PERCENT____"?#![{____SIMPLE_HTML_DOM__VOKU__AT____`',
            // non url && non dom special chars -> no changes
            'www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}' => 'www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP____{{foo}}',
            // url -> changes
            '[https://www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}#bar]' => '____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_LEFT____https://www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT____foo____SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT________SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT____#bar____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_RIGHT____',
            // url -> changes
            'https://www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}#foo' => 'https://www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT____foo____SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT________SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT____#foo',
        ];

        foreach ($tests as $test => $expected) {
            $result = HtmlDomParser::replaceToPreserveHtmlEntities($test);
            static::assertSame($expected, $result);

            $result = HtmlDomParser::putReplacedBackToPreserveHtmlEntities($result);
            static::assertSame($test, $result);
        }
    }

    public function testUseXPath()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '
            <html>
              <head></head>
              <body>
                <p>.....</p>
                <script>
                Some code ... 
                document.write("<script src=\'some script\'><\/script>") 
                Some code ... 
                </script>
                <p>....</p>
              </body>
            </html>'
        );
        $elm = $dom->find('*');
        static::assertSame('.....', $elm[3]->innerHtml);

        $elm = $dom->find('//*');
        static::assertSame('.....', $elm[3]->innerHtml);
    }

    public function testScriptCleanerScriptTag()
    {
        $dom = new HtmlDomParser();
        $dom->load(
            '
            <p>.....</p>
            <script>
            Some code ... 
            document.write("<script src=\'some script\'><\/script>") 
            Some code ... 
            </script>
            <p>....</p>'
        );
        $elm = $dom->getElementsByTagName('p');
        static::assertSame('....', $elm[1]->innerHtml);
    }

    public function testEmptyString()
    {
        $dom = HtmlDomParser::str_get_html('');
        $tag = $dom->findOne('meta[name="myToken"]');

        static::assertSame('', $tag->innerText());
    }

    public function testSpecialScriptTag()
    {
        // init
        $html = '
        <!doctype html>
        <html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script id="elements-image-2" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            <script class="foobar" type=\'text/html\'>
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type=text/html>
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
        </body>
        </html>
        ';

        $expected = '
        <!DOCTYPE html>' . "\n" . '<html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script id="elements-image-2" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
        </body>
        </html>
        ';

        $dom = new HtmlDomParser();

        $html = \str_replace(["\r\n", "\r", "\n"], "\n", (string) $dom->load($html));
        $expected = \str_replace(["\r\n", "\r", "\n"], "\n", $expected);

        static::assertSame(\trim($expected), \trim($html));
    }

    public function testJavaScriptTemplateTag()
    {
        $html = "
            <!doctype html>
            <html lang=\"nl\">
                <head>
                </head>
              <body>
              
              <div class=\"price-box price-tier_price\" data-role=\"priceBox\" data-product-id=\"1563\" data-price-box=\"product-id-1563\">
              </div>
              
              <script type=\"text/x-custom-template\" id=\"tier-prices-template\">
                <ul class=\"prices-tier items\">
                    <% _.each(tierPrices, function(item, key) { %>
                    <%  var priceStr = '<span class=\"price-container price-tier_price\">'
                            + '<span data-price-amount=\"' + priceUtils.formatPrice(item.price, currencyFormat) + '\"'
                            + ' data-price-type=\"\"' + ' class=\"price-wrapper \">'
                            + '<span class=\"price\">' + priceUtils.formatPrice(item.price, currencyFormat) + '</span>'
                            + '</span>'
                        + '</span>'; %>
                    <li class=\"item\">
                        <%= 'some text %1 %2'.replace('%1', item.qty).replace('%2', priceStr) %>
                        <strong class=\"benefit\">
                           save <span class=\"percent tier-<%= key %>\">&nbsp;<%= item.percentage %></span>%
                        </strong>
                    </li>
                    <% }); %>
                </ul>
              </script>
              
              <div data-role=\"tier-price-block\"></div>
              
              </body>
            </html>
            ";

        $expected = '<!DOCTYPE html>
<html lang="nl">
                <head>
                </head>
              <body>
              
              <div class="price-box price-tier_price" data-role="priceBox" data-product-id="1563" data-price-box="product-id-1563">
              </div>
              
              <script type="text/x-custom-template" id="tier-prices-template">
                <ul class="prices-tier items">
                    <% _.each(tierPrices, function(item, key) { %>
                    <%  var priceStr = \'<span class="price-container price-tier_price">\'
                            + \'<span data-price-amount="\' + priceUtils.formatPrice(item.price, currencyFormat) + \'"\'
                            + \' data-price-type=""\' + \' class="price-wrapper ">\'
                            + \'<span class="price">\' + priceUtils.formatPrice(item.price, currencyFormat) + \'</span>\'
                            + \'</span>\'
                        + \'</span>\'; %>
                    <li class="item">
                        <%= \'some text %1 %2\'.replace(\'%1\', item.qty).replace(\'%2\', priceStr) %>
                        <strong class="benefit">
                           save <span class="percent tier-<%= key %>">&nbsp;<%= item.percentage %></span>%
                        </strong>
                    </li>
                    <% }); %>
                </ul>
              </script>
              
              <div data-role="tier-price-block"></div>
              
              </body>
            </html>';

        $dom = new HtmlDomParser();

        $html = \str_replace(["\r\n", "\r", "\n"], "\n", (string) $dom->load($html));
        $expected = \str_replace(["\r\n", "\r", "\n"], "\n", $expected);

        static::assertSame(\trim($expected), \trim($html));
    }

    public function testHtmlEmbeddedInJavaScript()
    {
        $html = '
        <!doctype html>
        <html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1">
              var strJS = "<strong>foobar<\/strong>";
            </script>
        </body>
        </html>
        ';

        $expected = '
        <!DOCTYPE html>' . "\n" . '<html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1">
              var strJS = "<strong>foobar<\/strong>";
            </script>
        </body>
        </html>';

        $dom = new HtmlDomParser();

        $html = \str_replace(["\r\n", "\r", "\n"], "\n", (string) $dom->load($html));
        $expected = \str_replace(["\r\n", "\r", "\n"], "\n", $expected);

        static::assertSame(\trim($expected), \trim($html));
    }

    public function testBeforeClosingTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="stream-container "  > <div class="stream-item js-new-items-bar-container"> </div> <div class="stream">');
        static::assertSame('<div class="stream-container "> <div class="stream-item js-new-items-bar-container"> </div> <div class="stream"></div>' . "\n" . '</div>', (string) $dom);
    }

    public function testCodeTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<strong>hello</strong><code class="language-php">$foo = "bar";</code>');
        static::assertSame('<strong>hello</strong><code class="language-php">$foo = "bar";</code>', (string) $dom);
    }

    public function testDeleteNodeOuterHtml()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $a = $dom->find('a');
        $a[0]->outerHtml = '';
        unset($a);
        static::assertSame('<div class="all"><p>Hey bro, <br> :)</p></div>', (string) $dom);
    }

    public function testDeleteNodeInnerHtml()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $a = $dom->find('div.all');
        $a[0]->innerHtml = '';
        unset($a);
        static::assertSame('<div class="all"></div>', (string) $dom);
    }

    public function testDataJsonInHtml()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div data-json=\'{"key":"value"}\'></div>');
        $div = $dom->find('div');
        static::assertSame('<div data-json=\'{"key":"value"}\'></div>', (string) $div);
    }

    public function testHtmlInAttribute()
    {
        $html = '<button type="button" id="rotate_crop" class="btn btn-primary" data-loading-text="<i class=\'fa fa-spinner fa-spin\'></i> Rotando..." style="">Rotar</button>';

        $dom = new HtmlDomParser();
        $dom->load($html);
        $button = $dom->find('button');
        static::assertSame($html, (string) $button);
    }

    public function testAmpHtmlStuff()
    {
        $dom = new HtmlDomParser();
        $dom->load('<html ⚡>foo</html>');
        $html = $dom->find('html');
        static::assertSame('<html ⚡>foo</html>', (string) $html);

        // ---

        $html = '
        <!doctype html>
            <html amp lang="en">
              <head>
                <meta charset="utf-8">
                <script async src="https://cdn.ampproject.org/v0.js"></script>
                <title>Hello, AMPs</title>
                <link rel="canonical" href="https://amp.dev/documentation/guides-and-tutorials/start/create/basic_markup">
                <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
                <script type="application/ld+json">
                  {
                    "@context": "http://schema.org",
                    "@type": "NewsArticle",
                    "headline": "Open-source framework for publishing content",
                    "datePublished": "2015-10-07T12:02:41Z",
                    "image": [
                      "logo.jpg"
                    ]
                  }
                </script>
                <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
              </head>
              <body>
                <h1>Welcome to the mobile web</h1>
              </body>
            </html>';

        $expected = '<html amp lang="en">
              <head>
                <meta charset="utf-8">
                <script async src="https://cdn.ampproject.org/v0.js"></script>
                <title>Hello, AMPs</title>
                <link rel="canonical" href="https://amp.dev/documentation/guides-and-tutorials/start/create/basic_markup">
                <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
                <script type="application/ld+json">
                  {
                    "@context": "http://schema.org",
                    "@type": "NewsArticle",
                    "headline": "Open-source framework for publishing content",
                    "datePublished": "2015-10-07T12:02:41Z",
                    "image": [
                      "logo.jpg"
                    ]
                  }
                </script>
                <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
<noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
              </head>
              <body>
                <h1>Welcome to the mobile web</h1>
              </body>
            </html>';

        $dom = new HtmlDomParser();
        $dom->load($html);
        $html = $dom->find('html');
        static::assertSame($expected, (string) $html);
    }

    public function testScriptInCommentHtml()
    {
        // --- via load()

        $dom = new HtmlDomParser();
        $dom->load(
            '
      <script class="script_1" type="text/javascript">someCode</script>
      <!-- <script class="script_2" type="text/javascript">someCode</script> -->
    '
        );
        $script = $dom->find('script');
        static::assertSame('<script class="script_1" type="text/javascript">someCode</script>', (string) $script);

        // --- via "str_get_html()"

        $dom = HtmlDomParser::str_get_html(
            '
      <script class="script_1" type="text/javascript">someCode</script>
      <!-- <script class="script_2" type="text/javascript">someCode</script> -->
    '
        );
        $script = $dom->find('script');
        static::assertSame('<script class="script_1" type="text/javascript">someCode</script>', (string) $script);
    }

    public function testHtmlAndJavaScriptMix()
    {
        $htmlAndJs = '<p>Text 1</p><script>$(".second-column-mobile-inner").wrapAll("<div class=\'collapse\' id=\'second-column\'></div>");</script><p>Text 2</p>';

        $dom = HtmlDomParser::str_get_html($htmlAndJs);
        $script = $dom->find('script');
        static::assertSame('<script>$(".second-column-mobile-inner").wrapAll("<div class=\'collapse\' id=\'second-column\'><\/div>");</script>', (string) $script);
    }

    public function testSpecialCharsAndPlaintext()
    {
        $file = __DIR__ . '/fixtures/test_page_plaintext.html';
        $dom = new HtmlDomParser();
        $dom->loadHtmlFile($file);

        $review_content = $dom->find('.review-content p');
        static::assertInstanceOf(SimpleHtmlDomNode::class, $review_content);

        $allReviews = '';
        foreach ($review_content as $review) {
            $allReviews .= $review->plaintext . "\n";
        }
        static::assertTrue(\strlen($allReviews) > 0);
        static::assertContains('It&#39;s obvious having', $allReviews);
        static::assertContains('2006 Volvo into Dave&#39;s due', $allReviews);
    }

    /**
     * This assumes that a script with the variable "$json_variable" is present containing the
     * complete dataset.
     *
     * @param string $html
     * @param string $json_variable
     *
     * @return stdClass|null
     */
    private function extractJson(string $html, string $json_variable = 'INITIAL_DATA')
    {
        // init
        $content_line = null;

        if (!$html) {
            return null;
        }

        $dom = HtmlDomParser::str_get_html($html);

        foreach ($dom->find('script') as $script) {
            $content = $script->innerHtml();

            if (\strpos($content, $json_variable) !== false) {
                $content_exploded = \explode("\n", $content);

                foreach ($content_exploded as $content_tmp) {
                    if (\strpos($content_tmp, $json_variable) !== false) {
                        $content_line = \trim($content_tmp);

                        break 2;
                    }
                }
            }
        }

        if (!$content_line) {
            return null;
        }

        $json_helper_position = \mb_strpos($content_line, '{');
        $json = \mb_substr($content_line, $json_helper_position, \mb_strrpos($content_line, '}') - $json_helper_position + 1);

        /** @noinspection PhpComposerExtensionStubsInspection */
        $data = \json_decode($json, false);

        return $data ?: null;
    }

    public function testHtmlInsideJavaScriptTemplates()
    {
        $html = '
        <script type=text/html>
            <p>Foo</p>
        
            <div class="alert alert-success">
                Bar
            </div>
            
            {{foo}}
            
            {% if foo == true %}
              priceStr = \'<span class="price-container price-tier_price">\'
              <div>
            {% else %}
              priceStr = \'<span>\'
            {% endif %}
            
            {{priceStr}}</span>
            
            {% if foo == true %}
              </div>
            {% endif %}
        </script>
        ';

        // ---

        $d = new voku\helper\HtmlDomParser();
        $d->overwriteTemplateLogicSyntaxInSpecialScriptTags(['{#']);
        $d->loadHtml($html);

        $expectedDomError = '<script type="text/html">
            <p>Foo</p>
        
            <div class="alert alert-success">
                Bar
            </div>
            
            {{foo}}
            
            {% if foo == true %}
              priceStr = \'<span class="price-container price-tier_price">\'
              <div>
            {% else %}
              priceStr = \'<span>\'
            {% endif %}
            
            {{priceStr}}</span>
            
            {% if foo == true %}
              </div>
            {% endif %}
        </span></script>';

        static::assertSame($expectedDomError, $d->html());

        // ---

        $d = new voku\helper\HtmlDomParser();
        $d->overwriteTemplateLogicSyntaxInSpecialScriptTags(['{%']);
        $d->loadHtml($html);

        $expectedNonDomError = '<script type="text/html">
            <p>Foo</p>
        
            <div class="alert alert-success">
                Bar
            </div>
            
            {{foo}}
            
            {% if foo == true %}
              priceStr = \'<span class="price-container price-tier_price">\'
              <div>
            {% else %}
              priceStr = \'<span>\'
            {% endif %}
            
            {{priceStr}}</span>
            
            {% if foo == true %}
              </div>
            {% endif %}
        </script>';

        static::assertSame($expectedNonDomError, $d->html());
    }

    public function testOverwriteTemplateLogicSyntaxInSpecialScriptTagsError()
    {
        static::expectException(InvalidArgumentException::class);

        $d = new voku\helper\HtmlDomParser();
        $d->overwriteTemplateLogicSyntaxInSpecialScriptTags([['{{']]);
    }

    public function testExtractJson()
    {
        $data = '<script type="text/javascript">
        var CONFIG = {"environment":"production","environmentSuffix":"","baseUri":"\/","debug":false,"gtmCode":"GTM-WJRBWVS","disallowAll":false,"defaultLanguage":"nl","languages":["nl","en"],"bioPortalUrl":"http:\/\/bioportal.naturalis.nl\/specimen\/","absoluteUrl":"http:\/\/topstukken.naturalis.nl","currentPath":"object\/malacostraca-podophthalmata-brittanniae","currentUrl":"http:\/\/topstukken.naturalis.nl\/object\/malacostraca-podophthalmata-brittanniae"};
        var INITIAL_DATA = {"general":{"title":"Naturalis","nav":{"main":[{"url":"\/","label":"Overzicht"},{"url":"http:\/\/naturalis.nl","label":"Naturalis.nl","external":true}],"latestLabel":"Laatst toegevoegd","latest":[{"url":"\/object\/syrische-bruine-beer","label":"Syrische bruine beer"},{"url":"\/object\/siberische-tijger","label":"Siberische tijger"},{"url":"\/object\/zwarte-wolf","label":"Zwarte wolf"}],"social":[{"type":"facebook","url":"https:\/\/www.facebook.com\/museumnaturalis\/"},{"type":"twitter","url":"https:\/\/twitter.com\/museumnaturalis"},{"type":"instagram","url":"https:\/\/www.instagram.com\/naturalismuseum\/"},{"type":"youtube","url":"https:\/\/www.youtube.com\/user\/NaturalisLeiden"}],"legal":[],"copyright":"&copy; Naturalis Biodiversity Center","about":{"title":"Over Topstukken","body":"Natuurhistorische collecties zijn al eeuwen de spil van het onderzoek naar de natuur. Ze vormen een belangrijk modern wetenschappelijk instrument voor de mens om vat te krijgen op de natuurlijke omgeving en diens oorsprong. De collecties, de daarin verborgen en daaraan gekoppelde informatie, vormen de ruggengraat van het onderzoek naar geologische en biologische diversiteit. Ze helpen om de biodiversiteit uit heden en verleden in kaart te brengen, te benoemen en te begrijpen. Ze spelen een sleutelrol in het zoeken naar oplossingen voor een gezonde toekomst van de mensheid.<br><br>Naturalis heeft wereldwijd \u00e9\u00e9n van de grootste natuurhistorische collecties. Met meer dan 41 miljoen objecten is de collectie is van grote maatschappelijke, historische en wetenschappelijke waarde. De collectie is bovendien uitzonderlijk goed ontsloten, doordat de gehele verzameling digitaal is geregistreerd en toegankelijk gemaakt.<br><br>Onze onderzoekers en collectiebeheerders selecteren steeds weer de meest mooie of bijzondere objecten om die in Naturalis en op het web een plek te geven. Daarmee proberen we iedereen het WAUW!-effect te laten beleven dat wij zelf dagelijks ervaren als wij werken met de collectie."}}},"locale":{"infoTitle":"Details","info":{"scientificName":"Wetenschappelijke naam","collection":"Hoort bij collectie","year":"Jaar","country":"Land van herkomst","expedition":"Verzameld tijdens expeditie","collector":"Verzamelaar","author":"Auteur","illustrator":"Illustrator","registrationNumber":"Registratienummer"},"infoActionLabel":"Alle gegevens van dit object"},"alternate":null,"grid":null,"specimen":{"title":"Malacostraca Podophthalmata Brittanniae","titleSoftHyphen":null,"id":207,"registrationNumber":"RBR Holt 00626 & RBR Holt 00732","slug":"malacostraca-podophthalmata-brittanniae","language":"nl","metatags":{"title":false,"description":false},"opengraph":{"image":{"src":"\/assets\/styles\/og_image\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=c818156e&itok=dNnJ_Z2n","alt":"","aspectRatio":1,"placeholder":"\/assets\/styles\/researcher_placeholder\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?itok=70dDOiNr"}},"subtitle":null,"image":{"srcSet":{"1920":"\/assets\/styles\/specimen_header_1920\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=sIwXRgEp","1280":"\/assets\/styles\/specimen_header_1280\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=szXLga7d","960":"\/assets\/styles\/specimen_header_960\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=aZhryErb","640":"\/assets\/styles\/specimen_header_640\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=Ceou5BJz","320":"\/assets\/styles\/specimen_header_320\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=X7V_sVbe"},"alt":"","aspectRatio":1.7778,"placeholder":"\/assets\/styles\/specimen_header_placeholder\/public\/content\/specimen\/image\/DSC_3463test_0.jpg?h=43b24274&itok=Ag2eqNDJ"},"info":{"collection":"Bibliotheek en archief","country":"Verenigd Koninkrijk","scientificName":null,"year":"1815","expedition":null,"collector":null,"registrationNumber":"RBR Holt 00626 & RBR Holt 00732"},"bioPortal":false,"blocks":[{"type":"textAndImage","title":"Mooiste kreeftenboek","body":"<p dir=\"ltr\">Zonder twijfel is Malacostraca Podophthalmata Brittanniae (1815-1875) een van de mooiste publicaties gewijd aan kreeftachtigen. Dit fantastische overzicht met in totaal 54 fraaie handgekleurde platen is het werk van twee bekende namen in de Britse natuurgeschiedenis: de jonge zo\u00f6loog William Elford Leach (1791-1836) en de ervaren naturalist en graveur James Sowerby (1757-1822). Leach\u2019 wetenschappelijke productiviteit was enorm, maar zijn carri\u00e8re kwam vroegtijdig tot een einde door een inzinking waar hij niet meer van herstelde. Na de zeventiende aflevering die in 1820 verscheen, stopte de publicatie. Pas in 1875 werd het boek met twee extra afleveringen en zes nieuwe platen door de uitgever voltooid op aandringen van de zoon van James.<\/p>\r\n","images":{"srcSet":{"940":"\/assets\/styles\/specimen_content_item_940\/public\/content\/paragraph\/content-block-image\/15_05%20%281%29test_0.jpg?h=2d399185&itok=XnrWaqqU","705":"\/assets\/styles\/specimen_content_item_705\/public\/content\/paragraph\/content-block-image\/15_05%20%281%29test_0.jpg?h=2d399185&itok=qJBuiRGq","470":"\/assets\/styles\/specimen_content_item_470\/public\/content\/paragraph\/content-block-image\/15_05%20%281%29test_0.jpg?h=2d399185&itok=8Dh5dLJZ","235":"\/assets\/styles\/specimen_content_item_235\/public\/content\/paragraph\/content-block-image\/15_05%20%281%29test_0.jpg?h=2d399185&itok=P43ZVVdU"},"alt":"Malacostraca Podophthalmata Brittanniae","aspectRatio":0.8704,"placeholder":"\/assets\/styles\/specimen_content_item_placeholder\/public\/content\/paragraph\/content-block-image\/15_05%20%281%29test_0.jpg?h=2d399185&itok=WAnO49iI"},"buttons":[],"researcher":null},{"type":"textAndImage","title":"Kostbare drukproeven","body":"<p dir=\"ltr\">In de bibliotheek van Naturalis bevinden zich twee versies van dit bijzondere werk: een prachtig gebonden exemplaar versierd met vergulde krabben, en een complete set van de negentien losse afleveringen. Ze maken deel uit van de Bibliotheca Carcinologica, een unieke collectie van voormalig Naturalis curator Lipke Bijdeley Holthuis (1921-2008). Holthuis was al in bezit van het gebonden exemplaar toen hij voor veel geld de losse afleveringen kocht. Dat lijkt wat overdreven, maar het zijn de originele drukproeven met aantekeningen die Leach maakte voor Sowerby. De drukproeven geven dus een bijzonder mooi inzicht in de publicatiegeschiedenis en de manier waarop de twee naturalisten samenwerkten.<\/p>\r\n","images":{"srcSet":{"940":"\/assets\/styles\/specimen_content_item_940\/public\/content\/paragraph\/content-block-image\/DSC_3476test_0.jpg?h=8a700d67&itok=fTCB5t-d","705":"\/assets\/styles\/specimen_content_item_705\/public\/content\/paragraph\/content-block-image\/DSC_3476test_0.jpg?h=8a700d67&itok=BS_8ewLd","470":"\/assets\/styles\/specimen_content_item_470\/public\/content\/paragraph\/content-block-image\/DSC_3476test_0.jpg?h=8a700d67&itok=0EeQd9f_","235":"\/assets\/styles\/specimen_content_item_235\/public\/content\/paragraph\/content-block-image\/DSC_3476test_0.jpg?h=8a700d67&itok=153tqwMq"},"alt":"Malacostraca Podophthalmata Brittannia","aspectRatio":0.8704,"placeholder":"\/assets\/styles\/specimen_content_item_placeholder\/public\/content\/paragraph\/content-block-image\/DSC_3476test_0.jpg?h=8a700d67&itok=CmnTyPiY"},"buttons":[{"label":"Meer weten? Lees deze blogpost","url":"https:\/\/blog.biodiversitylibrary.org\/2017\/12\/magnificent-crustacea-leach-and-sowerbys-malacostraca-podophthalmata-brittanniae.html","external":true},{"label":"Bekijk het boek in de Biodiversity Heritage Library","url":"https:\/\/www.biodiversitylibrary.org\/page\/51482094?utm_medium=social%20media&utm_source=blogger&utm_campaign=Book%20of%20the%20Month&utm_content=Naturalis%20Biodiversity%20Center#page\/5\/mode\/1up","external":true}],"researcher":{"name":"Lipke Bijdeley Holthuis","role":"Onderzoeker kreeftachtigen (1921-2008)","image":{"src":"\/assets\/styles\/researcher\/public\/content\/researcher\/17_04_Holthuis1_Volkskrant_2001.jpg?h=20fd3246&itok=DOaetnxM","alt":"","aspectRatio":1,"placeholder":"\/assets\/styles\/researcher_placeholder\/public\/content\/researcher\/17_04_Holthuis1_Volkskrant_2001.jpg?h=20fd3246&itok=CI7RI_uW"}}}],"related":{"title":"Bekijk ook","items":[{"title":"Schorswants","id":173,"language":"nl","url":"schorswants","image":{"srcSet":{"1920":"\/assets\/styles\/specimen_header_1920\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=IXOnjXjW","1280":"\/assets\/styles\/specimen_header_1280\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=LCsG-WJn","960":"\/assets\/styles\/specimen_header_960\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=4YosWKRa","640":"\/assets\/styles\/specimen_header_640\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=QUrxxuiJ","320":"\/assets\/styles\/specimen_header_320\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=Z_FS7_kG"},"alt":"","aspectRatio":1.7778,"placeholder":"\/assets\/styles\/specimen_header_placeholder\/public\/content\/specimen\/image\/PSE%20Kopie%20van%20RMNH.INS_.1089600%206.jpg?h=10080870&itok=tQlkb8R2"}},{"title":"Gevleugelde papiernautilus","id":120,"language":"nl","url":"gevleugelde-papiernautilus","image":{"srcSet":{"1920":"\/assets\/styles\/specimen_header_1920\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=WHkeq3YQ","1280":"\/assets\/styles\/specimen_header_1280\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=zH_Y_l5n","960":"\/assets\/styles\/specimen_header_960\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=9jvOIqqE","640":"\/assets\/styles\/specimen_header_640\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=YNKgxKFA","320":"\/assets\/styles\/specimen_header_320\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=dhapG-Rr"},"alt":"","aspectRatio":1.7778,"placeholder":"\/assets\/styles\/specimen_header_placeholder\/public\/content\/specimen\/image\/RMNH.MOL_.8-9_2_HL_1bewerktbreed.jpg?h=318c2c63&itok=YWQ5snQF"}}]}}};
        </script>';

        $result = $this->extractJson($data);

        static::assertNotNull($result);
        static::assertInstanceOf(\stdClass::class, $result, \print_r($result, true));
    }

    public function testIssue42()
    {
        $d = new voku\helper\HtmlDomParser();

        $d->loadHtml('<p>p1</p><p>p2</p>');
        static::assertSame('<p>p1</p>' . "\n" . '<p>p2</p>', (string) $d);

        $d->loadHtml('<div><p>p1</p></div>');
        static::assertSame('<div><p>p1</p></div>', (string) $d);
    }

    public function testIssue53()
    {
        $d = new voku\helper\HtmlDomParser();

        $html = '
        <blockquote class="bg-gray primary">
            <p class="text-monospace">
                Malwarebytes<br>
                www.malwarebytes.com<br>
                User: User-\<wbr>u00d0\<wbr>u009f\<wbr>u00d0\<wbr>u009a\<wbr>User<br>
                <br>
                Windows (WMI): 0<br>
                (end)<br>
            </p>
        </blockquote>
        ';

        $expected = '
        <blockquote class="bg-gray primary">
            <p class="text-monospace">
                Malwarebytes<br>
                www.malwarebytes.com<br>
                User: User-\<wbr>u00d0\<wbr>u009f\<wbr>u00d0\<wbr>u009a\<wbr>User<br>
                <br>
                Windows (WMI): 0<br>
                (end)<br>
            </p>
        </blockquote>
        ';

        $d->loadHtml($html);
        static::assertSame(\trim($expected), (string) $d);
    }

    public function testInvalidHtml()
    {
        $html = '<!DOCTYPE HTML>
        <html>
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        </html>
        <div id="åäö">
            body
        </div>
        ';

        $expected = '<!DOCTYPE HTML>
<html>
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        
        <div id="åäö">
            body
        </div>
        </html>';

        $domTree = \voku\helper\HtmlDomParser::str_get_html($html);

        static::assertSame($expected, $domTree->html());

        static::assertSame(['an apple'], $domTree->find('#a')->text());

        static::assertSame(['body'], $domTree->find('#åäö')->text());
    }

    public function testHtmlWithSpecialComments()
    {
        $html = '<!-- === BEGIN TOP === -->
        <!DOCTYPE html>
        <!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
        <!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
        <!--[if !IE]><!-->
        <html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        </html>
        <div id="åäö">
            body
        </div>
        ';

        $expected = '<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]--><!--[if IE 9]> <html lang="en" class="ie9"> <![endif]--><!--[if !IE]><!--><html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        
        <div id="åäö">
            body
        </div>
        </html>';

        $domTree = \voku\helper\HtmlDomParser::str_get_html($html);

        static::assertSame($expected, $domTree->html());

        static::assertSame(['an apple'], $domTree->find('#a')->text());

        static::assertSame(['body'], $domTree->find('#åäö')->text());
    }

    public function testHtmlWithSpecialCommentsAndKeepBrokenHtml()
    {
        $html = '<!-- === BEGIN TOP === -->
        <!DOCTYPE html>
        <!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
        <!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
        <!--[if !IE]><!-->
        <html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        </html>
        <div id="åäö">
            body
        </div>
        ';

        $expected = '<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]--><!--[if IE 9]> <html lang="en" class="ie9"> <![endif]--><!--[if !IE]><!--><html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        
        <div id="åäö">
            body
        </div></html>';

        $dom = new HtmlDomParser();
        $dom = $dom->useKeepBrokenHtml(true);
        $domTree = $dom->load($html);

        static::assertSame($expected, $domTree->html());

        static::assertSame(['an apple'], $domTree->find('#a')->text());

        static::assertSame(['body'], $domTree->find('#åäö')->text());
    }

    public function testHtmlWithSpecialCommentsAndKeepBrokenHtml2()
    {
        $html = '<!-- === BEGIN TOP === -->
        <!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
        <!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
        <!--[if !IE]><!-->
        <html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        </html>
        <div id="åäö">
            body
        </div>
        ';

        $expected = '<!-- === BEGIN TOP === -->
        <!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
        <!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
        <!--[if !IE]><!-->
        <html prefix="og: http://ogp.me/ns#" lang="ru">
        <!--<![endif]-->
        <head>
            <title>title</title>
        </head>
        
        <body>
        <div id="a">
            an apple
        </div>
        </body>
        
        
        <div id="åäö">
            body
        </div></html>';

        $dom = new HtmlDomParser();
        $dom = $dom->useKeepBrokenHtml(true);
        $domTree = $dom->load($html);

        static::assertSame($expected, $domTree->html());

        static::assertSame(['an apple'], $domTree->find('#a')->text());

        static::assertSame(['body'], $domTree->find('#åäö')->text());
    }

    public function testFindClassTest()
    {
        $html = "
        <div class='services'></div> or
        <div class='services last-item'></div> or
        <div class='services active'></div>
        ";

        $d = new voku\helper\HtmlDomParser();
        $d->load($html);

        $htmlResult = '';
        foreach ($d->find('.services') as $e) {
            $e->setAttribute('data-foo', 'bar');
            $htmlResult .= $e->html();
        }

        $htmlExpected = '<div class="services" data-foo="bar"></div><div class="services last-item" data-foo="bar"></div><div class="services active" data-foo="bar"></div>';

        static::assertSame($htmlExpected, $htmlResult);

        // ---

        $d = new voku\helper\HtmlDomParser();
        $d->load($html);

        $htmlResult = '';
        foreach ($d->find('div[class~=services]') as $e) {
            $e->setAttribute('data-foo', 'bar');
            $htmlResult .= $e->html();
        }

        $htmlExpected = '<div class="services" data-foo="bar"></div><div class="services last-item" data-foo="bar"></div><div class="services active" data-foo="bar"></div>';

        static::assertSame($htmlExpected, $htmlResult);
    }
}
