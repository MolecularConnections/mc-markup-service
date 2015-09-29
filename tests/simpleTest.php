<?php

class simpleTest extends PHPUnit_Framework_TestCase
{
    private $jats_folder = '';
    private $html_folder = '';

    public function setUp()
    {
        $this->jats_folder = 'tests/fixtures/jats/';
        $this->html_folder = 'tests/fixtures/html/';
    }

    public function testJatsToHtmlAbstract() {
        $compares = $this->compareHtmlSection('-section-abstract', 'Abstract');
        $this->runHtmlComparisons($compares);
    }

    public function testJatsToHtmlDigest() {
        $compares = $this->compareHtmlSection('-section-digest', 'Digest');
        $this->runHtmlComparisons($compares);
    }

    public function testJatsToHtmlAcknowledgements() {
        $compares = $this->compareHtmlSection('-section-acknowledgements', 'Acknowledgments');
        $this->runHtmlComparisons($compares);
    }

    public function testJatsToHtmlAuthorResponse() {
        $compares = $this->compareHtmlSection('-section-author-response', 'Author response');
        $this->runHtmlComparisons($compares);
    }

    public function testJatsToHtmlDecisionLetter() {
        $compares = $this->compareHtmlSection('-section-decision-letter', 'Decision letter');
        $this->runHtmlComparisons($compares);
    }

    public function testJatsToHtmlReferences() {
        $compares = $this->compareHtmlSection('-section-references', 'References');
        $this->runHtmlComparisons($compares);
    }

    /**
     * Compare the expect and actual HTML results.
     *
     * @param array[] $compares
     */
    protected function runHtmlComparisons($compares) {
        $this->runComparisons($compares, 'assertEqualHtml');
    }

    /**
     * Compare the expect and actual results.
     *
     * @param array[] $compares
     * @param string $method
     */
    protected function runComparisons($compares, $method = 'assertEquals') {
        foreach ($compares as $compare) {
            call_user_func_array([$this, $method], $compare);
        }
    }

    /**
     * Prepare array of actual and expected results.
     */
    protected function compareHtmlSection($suffix, $section) {
        $html_prefix = '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
        $expected = 'expected';
        $htmls = glob($this->html_folder . "*" . $suffix . ".html");
        $compares = [];

        libxml_use_internal_errors(TRUE);
          foreach ($htmls as $html) {
            $file = str_replace($suffix, '', basename($html, '.html'));
            $actualDom = new DOMDocument();
            $actual_html = $this->getSection($file, $section);
            $actualDom->loadHTML($actual_html);

            $expectedDom = new DOMDocument();
            $expected_html = file_get_contents($html);
            $expectedDom->loadHTML($html_prefix . '<' . $expected . '>' . $expected_html . '</' . $expected . '>');

            $compares[] = [
                $this->getInnerHtml($expectedDom->getElementsByTagName($expected)->item(0)),
                $this->getInnerHtml($actualDom->getElementsByTagName('body')->item(0)),
            ];
        }
        libxml_clear_errors();

        return $compares;
    }

    protected function getSection($file, $section) {
      $eloc = substr($file, 0, 5);
      $response = '';
      try {
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => 'http://151.236.217.176:5050/ElifeWebService/service/fetchMarkup/10.7554%2FeLife.' . $eloc . '/' . rawurlencode($section),
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
      }
      catch (Exception $e) {
        throw $e;
      }

      return $response;
    }

    /**
     * Compare two HTML fragments.
     */
    protected function assertEqualHtml($expected, $actual)
    {
        $from = ['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/> </s'];
        $to = ['>', '<', '\\1', '><'];
        $this->assertEquals(
            preg_replace($from, $to, $expected),
            preg_replace($from, $to, $actual)
        );
    }

    /**
     * Get inner HTML.
     */
    function getInnerHtml($node) { 
        $innerHTML= ''; 
        $children = $node->childNodes; 
        foreach ($children as $child) { 
            $innerHTML .= $child->ownerDocument->saveXML($child); 
        }

        return trim($innerHTML);
    } 
}
