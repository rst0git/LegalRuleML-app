<?php

namespace App\Http\Controllers;

use const App\LRML_NS;
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
    public static function get_session($readOnly = true)
    {
        $user = $readOnly ? 'readOnly' : 'createOnly';
        $password = env('BASEX_' . strtoupper($user) . '_PASSWORD');
        $host = env('BASEX_HOST', 'xmldb');
        $port = env('BASEX_PORT', '1984');
        try {
            // Connect with read only access
            $session = new Session($host, $port, $user, $password);
            // Open database
            $session->execute('open xmldb');
        } catch (\Exception $e) {
            BaseXController::initializer();
            $session = new Session($host, $port, 'readOnly', $password);
            $session->execute('open xmldb');
        }
        return $session;
    }

    // Store XML file to the BaseX database
    public static function upload_file($filename, $filepath)
    {
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
    public static function full_text_search(
        string $statement = '',
        string $text = '',
        bool $advanced = false,
        string $deonticOperator = ''
    ): array {
        $input = 'declare namespace lrml = "' . LRML_NS . '"; ';

        $use_bind_variable = ($text !== '') || !$advanced;

        if ($use_bind_variable) {
            // Declare variable used for text search
            $input .= 'declare variable $text external;  ';
        }

        $query = $advanced ? $text : '{$text}';

        $input .= 'for $i in ';
        if ($statement === '') {
            $input .= '//self::lrml:ConstitutiveStatement ';
            $input .= '| //self::lrml:FactualStatement ';
            $input .= '| //self::lrml:PenaltyStatement ';
            $input .= '| //self::lrml:PrescriptiveStatement ';
            $input .= '| //self::lrml:ReparationStatement ';
        } else {
            $input .= '//lrml:' . $statement . ' ';
        }
        if ($deonticOperator !== '' || $text !== '') {
            $input .= 'where $i';
            if ($deonticOperator !== '') {
                $input .= '//lrml:' . $deonticOperator;
            }
            if ($text !== '') {
                $input .= '//lrml:Paraphrase[text() contains text ' . $query . ' ]';
            }
            $input .= ' ';
        }


        $input .= 'let $doc := doc(concat(db:name($i), "/", db:path($i))) ';
        $input .= 'let $keyref := concat("#", normalize-space($i/@key)) ';
        /*
         * Finds relevant override-overridden and reparation-prescriptive relations within the document.
         * Note that LRMLToHTMLConverter has its own implementation for finding this relations, which is used when it
         * is passed a whole document.
         * TODO: Support inter-file relations (keyrefs that contain an IRI before the #)
         */
        $input .= 'let $reparations := $doc//lrml:ReparationStatement//lrml:toPrescriptiveStatement[normalize-space(@keyref)=$keyref]/../../@key ';
        $input .= 'let $overridden := $doc//lrml:Override[normalize-space(@under)=$keyref]/@over ';
        $input .= 'let $overriding := $doc//lrml:Override[normalize-space(@over)=$keyref]/@under ';
        $input .= 'return <result reparations="{$reparations}" path="{db:path($i)}" overridden="{$overridden}" overriding="{$overriding}">{$i}</result>';

        // Open Session
        $session = BaseXController::get_session();

        // Create query instance
        $query = $session->query($input);

        if ($use_bind_variable) {
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
            foreach (["overridden", "overriding", "reparations"] as $name) {
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
                "overriding" => $overriding,
                "reparations" => $reparations
            ];
        }

        return $results;
    }

    // Delete document from BaseX
    public static function delete_file($filename)
    {
        // Open new session
        $session = BaseXController::get_session(false);

        // Replace files with this name
        $session->execute('delete ' . $filename);

        // Close session
        $session->close();
    }

    // Initialise user management for BaseX
    // - Change admin default passoword
    // - Create readOnly
    // - Create createOnly
    public static function initializer()
    {
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
            $session->execute('alter password admin ' . $admin_password);
            $session->close();

        } catch (\Exception $e) {
            // Password has already been changed
        }

        // Create "readOnly" and "createOnly" accounts, set passwords
        // and permissions. Then create database.
        $session = new Session($host, $port, 'admin', $admin_password);
        $session->execute('create user readOnly');
        $session->execute('alter password readOnly ' . $readOnly_password);
        $session->execute('GRANT read TO readOnly');
        $session->execute('create user createOnly');
        $session->execute('alter password createOnly ' . $createOnly_password);
        $session->execute('GRANT write TO createOnly');
        $session->execute('create db xmldb');
        $session->close();

    }

}
