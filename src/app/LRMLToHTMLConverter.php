<?php

namespace App;

use App\Http\Controllers\Controller;

class LRMLToHTMLConverter extends Controller
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
        "Rule" => true,
        "then" => true,
        "OverrideStatement" => true,
        "Override" => true
    ];

    const SPECIAL_ELEMENTS = [
        "ConstitutiveStatement" => "statement_handler",
        "FactualStatement" => "statement_handler",
        "PenaltyStatement" => "statement_handler",
        "PrescriptiveStatement" => "statement_handler",
        "ReparationStatement" => "statement_handler",
        "ConstitutiveStatement" => "statement_handler",
    ];

    /**
     * @var \DOMDocument The HTML document being constructed
     */
    private $htmlDoc;
    /**
     * @var string The URL of the document these elements are found in, used to generate keyrefs ("" if current page)
     */
    private $url;
    /**
     * @var array<array<string>> A map of keys of overridden statements to arrays of keys of overriding statements
     */
    private $overridden;
    /**
     * @var array<array<string>> A map of keys of overriding statements to arrays of keys of overridden statements
     */
    private $overriding;
    /**
     * @var array<array<string>> A map of keys of reparation statements to arrays of keys of applicable statements
     */
    private $reparations;

    private function __construct(string $url = "")
    {
        $this->htmlDoc = new \DOMDocument;
        $this->url = $url;
        $this->overridden = [];
        $this->overriding = [];
        $this->reparations = [];
    }

    public function collectRelations(\DOMDocument $xmlDoc)
    {
        $overrides = $xmlDoc->getElementsByTagNameNS(self::LRML_NS, "Override");
        foreach ($overrides as $override) {
            $over = \trim(\ltrim($override->getAttribute("over"), "#"));
            $under = \trim(\ltrim($override->getAttribute("under"), "#"));
            $this->overridden[$under][] = $over;
            $this->overriding[$over][] = $under;
        }
        $applications = $xmlDoc->getElementsByTagNameNS(self::LRML_NS, "toPrescriptiveStatement");
        foreach ($applications as $application) {
            $prescriptiveKey = \trim(\ltrim($application->getAttribute("keyref"), "#"));
            $reparation = $application->parentNode->parentNode ?? null;
            if ($reparation !== null && $reparation instanceof \DOMElement && $this->stripNS($reparation->tagName) === "ReparationStatement") {
                $reparationKey = $reparation->getAttribute("key");
                $this->reparations[$prescriptiveKey][] = $reparationKey;
            }
        }
    }

    public function statement_handler(\DOMNode $xml, \DOMNode $html)
    {
        $key = $xml->getAttribute("key");
        $html->insertBefore(self::makeKeyElement($key), $html->firstChild);
        if (isset($this->overridden[$key]) || isset($this->overriding[$key])) {
            $overrides = $this->htmlDoc->createElement('div');
            $overrides->setAttribute('class', 'overrides');
            foreach ([
                         "Overridden by: " => $this->overridden[$key] ?? null,
                         "Overrides: " => $this->overriding[$key] ?? null
                     ] as $label => $list) {
                if ($list !== null) {
                    $overrides->appendChild($this->htmlDoc->createTextNode($label));
                    $overrides->appendChild($this->makeKeyList($list));
                }
            }
            $html->appendChild($overrides);
        }
        if (isset($this->reparations[$key])) {
            $reparations = $this->htmlDoc->createElement('div');
            $reparations->setAttribute('class', 'reparations');
            if (isset($this->reparations[$key])) {
                $reparations->appendChild($this->htmlDoc->createTextNode("Has reparation: "));
                $reparations->appendChild($this->makeKeyList($this->reparations[$key]));
                $html->appendChild($reparations);
            }
        }
        if ($this->stripNS($xml->tagName) === 'ReparationStatement') {
            $xpath = new \DOMXPath($xml->ownerDocument);
            $nodes = $xpath->evaluate("lrml:Reparation/lrml:toPrescriptiveStatement/@keyref", $xml);
            if ($nodes->length === 1) {
                $appliesTo = $this->htmlDoc->createElement('div');
                $appliesTo->setAttribute('class', 'applies-to');
                $key = \trim(\ltrim($nodes[0]->value, "#"));
                $appliesTo->appendChild($this->htmlDoc->createTextNode("Applies to: "));
                $appliesTo->appendChild($this->makeKeyElement($key));
                $html->appendChild($appliesTo);
            }
        }
    }

    private function makeKeyList(
        array /*<string>*/
        $keys
    ): \DOMElement {
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

    private function makeKeyElement(string $key): \DOMElement
    {
        $keyElement = $this->htmlDoc->createElement("a");
        $keyElement->setAttribute("href", "$this->url#$key");
        $keyElement->setAttribute("class", "key");
        $keyElement->textContent = "[#$key]";
        return $keyElement;
    }

    public function stripNS(string $xmlTag): string
    {
        // Remove namespace from tag name, if necessary
        $colonPos = strpos($xmlTag, ":");
        if (false !== $colonPos) {
            $xmlTag = substr($xmlTag, $colonPos + 1);
        }
        return $xmlTag;
    }

    public function childrenToHTML(\DOMNode $xml, \DOMNode $html)
    {
        foreach ($xml->childNodes as $xmlChild) {
            // Replace XML elements with their children where specified

            if ($xmlChild instanceof \DOMElement && isset(self::OMITTED_ELEMENTS[self::stripNS($xmlChild->tagName)])) {
                self::childrenToHTML($xmlChild, $html);
            } else {
                $html->appendChild(self::toHTML($xmlChild));
            }
        }
    }

    public function toHTML(\DOMNode $xml)
    {
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
        } else {
            if ($xml instanceof \DOMText) {
                return $this->htmlDoc->createTextNode($xml->wholeText);
            } else {
                if ($xml instanceof \DOMComment) {
                    return $this->htmlDoc->createComment($xml->data);
                } else {
                    die("Unhandled DOMNode kind: " . get_class($xml) . PHP_EOL);
                }
            }
        }
    }

    public static function XMLFileToHTML(string $filename): string
    {
        $doc = new \DOMDocument();
        $doc->load($filename);

        $convertor = new self;
        $convertor->collectRelations($doc);
        $html = $convertor->toHTML($doc->documentElement);
        return $convertor->htmlDoc->saveHTML($html);
    }

    public static function XMLElementToHTML(
        \DOMElement $xml,
        string $url = "",
        array $overriding = [],
        array $overridden = [],
        array $reparations = []
    ): string {
        $convertor = new self($url);
        $convertor->overriding = $overriding;
        $convertor->overridden = $overridden;
        $convertor->reparations = $reparations;
        $html = $convertor->toHTML($xml);
        return $convertor->htmlDoc->saveHTML($html);
    }
}
