<?php

namespace NickDeKruijk\LaravelForms;

use Illuminate\Support\ViewErrorBag;

class Form
{
    /**
     * The default form attributes, merged with Form::open($attributes)
     *
     * @var array
     */
    private $attributes = [
        'method' => 'POST',
        'action' => null,
        'enctype' => 'multipart/form-data',
    ];

    /**
     * FormController options
     *
     * @var array
     */
    private $controller = [
        'handler' => null, // 'log|mail|mailable|model'
        'log_channel' => 'stack',
        'mail_to' => [], // Valid e-mailaddress(es) to send mail to
        'mail_bcc' => [], // Valid e-mailaddress(es) to send mail to as bcc
        'mail_subject' => 'New form submission',
        'mailable' => null, // 'App/Mail/Mailable'
        'model' => null, // 'App\Models\Registrations'
        'model_column' => 'data', // Json column to store data
    ];

    /**
     * Store validation errors here
     *
     * @var ViewErrorBag
     */
    private $errors = null;

    /**
     * To keep track of any input=file elements so Form::close will also include the bytesToSize javascript function
     *
     * @var boolean
     */
    private $hasFileInput = false;

    /**
     * Unique id for the current form
     *
     * @var string
     */
    private $id;

    /**
     * References to all file uploads within the form
     *
     * @var array
     */
    private $uploads = [];

    /**
     * The validation rules for all fields
     *
     * @var array
     */
    private $validate = [];

    /**
     * Keep all input values here
     *
     * @var array
     */
    private $values = [];

    /**
     * Store the current form in a session for FormController access
     *
     * @return void
     */
    private function form_session()
    {
        session([config('forms.session_prefix') . $this->id => [
            'attributes' => $this->attributes,
            'controller' => $this->controller,
            'uploads' => $this->uploads,
            'validate' => $this->validate,
            'values' => $this->values,
        ]]);
    }

    /**
     * Merge default $this->attributes array with new form attributes
     *
     * @param array $attributes
     * @return void
     */
    private function merge_attributes(array $attributes)
    {
        // Overwrite action with route if given
        if (isset($attributes['route'])) {
            $attributes['action'] = route($attributes['route']);
            unset($attributes['route']);
        }

        // Merge the attributes with defaults
        $this->attributes = array_merge($this->attributes, $attributes);

        // Generate unique id for the current form
        $this->id = md5(config('forms.session_prefix') . url()->current());

        // Use form-default route if action still empty
        if (empty($this->attributes['action'])) {
            $this->attributes['action'] = route(config('forms.route_name'), $this->id);
        }
    }

    /**
     * Open a new <form>
     *
     * @param array $attributes     list of html attributes for the <form> element
     * @param array $controller     FormController options
     * @return void
     */
    public function open(?array $attributes = [], ?array $controller = [])
    {
        // Merge attributes with defaults
        $this->merge_attributes($attributes ?: []);

        // Merge controller options with defaults
        $this->controller = array_merge($this->controller, $controller ?: []);

        // Get form uploads and values from session
        $form = session(config('forms.session_prefix') . $this->id);
        $this->uploads = $form['uploads'] ?? [];
        $this->values = $form['values'] ?? [];

        // Start response
        $response = '<form';

        // Add all set attributes
        foreach ($this->attributes as $attribute => $value) {
            if ($value) {
                $response .= ' ' . $attribute . '="' . $value . '"';
            }
        }

        // End of <form tag
        $response .= '>';

        // Add hidden CSRF field
        $response .= csrf_field();

        // Return the complete response
        return $response;
    }

    /**
     * Close a </form>
     *
     * @return void
     */
    public function close()
    {
        // Store the current form in a session
        $this->form_session();

        $response = '';

        // If the form has any input=file elemements add the necessary JavaScript code
        if ($this->hasFileInput) {
            $response .= $this->fileInputJavaScript();
        }

        $response .= '</form>';
        // Return response
        return $response;
    }

