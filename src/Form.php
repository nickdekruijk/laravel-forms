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
}
