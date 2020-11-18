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
     * Merge default $this->attributes array with new form attributes
     *
     * @param array $attributes
     * @return void
     */
    private function merge_attributes(array $attributes)
    {
        if (isset($attributes['route'])) {
            $attributes['action'] = route($attributes['route']);
            unset($attributes['route']);
        }
        $this->attributes = array_merge($this->attributes, $attributes);
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
     * @param mixed $validate       laravel validation
     * @return string
     */
    public function input(string $name, string $default = null, array $attributes = [], mixed $validate = null): string
    {
        $response = '<input';

        $attributes['name'] = $name;
        $attributes['value'] = old($name, $default);

        // Add all set attributes
        foreach ($attributes as $attribute => $value) {
            if ($value) {
                $response .= ' ' . $attribute . '="' . $value . '"';
            }
        }
        $response .= '>';
        return $response;
    }

    /**
     * Return an <input type="text"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       laravel validation
     * @return string
     */
    public function text(string $name, string $default = null, array $attributes = [], mixed $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'text'], $attributes));
    }

    /**
     * Return an <input type="submit"> element
     *
     * @param string $name          the name="" attribute
     * @param string $default       the default value if no old() available
     * @param array $attributes     other input html attributes
     * @param mixed $validate       laravel validation
     * @return string
     */
    public function submit(string $name, string $default = null, array $attributes = [], mixed $validate = null): string
    {
        return $this->input($name, $default, array_merge(['type' => 'submit'], $attributes));
    }
}
