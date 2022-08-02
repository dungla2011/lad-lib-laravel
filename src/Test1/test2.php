<?php

namespace LadLib\Laravel\Test1;

use Illuminate\Database\Eloquent\Model;

class test2
{
    function __construct()  {
        echo "<br> OK: ".get_called_class();
    }
}

