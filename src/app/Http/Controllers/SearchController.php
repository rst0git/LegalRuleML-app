<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

Use App\Document;
Use App\Http\Controllers\BaseXController;

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
        'Statements' => 'any',
        'ConstitutiveStatement' => 'constitutive',
        'FactualStatement' => 'factual',
        'PenaltyStatement' => 'penalty',
        'PrescriptiveStatement' => 'prescriptive',
        'ReparationStatement' => 'reparation'
    ];

    const OPERATOR_KINDS = [
        '' => '(any deontic operator)',
        'Obligation' => 'obligation',
        'Permission' => 'permission',
        'Prohibition' => 'prohibition'
    ];

    public function index(Request $request) {
        if ($request->input('statement') === NULL) {
            return view('search')->with('data', [
                'kinds' => self::STATEMENT_KINDS,
                'operator_kinds' => self::OPERATOR_KINDS,
                'all' => FALSE
            ]);
        }

        $statement = $request->input('statement');
        if (!isset(self::STATEMENT_KINDS[$statement])) {
            return response('', 400);
        }
        $deonticOperator = $request->input('deontic_operator') ?? '';
        if (!isset(self::OPERATOR_KINDS[$deonticOperator])) {
            return response('', 400);
        }

        $text = $request->input('search') ?? '';
        $advanced = isset($request->advanced);
        $all = ($request->input('all') ?? "no") === "yes";

        $XML_results = BaseXController::full_text_search($statement,
                                                         $text,
                                                         $advanced,
                                                         $deonticOperator,
                                                         $all);
        if(!empty($XML_results['error'])) {
            return view('search')->with('data', [
                'search' => $text,
                'all' => $all,
                'query_error_message' => $XML_results['error'],
                'kinds' => self::STATEMENT_KINDS,
                'operator_kinds' => self::OPERATOR_KINDS
            ]);
        }
        $HTML_results = [];
        foreach ($XML_results as $result) {
            $path = $result["path"];
            $url = route('doc_show', ['title' => $path]);
            $html = Converter::DOM_to_html($result["lrml"], $url, $result["overriding"], $result["overridden"]);
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
            'search' => $text,
            'all' => $all
        ];

        return view('search')->with('data', $data);
    }
}
