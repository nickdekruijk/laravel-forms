<?php

namespace NickDeKruijk\LaravelForms;

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
        return '</form>';
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
        // Start response
        $response = '<input';

        // Set name attribute from $name parameter
        $attributes['name'] = $name;
        // Get previous input value or use default
        $attributes['value'] = old($name, $default);

        // Add all set attributes
        foreach ($attributes as $attribute => $value) {
            if ($value) {
                $response .= ' ' . $attribute . '="' . $value . '"';
            }
        }

        // Add validation rule
        if ($validate) {
            $this->validate[$name] = $validate;
        }

        // End of <input tag
        $response .= '>';

        // Return the complete response
        return $response;
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
     * Return an <input type="submit"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       Laravel validation rules
     * @return string
     */
    public function submit(string $name, string $default = null, array $attributes = [], $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'submit'], $attributes), $validate);
    }
}
