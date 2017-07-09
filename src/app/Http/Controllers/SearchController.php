<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

    public function index() {
        return view('search')->with('data', [
            'kinds' => self::STATEMENT_KINDS
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

      $text = $request->input('search');

      $XQuery_result = BaseXController::full_text_search($statement, $text);

      $html_result = Converter::xml_to_html('<div>'.$XQuery_result.'</div>', false);

      $data = [
        'kinds' => self::STATEMENT_KINDS,
        'query_result' => $html_result,
        'statement' => $statement,
        'search' => $text
      ];

      return view('search')->with('data', $data);
    }
}
