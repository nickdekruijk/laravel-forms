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
    /**
     * Deletes all old uploaded files from storage (garbage collection)
     *
     * @param integer $gracePeriod  the number of seconds a file should be kept in storage
     * @return void
     */
    public static function clearOldUploads(int $gracePeriod = 3600 * 24)
    {
        $disk = Storage::disk(config('forms.upload_storage'));
        foreach ($disk->files(config('forms.upload_path')) as $file) {
            if (time() - $disk->lastModified($file) > $gracePeriod) {
                $disk->delete($file);
            }
        }
    }

    /**
     * Convert a value to an array if needed
     *
     * @param mixed $value
     * @return array
     */
    private static function toArray($value): array
    {
        if (!is_array($value)) {
            $value = explode('|', $value);
        }
        return $value;
    }

    /**
     * The mail handler
     *
     * @param array $form
     * @return void
     */
    private function mail(array $form)
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
            foreach (self::toArray($form['controller']['mail_to']) as $email) {
                $message->to($email);
            }
            foreach (self::toArray($form['controller']['mail_bcc']) as $email) {
                $message->bcc($email);
            }
            foreach ($form['uploads'] ?? [] as $upload => $value) {
                $message->attachData(Storage::disk(config('forms.upload_storage'))->get($value['path']), $value['name']);
            }
            $message->setBody($body, 'text/html');
        });
    }

    /**
     * The log handler
     *
     * @param array $form
     * @return void
     */
    private function log(array $form)
    {
        Log::channel($form['controller']['log_channel'])->info('Form post', $form);
    }

    /**
     * The model handler
     *
     * @param array $form
     * @return void
     */
    private function model(array $form)
    {
        $model = new $form['controller']['model'];
        $model_column = $form['controller']['model_column'];
        $model->$model_column = $form['values'];
        $model->save();
    }

    /**
     * FormController@post route handler
     *
     * @param Request $request
     * @param string $id
     * @return void
     */
    public function post(Request $request, string $id)
    {
        // Clear all old uploaded files (garbage collection)
        $this->clearOldUploads();

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
