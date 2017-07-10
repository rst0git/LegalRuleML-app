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
        'ConstitutiveStatement' => 'constitutive',
        'FactualStatement' => 'factual',
        'PenaltyStatement' => 'penalty',
        'PrescriptiveStatement' => 'prescriptive',
        'ReparationStatement' => 'reparation'
    ];

    const OPERATOR_KINDS = [
        '' => '(no deontic operator)',
        'Obligation' => 'obligation',
        'Permission' => 'permission',
        'Prohibition' => 'prohibition'
    ];

    public function index() {
        return view('search')->with('data', [
            'kinds' => self::STATEMENT_KINDS,
            'operator_kinds' => self::OPERATOR_KINDS
        ]);
    }

    public function search(Request $request){

      // Validate request
      $this->validate($request, [
        'statement' => 'required',
        'search' => 'required',
      ]);

      $statement = $request->input('statement');
      if (!isset(self::STATEMENT_KINDS[$statement])) {
        return response('', 400);
      }
      $deonticOperator = $request->input('deontic_operator') ?? '';
      if (!isset(self::OPERATOR_KINDS[$deonticOperator])) {
        return response('', 400);
      }

      $text = $request->input('search');

      $XML_results = BaseXController::full_text_search($statement, $text, $deonticOperator);
      $HTML_results = [];
      foreach ($XML_results as $result) {
        $path = $result["path"];
        $url = route('doc_show', ['title' => $path]);
        $html = Converter::DOM_to_html($result["lrml"], $url);
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
