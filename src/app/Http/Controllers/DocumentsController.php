<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Input;
use Validator;
use Illuminate\Http\Request;

use App\Document;
use App\LRMLToHTMLConverter;
use App\Http\Controllers\BaseXController;

class DocumentsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Document Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible Upload, Delete, Show and
    | Download of documents. The shown document is HTML version of
    | the original uploaded XML file. Upload/Destroy is performed
    | for both the Relational Database (PostreSQL) and XML DB (BaseX).
    |
    */

    // Show all uploaded documents
    public function index()
    {
        $docs = Document::orderBy('created_at', 'desc')->paginate(7);
        $data = [
            'num_doc' => Document::count(),
            'num_doc_shown' => count($docs),
            'docs' => $docs
        ];
        return view('documents.index')->with('data', $data);
    }

    // Show the view for uploading files
    public function upload()
    {
        return view('documents.upload');
    }

    // Store uploaded document
    public function store(Request $request)
    {

        // Getting all of the post data
        $files = Input::file('files');

        // Show error if no files are submitted
        if (is_null($files)) {
            return redirect(route('doc_upload'))->withInput()->withErrors("Please select file!");
        }

        // Making counting of uploaded images
        $file_count = count($files);
        // Get current user ID
        $user_id = $request->user()->id;
        // start count how many uploaded
        $uploadcount = 0;
        foreach ($files as $file) {

            $rules = array('file' => 'required|mimes:xml');
            $validator = Validator::make(array('file' => $file), $rules);

            if ($validator->passes()) {
                $destinationPath = 'uploads';
                $filename = $file->getClientOriginalName();
                $upload_success = $file->move($destinationPath, $filename);
                $uploadcount++;

                $filepath = $destinationPath . DIRECTORY_SEPARATOR . $filename;

                // Convert XML to HTML
                $to_html = LRMLToHTMLConverter::XMLFileToHTML($filepath);
                if ($to_html) {
                    //Upload XML content to BaseX
                    BaseXController::upload_file($filename, $filepath);

                    // Create DB record
                    $new_doc = Document::firstOrNew(['title' => $filename]);
                    $new_doc->filename = $filename;
                    $new_doc->html = $to_html;
                    $new_doc->user_id = $user_id;
                    $new_doc->save();
                } else {
                    return redirect(route('doc'))->with('error',
                        'File "' . $filename . '" could not be converted to HTML.');
                }
            }
        }
        if ($uploadcount == $file_count) {
            return redirect(route('doc'))->with('success', 'Upload successfully');
        } else {
            return redirect(route('doc_upload'))->withInput()->withErrors($validator);
        }

    }

    // Show HTML version of LegalRuleML document
    public function show($title)
    {
        $doc = Document::where('title', $title)->first();
        if (is_null($doc)) {
            return redirect(route('doc'))->with('error', 'Document could not be found.');
        }
        $data = [
            'doc' => $doc,
            'html' => ''
        ];
        if (config('app.debug')) {
            $filepath = 'uploads' . DIRECTORY_SEPARATOR . $doc->filename;
            $data['html'] = LRMLToHTMLConverter::XMLFileToHTML($filepath);
        }
        return view('documents.show')->with('data', $data);
    }

    // Download XML file
    public function download($id)
    {
        $doc = Document::find($id);
        if (is_null($doc)) {
            return redirect(route('doc'))->with('error', 'Document could not be found.');
        }

        $pathToFile = public_path() . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $doc->filename;
        $headers = array('Content-Type: application/octet-stream');

        return response()->download($pathToFile, $doc->filename, $headers);
    }

    // Delete record of uploaded document from the database
    public function destroy($id)
    {
        // Get DB record
        $doc = Document::find($id);

        // Show error message if record does not exist
        if (is_null($doc)) {
            return redirect(route('doc'))->with('error', 'Document could not be found.');
        }

        // Delete from BaseX
        BaseXController::delete_file($doc->filename);

        // Delete record
        $doc->delete();

        return redirect(route('doc'))->with('success', 'Document deleted.');
    }
}
