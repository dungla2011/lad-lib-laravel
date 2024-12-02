<?php

namespace LadLib\Laravel\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LadLib\Common\Database\DbHelper;
use LadLib\Common\Database\MetaOfTableInDb;

class DbHelperLaravel {

    public static function getDbCon(){
        return \Illuminate\Support\Facades\DB::getPdo();
    }

    public static function getDBInfo(){
        return \Illuminate\Support\Facades\Config::get('database.connections.'.\Illuminate\Support\Facades\Config::get('database.default'));
    }

    public static function getAllTableName($x = null, $y = null){
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($tables);
//        echo "</pre>";

        return $tables;
    }

    static function getTableColumns($con, $tableName){

        $obj = DbHelper::getObjModelFromTableName($tableName);
        return $obj::getArrayField();


//        $mm = self::getTableColumnAndDataType($con, $tableName);
//        $ret  = array_keys($mm);
//        return $ret;
    }

    //https://stackoverflow.com/questions/18562684/how-to-get-database-field-type-in-laravel
    public static function getTableColumnAndDataType($conName = 0, $table) {

        $obj = DbHelper::getObjModelFromTableName($table);
        return $obj::getArrayFieldAndDataType();

//        $m1 = DB::select('describe '.$table);
//        $ret = [];
//        foreach($m1 AS $m2){
//
//            $ret [$m2->Field] = $m2->Type;
//
//            // echo "<br> $field_name | $val";
//        }
//        return $ret;
    }





//    /**
//     * @param $tableName
//     * @return MetaOfTableInDb
//     */
//    public static function getMetaObjFromTableName2($tableName){
//        $cls = "\\App\\Models\\" . Str::studly(Str::singular($tableName))."_Meta2";
//        if(!class_exists($cls))
//            return null;
//        $obj = new $cls;
//
//        return $obj;
//    }

    /**
     * @param $tableName
     * @return \App\Models\ModelGlxBase|mixed|null
     */
//    public static function getModelFromTableName_DBTool($tableName){
//        $path = app_path('Models') . '/*.php';
//        $allModel = collect(glob($path))->map(fn ($file) => basename($file, '.php'))->toArray();
//        foreach ($allModel AS $model){
//            $model =  '\\App\\Models\\' . $model;
//            $tmp = new  $model;
//            if($tmp instanceof \App\Models\ModelGlxBase);
//            if($tmp->getTable() == $tableName){
//                return $tmp;
//            }
//        }
//        return null;
//    }

    public static function getTableNameFromModelName($name){
        return Str::snake(Str::pluralStudly($name));
    }

    /**
     * @param Model $model
     * @return array
     * Clone from: vendor/kevincobain2000/laravel-erd/src/LaravelERD.php
     */
    public static function getRelationshipsModel(Model $model)
    {
        $relationships = self::getRelationshipsBaseModel($model);
        $linkItems = [];
        $fromTable = $model->getTable();
        foreach ($relationships as $method => $relationship) {

            $toTable = app($relationship['model'])->getTable();

            // check if is array for multiple primary key
            if (is_array($relationship['foreign_key']) || is_array($relationship['parent_key'])) {
                // TODO add support for multiple primary keys
                $fromPort = ".";
                $toPort = ".";
            } else {
                $isBelongsTo = ($relationship['type'] == "BelongsTo" || $relationship['type'] == "BelongsToMany");
                $fromPort = $isBelongsTo ? $relationship["foreign_key"] : $relationship["parent_key"];
                $toPort   = $isBelongsTo ? $relationship["parent_key"] : $relationship["foreign_key"];
            }

            $linkItems[$method] = [
                "from"     => $fromTable,
                "to"       => $toTable,
                "fromText" => config('laravel-erd.from_text.'.$relationship['type']),
                "toText"   => config('laravel-erd.to_text.'.$relationship['type']),
                "fromPort" => explode(".", $fromPort)[1], //strip tablename
                "toPort"   => explode(".", $toPort)[1],//strip tablename
                "type"     => $relationship['type'],
            ];
        }
        return $linkItems;
    }

    /**
     * Trả lại các hàm có relation của Model
     * Bắt buộc phải có return Relations trên hàm thì mới có trong kết quả ở đây
     * Clone from: vendor/kevincobain2000/laravel-erd/src/LaravelERD.php
     * @param Model $model
     * @return array
     */
    public static function getRelationshipsBaseModel(Model $model): array
    {
        $relationships = [];
       // $model = new $model;
        $rfl = new \ReflectionClass($model);
        $mts = $rfl->getMethods(\ReflectionMethod::IS_PUBLIC);

        //Bỏ qua các Method của Parent
        //Không cần bỏ qua, vì có thể kế thừa, chỉ cần khai báo Docs = return type = Relations
        //$f = new \ReflectionClass($model);
//        $methodsNotParent = array();
//        foreach ($f->getMethods() as $m) {
//            if ($m->class == $model::class) {
//                $methodsNotParent[] = $m->name;
//            }
//        }

        foreach ($mts as $method) {
//            if(!in_array($method->getName(), $methodsNotParent))
//                continue;
            //Để tránh bị call invoke nhiều, làm chậm hàm này (có khi lên đến 1 giây), bỏ đi thì chỉ còn 0.005 giây
            //thì loại bỏ các method không có return type trên Mô tả là Relations
            //Vậy bắt buộc phải có return Relations trên hàm thì mới thực thi
            if(!strstr($method->getDocComment(), 'Illuminate\Database\Eloquent\Relations')){
                continue;
            }
            if ($method->class != get_class($model)
                || !empty($method->getParameters())
                || $method->getName() == __FUNCTION__
            ) {
                continue;
            }
            try {
                $return = $method->invoke($model);

//                dump($return);

                // check if not instance of Relation
                if (!($return instanceof Relation)) {
                    continue;
                }


                $foreignKey = null;
                $related_key = $table = $relatePivoteKey = null;
                $relationType = (new \ReflectionClass($return))->getShortName();
                $modelName = (new \ReflectionClass($return->getRelated()))->getName();
                if($return instanceof BelongsToMany){
                    $foreignKey = $return->getQualifiedForeignPivotKeyName();
                    $relatePivoteKey = $return->getQualifiedRelatedPivotKeyName();
                    $table = $return->getTable();
//                    echo "<br/>\n $relatePivoteKey / $table";
                    $related_key = ($return->getRelatedKeyName());
                }
                else
                    $foreignKey = $return->getQualifiedForeignKeyName();

                $parent_table = ($return->getParent()->getTable());
                $related_table = ($return->getRelated()->getTable());

                $parentKey = $return->getQualifiedParentKeyName();
                $relationships[$method->getName()] = [
                    'type'        => $relationType,
                    'table'        => $table,
                    'parent_table' => $parent_table,
                    'related_table' => $related_table,
                    'model'       => $modelName,
                    'parent_key'  => $parentKey,
                    'related_key'  => $related_key,
                    'foreign_key' => $foreignKey,
                    'relate_pivote_key' => $relatePivoteKey
                ];

            } catch (QueryException $e) {
                loi2($e->getMessage());
            } catch (\TypeError $e) {
                loi2($e->getMessage());

            } catch (\Throwable $e) {
                loi2($e->getMessage());
            }
        }
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($relationships);
//        echo "</pre>";
        return $relationships;
    }
}


