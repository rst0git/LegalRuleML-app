<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// PagesController is used only for the Home Page
class PagesController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Pages Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles requests to the Home Page.
    |
    */

    public function index() {
        return view('index');
    }
}
