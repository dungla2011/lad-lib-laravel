<?php

namespace LadLib\Laravel\Database;

use Illuminate\Database\Eloquent\Model;

class test1
{
    function __construct()  {
        echo "<br> OK: ".get_called_class();
    }
}

