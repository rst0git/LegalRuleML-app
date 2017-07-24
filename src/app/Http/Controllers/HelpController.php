<?php

namespace App\Http\Controllers;

use League\CommonMark\CommonMarkConverter;

class HelpController extends Controller
{
    const HELP_DIR = __DIR__ . '/../../../resources/help/';

    /**
     * Show the help page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $markdown = \file_get_contents(self::HELP_DIR . "/usersguide.md");
        $converter = new CommonMarkConverter;
        $html = $converter->convertToHtml($markdown);
        // This is a horrible kludge so the markdown file can use images in its local directory, and thus be viewable
        // outside of the application, yet also be viewable in the app.
        $html = \preg_replace_callback('/<img src="([^"]*)"/i', function (array $matches): string {
            $imageFilename = $matches[1];
            $imageContent = \file_get_contents(self::HELP_DIR . $imageFilename);
            $dataURI = 'data:;base64,' . \base64_encode($imageContent);
            return '<img src="' . $dataURI . '"';
        }, $html);
        return view('help')->with('data', [
            "html" => $html
        ]);
    }
}
