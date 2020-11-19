<?php

namespace NickDeKruijk\LaravelForms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Log;
use Mail;
use Storage;

class FormController extends Controller
{
    private function mail($form)
    {
        $body = '<table>';
        foreach ($form['values'] as $name => $value) {
            $body .= '<tr><td>' . $name . '</td><td>' . $value . '</td></tr>';
        }
        foreach ($form['uploads'] as $name => $value) {
            $body .= '<tr><td>' . $name . '</td><td>' . $value['name'] . '</td></tr>';
        }
        $body .= '</table>';
        Mail::send([], [], function ($message) use ($form, $body) {
            $message->subject($form['controller']['mail_subject']);
            $to = $form['controller']['mail_to'];
            if (!is_array($to)) {
                $to = explode('|', $to);
            }
            foreach ($to as $email) {
                $message->to($email);
            }
            foreach ($form['uploads'] as $upload => $value) {
                $message->attachData(Storage::disk(config('forms.upload_storage'))->get($value['path']), $value['name']);
            }
            $message->setBody($body, 'text/html');
        });
    }

    private function log($form)
    {
        Log::channel($form['controller']['log_channel'])->info('Form post', $form);
    }

    public function post(Request $request, string $id)
    {
        // Get form from session
        $form = session(config('forms.session_prefix') . $id);

        // Walk thru all values
        foreach ($request->all() as $name => $value) {
            if ($value instanceof UploadedFile) {
                // If this is an uploaded file store it
                $path = $value->store(config('forms.upload_path'), config('forms.upload_storage'));
                if (isset($form['uploads'][$name])) {
                    Storage::disk(config('forms.upload_storage'))->delete($form['uploads'][$name]['path']);
                }
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
                // If stored upload is not updated with request remove the validation rule for it
                unset($form['validate'][$name]);
            }
        }

        // Validate the values
        $request->validate($form['validate']);

        // Run thru all handlers
        if (!is_array($form['controller']['handler'])) {
            $form['controller']['handler'] = explode('|', $form['controller']['handler']);
        }
        foreach ($form['controller']['handler'] as $handler) {
            $this->$handler($form);
        }

        // Clear the form uploads and session
        foreach ($form['uploads'] as $upload) {
            Storage::disk(config('forms.upload_storage'))->delete($upload['path']);
        }
        session()->pull(config('forms.session_prefix') . $id);

        // Redirect back
        return back()->with(['form-status' => 'ok']);
    }
}
