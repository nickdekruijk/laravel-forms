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
    ];

    /**
     * Unique id for the current form
     *
     * @var string
     */
    private $id;

    /**
     * The validation rules for all fields
     *
     * @var array
     */
    private $validate = [];

    /**
     * Store the current form in a session for FormController access
     *
     * @return void
     */
    private function form_session()
    {
        session([config('forms.session_prefix') . $this->id => [
            'attributes' => $this->attributes,
            'validate' => $this->validate,
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
     * @param array $attributes
     * @return void
     */
    public function open(array $attributes = [])
    {
        // Merge attributes with defaults
        $this->merge_attributes($attributes);

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

        // Return response
        return '</form>';
    }

    /**
     * Add $name as attribute and return all attributes for html element
     *
     * @param string $element       The html element to output (e.g. INPUT, TEXTAREA etc)
     * @param string $name          The name attribute
     * @param array $attributes     All other html attributes
     * @return string
     */
    private function html_element(string $element, string $name, array $attributes): string
    {
        $attributes['name'] = $name;

        // Generate response
        $response = '<' . $element;
        foreach ($attributes as $attribute => $value) {
            if ($value) {
                $response .= ' ' . $attribute . '="' . $value . '"';
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
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function input(string $name, string $default = null, array $attributes = [], $validate = null): string
    {
        // Get previous input value or use default
        $attributes['value'] = old($name, $default);
        $this->add_rule($name, $validate);
        return $this->html_element('input', $name, $attributes);
    }

    /**
     * Return an <input type="text"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function text(string $name, string $default = null, array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'text'], $attributes), $validate);
    }

    /**
     * Return an <input type="email"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function email(string $name, string $default = null, array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'email'], $attributes), $validate);
    }

    /**
     * Return an <input type="file"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function file(string $name, string $default = null, array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'file'], $attributes), $validate);
    }

    /**
     * Return an <input type="submit"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function submit(string $name, array $attributes = [], $validate = null): string
    {
        return $this->input($name, null, array_merge(['type' => 'submit'], $attributes), $validate);
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