    /**
     * Add $name as attribute and return all attributes for html element
     *
     * @param string $element       The html element to output (e.g. INPUT, TEXTAREA etc)
     * @param string $name          The name attribute
     * @param array $attributes     All other html attributes
     * @return string
     */
    private function html_element(string $element, string $name, ?array $attributes): string
    {
        // Initiate response html
        $response = '';

        // Add name as attribute 
        $attributes['name'] = $name;

        // Add error to class attribute and add error-msg element if a validation error exists
        if ($this->errors && $this->errors->has($name)) {
            $classes = explode(' ', $attributes['class'] ?? '');
            array_push($classes, 'error');
            $attributes['class'] = implode(' ', $classes);
            $attributes['oninput'] = ($attributes['onkeyup'] ?? '') . ';' . ($attributes['oninput'] ?? '') . ';this.previousSibling.classList.add(\'changed\')';
            $attributes['onchange'] = ($attributes['onkeyup'] ?? '') . ';' . ($attributes['onchange'] ?? '') . ';this.previousSibling.classList.add(\'changed\')';
            $response .= '<span class="error-msg">' . rtrim($this->errors->first($name), '.') . '</span>';
        }

        // Generate response
        $response .= '<' . $element;
        foreach ($attributes as $attribute => $value) {
            if ($value) {
                if (is_numeric($attribute)) {
                    $response .= ' ' . $value;
                } else {
                    $response .= ' ' . $attribute . '="' . $value . '"';
                }
            }
        }

        // End of <input tag
        $response .= '>';

        return $response;
    }

    /**
     * Add a validation rule to $this->validate array
     *
     * @param string $name
     * @param mixed $rule
     * @return void
     */
    private function add_rule(string $name, $rule)
    {
        // Add validation rule 
        if ($rule) {
            $this->validate[$name] = $rule;
        }
    }

    /**
     * Return an <input> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function input(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        // Get previous input value or use default
        $attributes['value'] = $this->values[$name] ?? $default;
        $this->add_rule($name, $validate);
        return $this->html_element('input', $name, $attributes);
    }

    /**
     * Return a <textarea> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old  available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function textarea(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        $this->add_rule($name, $validate);
        return $this->html_element('textarea', $name, $attributes) . ($this->values[$name] ?? $default) . '</textarea>';
    }

    /**
     * Return an <input type="text"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function text(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'text'], $attributes ?: []), $validate);
    }

    /**
     * Return an <input type="email"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function email(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'email'], $attributes ?: []), $validate);
    }

    /**
     * Return an <input type="date"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function date(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'date'], $attributes ?: []), $validate);
    }

    /**
     * Return a human readable size
     *
     * @param integer $bytes
     * @return string
     */
    public static function bytesToSize(int $bytes): string
    {
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if ($bytes == 0) return '0 Byte';
        $i = (floor(log($bytes) / log(1024)));
        return round($bytes / pow(1024, $i)) . ' ' . $sizes[$i];
    }

    /**
     * This will return the necessary javascript for make file inputs work
     *
     * @return string
     */
    public static function fileInputJavaScript(): string
    {
        return "
            <script>
                if (typeof bytesToSize != 'function') {
                    window.bytesToSize = function(bytes) {
                        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                        if (bytes == 0) return '0 Byte';
                        var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                        return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                    };
                }
                if (typeof form_file_browse_click != 'function') {
                    window.form_file_browse_click = function(t) {
                        t.parentElement.querySelector('INPUT[type=file]').click();
                    };
                }
                if (typeof form_file_delete_click != 'function') {
                    window.form_file_delete_click = function(t) {
                        t.parentElement.querySelector('.button-browse').style.display = 'block';
                        t.parentElement.querySelector('.button-delete').style.display = 'none';
                        t.parentElement.querySelector('.fileinfo').style.display = 'none';
                        t.parentElement.querySelector('.fileinfo').innerHTML = '';
                        t.parentElement.querySelector('INPUT[type=checkbox]').checked = false;
                        t.parentElement.querySelector('INPUT[type=file]').value = '';
                    };
                }
                if (typeof form_file_input_change != 'function') {
                    window.form_file_input_change = function(t) {
                        t.parentElement.querySelector('INPUT[type=checkbox]').checked = false;
                        console.log('form_file_input_change');
                        t.parentElement.querySelector('.fileinfo').innerHTML = t.value.replace(/^.*[\\\/]/, '') + ' (' + bytesToSize(t.files[0].size) + ')';
                        t.parentElement.querySelector('.fileinfo').style.display = 'block';
                        t.parentElement.querySelector('.button-delete').style.display = 'block';
                        t.parentElement.querySelector('.button-browse').style.display = 'none';
                    };
                }
            </script>
        ";
    }

