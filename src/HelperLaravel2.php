<?php

namespace LadLib\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LadLib\Common\Database\MetaOfTableInDb;

class HelperLaravel2 {


    public static function getUrlFromRouteName($namedRoute){
        $routeCollection = Route::getRoutes();
        if($routeCollection->hasNamedRoute($namedRoute))
        {
            $route = $routeCollection->getByName($namedRoute);
            return $route->uri();
        }
        return null;
    }

    public static function getRouteMethodsFromRouteName($namedRoute)
    {
        $routeCollection = Route::getRoutes();
        if($routeCollection->hasNamedRoute($namedRoute))
        {
            $route = $routeCollection->getByName($namedRoute);
            return $route->methods;
        }

        return null;
    }





}


