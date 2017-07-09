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
        "then" => TRUE
    ];

    const SPECIAL_ELEMENTS = [
        "Override" => "override_statement_handler",
        "ConstitutiveStatement" => "statement_handler",
        "FactualStatement" => "statement_handler",
        "PenaltyStatement" => "statement_handler",
        "PrescriptiveStatement" => "statement_handler",
        "ReparationStatement" => "statement_handler",
        "ConstitutiveStatement" => "statement_handler",
    ];

    private $htmlDoc;
    private $url;

    private function __construct(string $url = "") {
        $this->htmlDoc = new \DOMDocument;
        $this->url = $url;
    }

    public function override_statement_handler(\DOMNode $xml, \DOMNode $html) {
        $over = \ltrim($xml->getAttribute("over"), "#");
        $under = \ltrim($xml->getAttribute("under"), "#");
        $html->appendChild($this->htmlDoc->createTextNode("Override over:"));
        $html->appendChild(self::makeKeyElement($over));
        $html->appendChild($this->htmlDoc->createTextNode(" under:"));
        $html->appendChild(self::makeKeyElement($under));
    }
    public function statement_handler(\DOMNode $xml, \DOMNode $html) {
        $key = $xml->getAttribute("key");
        $html->insertBefore(self::makeKeyElement($key), $html->firstChild);
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

            if ($xmlChild instanceof DOMElement && isset(self::OMITTED_ELEMENTS[self::stripNS($xmlChild->tagName)])) {
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
      $html = $convertor->toHTML($doc->documentElement);
      return $convertor->htmlDoc->saveHTML($html);
    }

    public static function DOM_to_html(\DOMElement $xml, string $url = ""): string {
      $convertor = new self($url);
      $html = $convertor->toHTML($xml);
      return $convertor->htmlDoc->saveHTML($html);
    }
}
