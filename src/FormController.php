<?php

namespace NickDeKruijk\LaravelForms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function post(Request $request, string $id)
    {
        $form = session(config('forms.session_prefix') . $id);
        $request->validate($form['validate']);
    }
}
