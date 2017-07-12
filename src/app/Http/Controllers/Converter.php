<?php

namespace App\Http\Controllers;

class Converter extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LegalRuleML-to-HTML converter
    |--------------------------------------------------------------------------
    |
    | This file is responsible for converting LegalRuleML XML to
    | valid HTML5 content.
    |
    | Created by Andrea Faulds (@hikari-no-yume)
    |
    */

    const LRML_NS = "http://docs.oasis-open.org/legalruleml/ns/v1.0/";

    const ATTRIBUTE_MAP = [
        "key" => "id",
        "over" => "data-over",
        "under" => "data-under"
    ];

    const ELEMENT_MAP = [
        "Paraphrase" => "span"
    ];

    const OMITTED_ELEMENTS = [
        "Rule" => TRUE,
        "then" => TRUE,
        "OverrideStatement" => TRUE,
        "Override" => TRUE
    ];

    const SPECIAL_ELEMENTS = [
        "ConstitutiveStatement" => "statement_handler",
        "FactualStatement" => "statement_handler",
        "PenaltyStatement" => "statement_handler",
        "PrescriptiveStatement" => "statement_handler",
        "ReparationStatement" => "statement_handler",
        "ConstitutiveStatement" => "statement_handler",
    ];

    private $htmlDoc;
    private $url;
    private $overridden;
    private $overriding;

    private function __construct(string $url = "") {
        $this->htmlDoc = new \DOMDocument;
        $this->url = $url;
        $this->overridden = [];
        $this->overriding = [];
        $this->reparations = [];;
    }

    public function collectOverrides(\DOMDocument $xmlDoc) {
        $overrides = $xmlDoc->getElementsByTagNameNS(self::LRML_NS, "Override");
        foreach ($overrides as $override) {
            $over = \trim(\ltrim($override->getAttribute("over"), "#"));
            $under = \trim(\ltrim($override->getAttribute("under"), "#"));
            $this->overridden[$under][] = $over;
            $this->overriding[$over][] = $under;
        }
    }

    public function statement_handler(\DOMNode $xml, \DOMNode $html) {
        $key = $xml->getAttribute("key");
        $html->insertBefore(self::makeKeyElement($key), $html->firstChild);
        if (isset($this->overridden[$key]) || isset($this->overriding[$key])) {
            $overrides = $this->htmlDoc->createElement('div');
            $overrides->setAttribute('class', 'overrides');
            foreach ([
                "Overriden by: " => $this->overridden[$key] ?? NULL,
                "Overrides: " => $this->overriding[$key] ?? NULL
            ] as $label => $list) {
                if ($list !== NULL) {
                    $overrides->appendChild($this->htmlDoc->createTextNode($label));
                    $overrides->appendChild($this->makeKeyList($list));
                }
            }
            $html->appendChild($overrides);
        }
        if (isset($this->reparations[$key])) {
            $reparations = $this->htmlDoc->createElement('div');
            $reparations->setAttribute('class', 'reparations');
            $label = "Has reparation: ";
            $list = $this->reparations[$key];
            $reparations->appendChild($this->htmlDoc->createTextNode($label));
            $reparations->appendChild($this->makeKeyList($list));
            $html->appendChild($reparations);
        }
    }

    private function makeKeyList(array /*<string>*/ $keys): \DOMElement {
        $ul = $this->htmlDoc->createElement('ul');
        $ul->setAttribute('class', 'key-list');
        foreach ($keys as $item) {
            $li = $this->htmlDoc->createElement('li');
            $key = $this->makeKeyElement($item);
            $li->appendChild($key);
            $ul->appendChild($li);
        }
        return $ul;
    }

    private function makeKeyElement(string $key): \DOMElement {
        $keyElement = $this->htmlDoc->createElement("a");
        $keyElement->setAttribute("href", "$this->url#$key");
        $keyElement->setAttribute("class", "key");
        $keyElement->textContent = "[#$key]";
        return $keyElement;
    }

    public function stripNS(string $xmlTag): string {
        // Remove namespace from tag name, if necessary
        $colonPos = strpos($xmlTag, ":");
        if (FALSE !== $colonPos) {
            $xmlTag = substr($xmlTag, $colonPos + 1);
        }
        return $xmlTag;
    }

    public function childrenToHTML(\DOMNode $xml, \DOMNode $html) {
        foreach ($xml->childNodes as $xmlChild) {
            // Replace XML elements with their children where specified

            if ($xmlChild instanceof \DOMElement && isset(self::OMITTED_ELEMENTS[self::stripNS($xmlChild->tagName)])) {
                self::childrenToHTML($xmlChild, $html);
            } else {
                $html->appendChild(self::toHTML($xmlChild));
            }
        }
    }

    public function toHTML(\DOMNode $xml) {
      if ($xml instanceof \DOMElement) {
          $xmlTag = self::stripNS($xml->tagName);

          // Map elements to <div>s if we haven't specified otherwise
          $htmlTag = self::ELEMENT_MAP[$xmlTag] ?? "div";

          $html = $this->htmlDoc->createElement($htmlTag);
          $html->setAttribute("class", $xmlTag);

          // Map XML attributes to appropriate HTML attributes
          foreach (self::ATTRIBUTE_MAP as $xmlAttrib => $htmlAttrib) {
              if ($xml->hasAttribute($xmlAttrib)) {
                  $html->setAttribute($htmlAttrib, $xml->getAttribute($xmlAttrib));
              }
          }

          self::childrenToHTML($xml, $html);

          // Use overriding handler function for element content, if any
          if (isset(self::SPECIAL_ELEMENTS[$xmlTag])) {
              $this->{self::SPECIAL_ELEMENTS[$xmlTag]}($xml, $html);
          }
          return $html;
      } else if ($xml instanceof \DOMText) {
          return $this->htmlDoc->createTextNode($xml->wholeText);
      } else if ($xml instanceof \DOMComment) {
          return $this->htmlDoc->createComment($xml->data);
      } else {
          die("Unhandled DOMNode kind: " . get_class($xml) . PHP_EOL);
      }
    }

    public static function xml_to_html($infileContent, $is_file=true): string {
      $doc = new \DOMDocument();

      if ($is_file == true) {
        $doc->load($infileContent);
      }
      else {
        $doc->loadXML($infileContent);
      }


      $convertor = new self;
      $convertor->collectOverrides($doc);
      $html = $convertor->toHTML($doc->documentElement);
      return $convertor->htmlDoc->saveHTML($html);
    }

    public static function DOM_to_html(\DOMElement $xml, string $url = "", array $overriding = [], array $overridden = [], array $reparations = []): string {
      $convertor = new self($url);
      $convertor->overriding = $overriding;
      $convertor->overridden = $overridden;
      $convertor->reparations = $reparations;
      $html = $convertor->toHTML($xml);
      return $convertor->htmlDoc->saveHTML($html);
    }
}
