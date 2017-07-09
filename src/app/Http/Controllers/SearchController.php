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

    public function index() {
          return view('search');
    }

    public function search(Request $request){

      // Validate request
      $this->validate($request, [
        'statement' => 'required',
        'search' => 'required',
      ]);

      $statementMap = [
        'constutative' => 'ConstitutiveStatement',
        'permission' => 'Permission',
        'prohibition' => 'Prohibition',
      ];
      $statement = $statementMap[$request->input('statement')];

      $text = $request->input('search');

      $XQuery_result = BaseXController::full_text_search($statement, $text);

      if(strlen($XQuery_result) > 1){
        $html_result = Converter::xml_to_html('<div>'.$XQuery_result.'</div>', false);
      }
      else {
        $html_result = "";
      }

      $data = [
        'query_result' => $html_result,
        'statement' => $statement,
        'search' => $text
      ];

      return view('search')->with('data', $data);
    }
}
