<?php

namespace App\Http\Controllers;

use App\LRMLToHTMLConverter;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Search Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles search requests and responces
    | from the BaseXController.
    |
    */

    const STATEMENT_KINDS = [
        '' => '(any statement type)',
        'ConstitutiveStatement' => 'constitutive',
        'FactualStatement' => 'factual',
        'PenaltyStatement' => 'penalty',
        'PrescriptiveStatement' => 'prescriptive',
        'ReparationStatement' => 'reparation'
    ];

    const OPERATOR_KINDS = [
        '' => '(any or no deontic operator)',
        'Obligation' => 'obligation',
        'Permission' => 'permission',
        'Prohibition' => 'prohibition'
    ];

    public function index(Request $request)
    {
        if (!$request->exists('statement')) {
            return view('search')->with('data', [
                'kinds' => self::STATEMENT_KINDS,
                'operator_kinds' => self::OPERATOR_KINDS,
                'no_search' => true
            ]);
        }

        $statement = $request->input('statement') ?? '';
        if (!isset(self::STATEMENT_KINDS[$statement])) {
            $statement = '';
        }
        $deonticOperator = $request->input('deontic_operator') ?? '';
        if (!isset(self::OPERATOR_KINDS[$deonticOperator])) {
            $deonticOperator = '';
        }

        $text = $request->input('search') ?? '';
        $advanced = isset($request->advanced);

        $XML_results = BaseXController::full_text_search($statement,
            $text,
            $advanced,
            $deonticOperator);
        if (!empty($XML_results['error'])) {
            return view('search')->with('data', [
                'search' => $text,
                'query_error_message' => $XML_results['error'],
                'kinds' => self::STATEMENT_KINDS,
                'operator_kinds' => self::OPERATOR_KINDS
            ]);
        }
        $HTML_results = [];
        foreach ($XML_results as $result) {
            $path = $result["path"];
            $url = route('doc_show', ['title' => $path]);
            $html = LRMLToHTMLConverter::XMLElementToHTML($result["lrml"], $url, $result["overriding"], $result["overridden"],
                $result["reparations"]);
            $HTML_results[] = [
                "name" => $path,
                "url" => $url,
                "html" => $html
            ];
        }

        $data = [
            'kinds' => self::STATEMENT_KINDS,
            'operator_kinds' => self::OPERATOR_KINDS,
            'query_results' => $HTML_results,
            'statement' => $statement,
            'deontic_operator' => $deonticOperator,
            'search' => $text
        ];

        return view('search')->with('data', $data);
    }
}
