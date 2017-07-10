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
    public static function get_session($readOnly = true) {
          $user = $readOnly ? 'readOnly' : 'createOnly';
          $password = env('BASEX_'.strtoupper($user).'_PASSWORD');
          $host = env('BASEX_HOST', 'xmldb');
          $port = env('BASEX_PORT', '1984');
          try{
            // Connect with read only access
            $session = new Session($host, $port, $user, $password);
            // Open database
            $session->execute('open xmldb');
          }
          catch(\Exception $e) {
            BaseXController::initializer();
            $session = new Session($host, $port, 'readOnly', $password);
            $session->execute('open xmldb');
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
      $session = BaseXController::get_session(false);

      // Replace files with this name
      $session->replace($filename, $XML_content);

      // Close session
      $session->close();
    }

    // Search within BaseX
    public static function full_text_search(string $statement,
                                            string $text,
                                            bool $advanced,
                                            string $deonticOperator = '',
                                            bool $ignoreSearchTerms = FALSE): array {
      // Namespace declaration in XQuery
      $input = 'declare namespace lrml = "http://docs.oasis-open.org/legalruleml/ns/v1.0/"; ';

      // Use bind variable to pass the user query only:
      // - if the user query is not ignored
      // - if the adnvanced search is NOT enebled
      $use_bind_variable = $ignoreSearchTerms || !$advanced;

      if($use_bind_variable){
        // Declare variable used for text search
        $input .= 'declare variable $text external;  ';
      }

      $query = $advanced ?  $text : '{$text}';

      // Search for all statements within "Statements" element
      if($statement == 'any')
      {
        $statement  = 'Statements//*[self::lrml:ConstitutiveStatement ';
        $statement .= 'or self::lrml:FactualStatement ';
        $statement .= 'or self::lrml:PenaltyStatement ';
        $statement .= 'or self::lrml:PrescriptiveStatement ';
        $statement .= 'or self::lrml:ReparationStatement] ';
      }

      // Full text search query

      if (!$ignoreSearchTerms) {
        // Use the user's query
          if ($deonticOperator === '') {
              // If deontic operator is not specified,
              // in the text of Paraphrase nodes under this statement.
              $input .= 'for $i in //lrml:'.$statement.' ';
              $input .= 'where $i//lrml:Paraphrase[text() contains text ' . $query . ' ] ';
          } else {
              // If deontic element is specified, get the child.
              // element "Paraphrase" and search in the text.
              $input .= 'for $i in //lrml:'.$statement.'//lrml:' . $deonticOperator.' ';
              $input .= 'where $i/lrml:Paraphrase[text() contains text ' . $query . ' ] ';
          }
      } elseif ($deonticOperator !== '') {
          // Do not use the user's search query.
          // Filter the results by $statement and $deonticOperator.
          $input .= 'for $i in //lrml:'.$statement.'//lrml:' . $deonticOperator . ' ';
      }
      else {
          // Do not use the user's search query
          // Filter the results only by $statement.
          $input .= 'for $i in //lrml:'.$statement.' ';
      }

      if ($deonticOperator === ''){
        // The flor query is search for statement nodes
        $input .= 'let $statementNode := $i ';
      }
      else {
        // The $i element is the paraphrase of a node to
        // get the statement node get the first ancestor
        // with @key attribute.
        $input .= 'let $statementNode := $i/ancestor::*[@key][1] ';
      }

      $input .= 'let $doc := doc(concat(db:name($i), "/", db:path($i))) ';
      $input .= 'let $keyref := concat("#", normalize-space($statementNode/@key)) ';
      $input .= 'let $overridden := $doc//lrml:Override[normalize-space(@under)=$keyref]/@over ';
      $input .= 'let $overriding := $doc//lrml:Override[normalize-space(@over)=$keyref]/@under ';
      $input .= 'return <result path="{db:path($i)}" overridden="{$overridden}" overriding="{$overriding}">{$statementNode}</result>';

      // Open Session
      $session = BaseXController::get_session();

      // Create query instance
      $query = $session->query($input);

      if($use_bind_variable){
        // Bind query variable - Send the user string to BaseX
        $query->bind('text', $text);
      }
      // Get query results
      try {
        $XMLresults = [];
        while ($query->more()) {
          $XMLresults[] = $query->next();
        }
      } catch (\Exception $e) {
        // Return error message if the query has faild
          return ['error' => $e->getMessage()];
      }

      // Close query instance
      $query->close();
      $session->close();

      $results = [];
      foreach ($XMLresults as $xml) {
        $xmlDocument = new \DOMDocument;
        $xmlDocument->loadXML($xml);
        $result = $xmlDocument->documentElement;
        $lrml = $result->firstChild;
        if ($lrml instanceof \DOMText) {
            $lrml = $lrml->nextSibling;
        }
        // Transform XQuery overridden/overrides strings into neat arrays
        // of the format [$mainStatementKey => [$relatedStatementKey1, ...]]
        foreach (["overridden", "overriding"] as $name) {
            $string = $result->getAttribute($name);
            $$name = [];
            if ($string !== '') {
                $key = $lrml->getAttribute("key");
                $keys = \array_map(function (string $key): string {
                    return \trim(\ltrim($key, "#"));
                // split by whitespace, except if at the start or end of string
                }, \preg_split('/(?!^)\s+(?!$)/', $string));
                $$name[$key] = $keys;
            }
        }
        $results[] = [
          "path" => $result->getAttribute("path"),
          "lrml" => $lrml,
          "overridden" => $overridden,
          "overriding" => $overriding
        ];
      }

      return $results;
    }

    // Delete document from BaseX
    public static function delete_file($filename) {
      // Open new session
      $session = BaseXController::get_session(false);

      // Replace files with this name
      $session->execute('delete '.$filename);

      // Close session
      $session->close();
    }

    // Initialise user management for BaseX
    // - Change admin default passoword
    // - Create readOnly
    // - Create createOnly
    public static function initializer() {
      // Get environment variables
      $admin_password = env('BASEX_ADMIN_PASSWORD');
      $readOnly_password = env('BASEX_READONLY_PASSWORD');
      $createOnly_password = env('BASEX_CREATEONLY_PASSWORD');
      $host = env('BASEX_HOST', 'xmldb');
      $port = env('BASEX_PORT', '1984');

      // Default BaseX credentials
      $default_admin_user = 'admin';
      $default_admin_password = 'admin';

      try {

        // Change the default admin password
        $session = new Session($host, $port, $default_admin_user, $default_admin_password);
        $session->execute('alter password admin '.$admin_password);
        $session->close();

      } catch (\Exception $e) {
        // Password has already been changed
      }

      // Create "readOnly" and "createOnly" accounts, set passwords
      // and permissions. Then create database.
      $session = new Session($host, $port, 'admin', $admin_password);
      $session->execute('create user readOnly');
      $session->execute('alter password readOnly '.$readOnly_password);
      $session->execute('GRANT read TO readOnly');
      $session->execute('create user createOnly');
      $session->execute('alter password createOnly '.$createOnly_password);
      $session->execute('GRANT write TO createOnly');
      $session->execute('create db xmldb');
      $session->close();

    }

}
