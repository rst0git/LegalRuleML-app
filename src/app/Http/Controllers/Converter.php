<?php

namespace App\Http\Controllers;

class Converter extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This file is responsible for converting valid XML content to
    | valid HTML5 content. It preserves the element names as class
    | attribute and appends "data-" infront of attribute names.
    |
    | Created by Andrea (@hikari-no-yume)
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
        // "ConstitutiveStatement" => "statement_handler",
        // "FactualStatement" => "statement_handler",
        // "PenaltyStatement" => "statement_handler",
        // "PrescriptiveStatement" => "statement_handler",
        // "ReparationStatement" => "statement_handler",
        // "ConstitutiveStatement" => "statement_handler",
        // "Statements" => "statements_handler",
    ];

    public static function override_statement_handler(\DOMNode $xml, \DOMNode $html, &$htmlDoc) {
            $over = $xml->getAttribute("over");
            $under = $xml->getAttribute("under");
            $html->textContent = "Override over:" . $over . " under:" . $under;
    }
    //
    // public static function statement_handler(\DOMNode $xml, \DOMNode $html, &$htmlDoc) {
    //
    //     $key = $xml->getAttribute("key");
    //     $html->insertBefore($htmlDoc->createTextNode("[#$key] "), $html->childNodes[0]);
    // }

    // public static function statements_handler(\DOMNode $xml, \DOMNode $html, &$htmlDoc) {
    //
    //     $html->insertBefore($htmlDoc->createElement("hr"), $html->childNodes[0]);
    //     $html->appendChild($htmlDoc->createElement("hr"));
    // }

    public static function stripNS(string $xmlTag): string {
        // Remove namespace from tag name, if necessary
        $colonPos = strpos($xmlTag, ":");
        if (FALSE !== $colonPos) {
            $xmlTag = substr($xmlTag, $colonPos + 1);
        }
        return $xmlTag;
    }

    public static function childrenToHTML(\DOMNode $xml, \DOMNode $html, &$htmlDoc) {
        foreach ($xml->childNodes as $xmlChild) {
            // Replace XML elements with their children where specified

            if ($xmlChild instanceof DOMElement && isset(self::OMITTED_ELEMENTS[self::stripNS($xmlChild->tagName)])) {
                self::childrenToHTML($xmlChild, $html, $htmlDoc);
            } else {
                $html->appendChild(self::toHTML($xmlChild, $htmlDoc));
            }
        }
    }

    public static function toHTML(\DOMNode $xml, &$htmlDoc) {
      if ($xml instanceof \DOMElement) {
          $xmlTag = self::stripNS($xml->tagName);

          // Map elements to <div>s if we haven't specified otherwise
          $htmlTag = self::ELEMENT_MAP[$xmlTag] ?? "div";

          $html = $htmlDoc->createElement($htmlTag);
          $html->setAttribute("class", $xmlTag);

          // Map XML attributes to appropriate HTML attributes
          foreach (self::ATTRIBUTE_MAP as $xmlAttrib => $htmlAttrib) {
              if ($xml->hasAttribute($xmlAttrib)) {
                  $html->setAttribute($htmlAttrib, $xml->getAttribute($xmlAttrib));
              }
          }

          self::childrenToHTML($xml, $html, $htmlDoc);

          // Use overriding handler function for element content, if any
          if (isset(self::SPECIAL_ELEMENTS[$xmlTag])) {
              self::{self::SPECIAL_ELEMENTS[$xmlTag]}($xml, $html, $htmlDoc);
          }
          return $html;
      } else if ($xml instanceof \DOMText) {
          return $htmlDoc->createTextNode($xml->wholeText);
      } else if ($xml instanceof \DOMComment) {
          return $htmlDoc->createComment($xml->data);
      } else {
          die("Unhandled DOMNode kind: " . get_class($xml) . PHP_EOL);
      }
    }

    public static function xml_to_html($infileContent, $is_file=true){
      $doc = new \DOMDocument();

      if ($is_file == true) {
        $doc->load($infileContent);
      }
      else {
        $doc->loadXML($infileContent);
      }


      $htmlDoc = new \DOMDocument();
      $html = self::toHTML($doc->documentElement, $htmlDoc);
      return $htmlDoc->saveHTML($html);

    }
}
