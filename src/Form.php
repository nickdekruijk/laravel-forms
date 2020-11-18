<?php

namespace NickDeKruijk\LaravelForms;

class Form
{
    public function open()
    {
        return '<form>' . csrf_token();
    }
    public function close()
    {
        return '</form>';
    }
}