    /**
     * Return an <input type="file"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function file(string $name, string $default = null, ?array $attributes = [], $validate = null): string
    {
        $this->hasFileInput = true;

        $attributes['value'] = $this->values[$name] ?? $default;
        $this->add_rule($name, $validate);
        $response = '<span class="' . ($this->errors && $this->errors->has($name) ? 'error ' : '') . ($attributes['class'] ?? 'form_input') . '">';
        $response .= '<button class="button-browse" type="button" ' . (isset($this->uploads[$name]['name']) ? 'style="display:none" ' : '') . 'onclick="return form_file_browse_click(this)">' . trans('form::button.browse') . '</button>';
        $response .= '<button class="button-delete" type="button" ' . (empty($this->uploads[$name]['name']) ? 'style="display:none" ' : '') . 'onclick="return form_file_delete_click(this)">' . trans('form::button.delete') . '</button>';
        if (isset($this->uploads[$name]['name'])) {
            $response .= '<span class="fileinfo">' . $this->uploads[$name]['name'] . ' (' . $this->bytesToSize($this->uploads[$name]['size']) . ')' . '</span>';
        } else {
            $response .= '<span class="fileinfo" style="display:none"></span>';
        }
        $response .= '<input style="display:none" type="checkbox" name="_delete_' . $name . '">';
        $response .= $this->html_element('input', $name, array_merge(['type' => 'file', 'style="display:none"', 'onchange' => 'return form_file_input_change(this)'], $attributes));
        $response .= '</span>';
        return $response;
    }

    /**
     * Return an <input type="submit"> element
     *
     * @param string $name          the name="" attribute
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function submit(string $name, array $attributes = [], $validate = null): string
    {
        return $this->input($name, null, array_merge(['type' => 'submit'], $attributes), $validate);
    }

    /**
     * Return a <button> element
     *
     * @param string $text          the text inside the button
     * @param array $attributes     other input html attributes
     * @return string
     */
    public function button(string $text, array $attributes = []): string
    {
        return $this->html_element('button', '', $attributes) . $text . '</button>';
    }

    /**
     * Return a <select> element with options
     *
     * @param string $name            the name="" attribute
     * @param array $options          array containing the value and string options for <option value="key">value</option> 
     * @param string $default         the default value if no old available
     * @param array|null $attributes  other input html attributes
     * @param mixed $validate         Laravel validation rules
     * @return string
     */
    public function select(string $name, array $options, string $default = null, ?array $attributes = [], $validate = null): string
    {
        $this->add_rule($name, $validate);
        $response = $this->html_element('select', $name, $attributes);
        foreach ($options as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }
            $response .= '<option' . (($this->values[$name] ?? $default) == $key ? ' selected' : '') . ' value="' . $key . '">' . $value . '</option>';
        }
        $response .= '</select>';
        return $response;
    }

    /**
     * Return a <UL> list with (validation) errors if any
     *
     * @param ViewErrorBag $errors  The $errors bag passed to the view
     * @param string $message       General error message to show on top of list
     * @param string $class         CSS class(es) to apply to the UL element
     * @return string
     */
    public function errors(ViewErrorBag $errors, string $message = null, string $class = 'alert alert-danger'): string
    {
        $this->errors = $errors;
        $response = '';
        if ($errors->any()) {
            $response .= '<ul class="' . $class . '">';
            if ($message) {
                $response .= $message;
            }
            foreach ($errors->all() as $error) {
                $response .= '<li>' . $error . '</li>';
            }
            $response .= '</ul>';
        }
        return $response;
    }
}
