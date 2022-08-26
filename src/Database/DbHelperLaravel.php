<?php

namespace LadLib\Laravel\Database;

use Illuminate\Support\Str;
use LadLib\Common\Database\MetaOfTableInDb;

class DbHelperLaravel {

    /**
     * @param $tableName
     * @return \App\Models\ModelGlxBase|mixed|null
     *
        echo Str::studly(Str::singular("demo_tables"));
        echo Str::snake(Str::pluralStudly("DemoTable"));
     */
    public static function getModelFromTableName($tableName){
        $name = Str::studly(Str::singular($tableName));
        $model =  '\\App\\Models\\' . $name;
        if(!class_exists($model))
            return null;
        $model = new $model;
        return $model;
    }

    public static function getModelNameFromTableName($tableName){
        $name = Str::studly(Str::singular($tableName));
        return $name;
    }

    /**
     * Get by the way laravel set tablename automatically with ModelName
     * @param $name
     * @return string
     */
    public static function getTableNameFromModelName($name){
        return Str::snake(Str::pluralStudly($name));
    }

    /**
     * @param $tableName
     * @return MetaOfTableInDb
     */
    public static function getMetaObjFromTableName($tableName){
        $cls = "\\App\\Models\\" . Str::studly(Str::singular($tableName))."_Meta";
        if(!class_exists($cls))
            return null;
        $obj = new $cls;

        return $obj;
    }

    /**
     * @param $tableName
     * @return \App\Models\ModelGlxBase|mixed|null
     *
        echo Str::studly(Str::singular("demo_tables"));
        echo Str::snake(Str::pluralStudly("DemoTable"));
     */
    public static function getModelFromTableName_DBTool($tableName){

//        Str::studly(Str::singular("Products"));
//        Str::pluralStudly("demo_table");

        $path = app_path('Models') . '/*.php';
        $allModel = collect(glob($path))->map(fn ($file) => basename($file, '.php'))->toArray();
        foreach ($allModel AS $model){
            $model =  '\\App\\Models\\' . $model;
            $tmp = new  $model;
            if($tmp instanceof \App\Models\ModelGlxBase);
            if($tmp->getTable() == $tableName){
                return $tmp;
            }
        }
        return null;
    }

}


