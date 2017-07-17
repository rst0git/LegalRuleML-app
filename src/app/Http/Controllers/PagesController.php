<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (Auth::check()) {
            return redirect(route('dashboard'));
        }
        return view('index');
    }
}
