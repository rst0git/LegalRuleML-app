<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

include('BaseXClient.php');

class BaseXController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BaseX Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for the interaction with BaseX
    | database. It uses the BaseXClient to open interactive sessions
    | and execute queries.
    |
    */

    // Return BaseX session instance
    public static function get_session() {

          $user = config('basex.username', 'admin');
          $password = config('basex.password', 'admin');
          $host = env('BASEX_HOST', 'xmldb');
          $port = env('BASEX_PORT', '1984');
          $session = new Session($host, $port, $user, $password);
          try{
            // Open database
            $session->execute('open xmldb');
          }
          catch(\Exception $e) {
            // Create database open has failed
            $session->execute('create db xmldb');
          }
          return $session;
    }

    // Store XML file to the BaseX database
    public static function upload_file($filename, $filepath) {
      // Load the file content into new DOM element
      $doc = new \DOMDocument();
      $doc->load($filepath);

      // Extract the XML content as string
      $XML_content = $doc->saveXML();

      // Open new session
      $session = BaseXController::get_session();

      // Replace files with this name
      $session->replace($filename, $XML_content);

      // Close session
      $session->close();
    }

    // Search within BaseX
    public static function full_text_search($statement, $text): array {
      // Namespace declaration in XQuery
      $input = 'declare namespace lrml = "http://docs.oasis-open.org/legalruleml/ns/v1.0/"; ';
      // Declare variable used for text search
      $input .= 'declare variable $text external;  ';

      // Full text search query
      $input .= 'for $i in //lrml:'.$statement.' ';
      $input .= 'where $i//lrml:Paraphrase[text() contains text {$text} ] ';
      $input .= 'return <result path="{db:path($i)}">{$i}</result>';

      // Open Session
      $session = BaseXController::get_session();

      // Create query instance
      $query = $session->query($input);

      // Bind query variable - Send the user string to BaseX
      $query->bind('text', $text);

      // Get query results
      $XMLresults = [];
      while ($query->more()) {
        $XMLresults[] = $query->next();
      }

      // Close query instance
      $query->close();

      $results = [];
      foreach ($XMLresults as $xml) {
        $xmlDocument = new \DOMDocument;
        $xmlDocument->loadXML($xml);
        $result = $xmlDocument->documentElement;
        $lrml = $result->firstChild;
        if ($lrml instanceof \DOMText) {
            $lrml = $lrml->nextSibling;
        }
        $results[] = [
          "path" => $result->getAttribute("path"),
          "lrml" => $lrml
        ];
      }

      return $results;
    }

    // Delete document from BaseX
    public static function delete_file($filename) {
      // Open new session
      $session = BaseXController::get_session();

      // Replace files with this name
      $session->execute('delete '.$filename);

      // Close session
      $session->close();
    }

}
