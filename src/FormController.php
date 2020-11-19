<?php

namespace NickDeKruijk\LaravelForms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Storage;

class FormController extends Controller
{
    public function post(Request $request, string $id)
    {
        // Get form from session
        $form = session(config('forms.session_prefix') . $id);

        // Walk thru all values
        foreach ($request->all() as $name => $value) {
            if ($value instanceof UploadedFile) {
                // If this is an uploaded file store it
                $path = $value->store(config('forms.upload_path'), config('forms.upload_storage'));
                $form['uploads'][$name] = [
                    'path' => $path,
                    'name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                ];
            } elseif (substr($name, 0, 8) === '_delete_') {
                // If the delete checkbox is checked delete the file from storage
                $name = substr($name, 8);
                Storage::disk(config('forms.upload_storage'))->delete($form['uploads'][$name]['path']);
                unset($form['uploads'][$name]);
            } elseif ($name != '_token') {
                // Any other values except _token should be saved here
                $form['values'][$name] = $value;
            }
        }

        // Store the updated form in the session again
        session([config('forms.session_prefix') . $id => $form]);

        // Check for stored uploads
        foreach ($form['uploads'] as $name => $file) {
            if (!$request->$name) {
                // If stored upload is not updated with request remove the valiation rule for it
                unset($form['validate'][$name]);
            }
        }

        // Validate the values
        $request->validate($form['validate']);

        // Handle it!
        return back();
    }
}
