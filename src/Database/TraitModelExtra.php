<?php

namespace LadLib\Laravel\Database;

use App\Components\ClassRandId2;
use App\Components\clsParamRequestEx;
use App\Models\FileUpload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LadLib\Common\Database\MetaOfTableInDb;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;

trait TraitModelExtra
{
//    public static $createRules;

    function getValidateRuleInsert(){
    }

    function getValidateRuleUpdate(){
/*        return [
            //Trường hợp email duy nhất, nhưng lại bỏ qua email đã bị xóa:
            'email' => 'required|email|unique:event_user_infos,email,' . $id . ',id,deleted_at,NULL',

            'email'=>'required|email|max:100|min:6|unique:users,email,'.$this->id,
            'username'=>'required|max:50|min:8|unique:users,username,'.$this->id,
            'password'=>'required|max:50|min:8',
        ];
*/
    }

    function getThumbInImageListWithNoImg($field = 'image_list'){
        return $this->getThumbInImageList($field, 1);
    }

    function getThumbSmall($w = 300, $domain = null){
        $field = "image_list";
        if(isset($this->$field) && $this->$field){

            $idf = explode(',',$this->$field)[0];
            if(is_numeric($idf)){
                if($file = FileUpload::find($idf)){
                    if($file instanceof FileUpload);
                    return $ret = $file->getCloudLinkImgThumb($w, $domain);
                }
            }
        }
    }

    function getThumbInImageList($field = 'image_list', $returnNoImage = 0){

        if(str_starts_with($this->$field, 'http'))
            return $this->$field;
        if(str_starts_with($this->$field, '/'))
            return $this->$field;


        if(isIPDebug()){

        }

        $ret = null;
        if(isset($this->$field) && $this->$field){


            $idf = explode(',',$this->$field)[0];
            if(is_numeric($idf)){

                if(isIPDebug()){
//                    dump("xxx0 - $idf");
//                    die();
                }
                if($file = FileUpload::find($idf)){
                    if(isIPDebug()){

                    }
                    if($file instanceof FileUpload);

                    $ret = $file->getSlink();
//                    $ret = $file->getCloudLinkImage();

                }
            }
        }

        if($returnNoImage && !$ret)
            return "/images/no-img.jpg";

        return $ret;
    }

    /**
     * @param $field
     * @param $getIdOrObj = 1 get Id list , 2: get objList
     * @return array
     */
    function getAllFileList($field, $getIdOrObj = 1){
        $mm = [];

//        getch("this->$field = " . $this->$field);
        if(isset($this->$field) && $this->$field){
            $m1 = explode(',',$this->$field);
            if($m1)
                foreach ($m1 AS $idf){
                    if(is_numeric($idf)){
                        if($getIdOrObj == 1){
                            $mm[] = $idf;
                        }
                        else
                        if($file = FileUpload::find($idf)) {
                            if ($file instanceof FileUpload) ;
                            $mm[] = $file;
                        }
                    }
                }
        }
        return $mm;
    }

    function getAllImageList(){
        $mm = [];
        if(str_starts_with($this->image_list, 'http'))
            return [$this->image_list];
        if(isset($this->image_list) && $this->image_list){
            $m1 = explode(',',$this->image_list);
            if($m1)
                foreach ($m1 AS $idf)
                if(is_numeric($idf)){
                    if($file = FileUpload::find($idf)) {
                        if ($file instanceof FileUpload) ;
//                        $mm[] = $file->getCloudLinkImage();
                        $mm[] = $file->getSlink();
                    }
                }
        }
        return $mm;
    }

    function getId(){
        return $this->id;
    }

    /**
     * Nếu có relate pivot table ở đây, update pivot table
     * @param $objMeta MetaOfTableInDb
     * @param $val
     * Todo: trường hợp Sync ở đây, như Tag có thể được tạo mới, nhưng ở đây ĐANG chỉ nhận các ID tag đã có, ko nhận tag mới
     */
    function syncDataRelationShip($objMeta, $val, $action = null){



        $field = $objMeta->field;

        //Kiểu cũ, cần khai báo trong field của meta
        if($funcJoinModel = $objMeta->join_func_model){
        }
        else
        //Kiểu mới, model và meta có cùng hàm join name gạch dưới _
        if(method_exists($this, $field)){
            $funcJoinModel = $field;
        }
        if(isDebugIp()){
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($val);
//            echo "</pre >    $objMeta->join_func_model ";

        }

        if($funcJoinModel){

            $mV = [];
            if(!is_array($val))
                $val = trim($val, ', ');
                if($val)
                    $mV = explode(",", $val);
            else
                $mV = $val;

            if(is_array($mV)){
                //Luôn là sync, vì insert sau mới sync
                $this->$funcJoinModel()->sync($mV);
            }
//            die( $this::class . " /  $funcJoinModel / $val  / " . serialize($mV));
        }

        if($funcJoinRelate = $objMeta->join_relation_func){
            $mJoin = $objMeta->getJoinRelationshipOfModel();
            if(isset($mJoin[$funcJoinRelate])){
                $mVal = [];
                //Các val input gửi lên là các ID cách nhau bởi dấu ,
                if(is_string($val)  && (trim($val) == '' || trim($val) == ',')){
                    $mVal = [];
                }
                else{
                    //Nếu mVal là mảng các đối tượng, hoặc mảng các số
                    if(!is_array($val)){
                        $val = trim($val, ',');
                        if($val)
                            $mVal = explode(',', $val);
                    }
                    else{

                        //Nếu là mảng các object, thì sẽ chỉ lấy ra ID của object
                        //Trường hợp Tag gửi lên cả mảng object (nguyên như được Get xuống bới API, thì sẽ là mảng các object)
                        $mVal = [];
                        foreach ($val AS $tmp){
                            if(!$tmp)
                                continue;
                            if(is_object($tmp)){
                                $mVal[] = $tmp->id;
                            }
                            else {//Bắt buộc phải là số ở đây  mới chuẩn:
                                if(!is_numeric($tmp))
                                    return rtJsonApiError("Not number ???");
                                $mVal[] = $tmp;
                            }
                        }
                    }
                }

                $mJoin = $objMeta->getJoinRelationshipOfModel();
                $mJoinRelate = $mJoin[$funcJoinRelate];

                if($mJoinRelate['type'] == 'BelongsToMany'){
                    //Update lại bảng bên kia
                    //Lấy ra obj để sync
//                    die("sync.... $funcJoinRelate");
                    $this->$funcJoinRelate()->sync($mVal);
                }
            }
        }
    }

    /** Laravel Version
     * Get all fields of a table , and return data type of field
     * @return array [fields => data type]
     */
    public function getTableColumnAndDataType() {

        return DbHelperLaravel::getTableColumnAndDataType(null, $this->getTable());

//        if($this instanceof Model);
//
//        $con = $this->getConnectionName();
//
//        $allField = $this->getConnection($con)->getSchemaBuilder()->getColumnListing($this->getTable());
//        $ret = [];
//        foreach($allField AS $field_name){
//            $val = $this->getConnection()->getDoctrineColumn($this->getTable(), $field_name)->getType()->getName();
//            $ret [$field_name] = $val;
//            // echo "<br> $field_name | $val";
//        }
//        return $ret;
    }


    /**
     * @return MetaOfTableInDb[]|\LadLib\Common\Database\MetaTableCommon[]|null
     */
    public static function getApiMetaArray(){
        $cls = get_called_class();
        $model = new $cls;
        $metaObj = \LadLib\Common\Database\MetaTableCommon::getMetaObjFromTableName($model->getTable());
        if(!$metaObj)
            return null;
        $metaObj::$_dbConnection = DbHelperLaravel::getDbCon();
        return $metaObj->getMetaDataApi();
    }

    public static function getArrayFieldList(){
        return array_keys(self::getApiMetaArray());
    }

    /** Lấy MetaObj nhanh chóng
     * @return \LadLib\Common\Database\MetaOfTableInDb|null
     */
    public static function getMetaObj(){
        $cls = get_called_class();
        $model = new $cls;
        $metaObj = \LadLib\Common\Database\MetaTableCommon::getMetaObjFromTableName($model->getTable());
        if(!$metaObj)
            return null;
        $metaObj::$_dbConnection = DbHelperLaravel::getDbCon();
        return $metaObj;
    }

    /**
     * Lấy dữ liệu , với các tham số params từ URL (get, post)
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    function queryDataWithParams(array $params, clsParamRequestEx $objPr = null): mixed {

        if(isDebugIp()){

            DB::enableQueryLog();
        }
        $objMeta = $this::getMetaObj();
        if( $objMeta instanceof MetaOfTableInDb)

        $mMetaAll = $objMeta->getMetaDataApi();
        $gid = 1;
        if($objPr){
            if(isset($mMetaAll['user_id']))
                $objPr->setUidIfMust();
            $gid = $objPr->set_gid;
        }

        $tblName = $objMeta->table_name_model;



        $latest = 1;
        $nPerPage = @$params['limit'];
        if(!$nPerPage)
            $nPerPage = $objMeta::$limitRecord;

        if(isset($params['last_order'])){
            $nPerPage = $objMeta::enableAddMultiItem();
        }
////
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($objPr);
//        echo "</pre>";
//        die();

        //Nếu cần thì sử dụng module:
        //$module = $paramEx['module'];

        //$lastest = 1, $nPerPage = 10, $gid = null


        //$dataRet = $modelObj;
        $dataRet = $this;

//        dump($this);
//        die();

//        $mFieldIndexAndExtra = $objMeta->getShowIndexAllowFieldList($gid);
        $mFieldIndexDb  = $objMeta->getShowIndexAllowFieldList($gid,0);



        if(!$mFieldIndexDb){
//            dump("Not field to show");
//            return null;
            $mFieldIndexDb = ['id'];
        }

        if(isset($params['in_trash'])){
            try{
            //dump($dataRet);
            if(!method_exists($dataRet, 'trashed')){
                return null;
            }

                $haveSort = 0;
                foreach ($params AS $pr1=>$vl1)
                    if(str_starts_with($pr1, DEF_PREFIX_SORTBY_URL_PARAM_GLX)) {
                        $haveSort = 1;
                        break;
                    }

                if(!$haveSort)
                {
                    $dataRet = $dataRet->orderBy( $tblName.'.deleted_at', "DESC")->onlyTrashed();
                }else{

                    $dataRet = $dataRet->onlyTrashed();
                }

            }
            catch (\BadMethodCallException $e) { // For PHP 7
                bl($e->getMessage());
                return null;
            }
        }

        $kJoinField = null;
        if($mJoinField = $objMeta->getJoinField())
            $kJoinField = array_keys($mJoinField);

//        if($tblName == 'news')
        if($mJoinField)
        {
            foreach ($mFieldIndexDb AS $id=>$fieldx){
                if(in_array($fieldx, $kJoinField))
                    $mFieldIndexDb[$id] = $mJoinField[$fieldx]['table'].".".$mJoinField[$fieldx]['field'];
                else
                    $mFieldIndexDb[$id] = $tblName.".".$fieldx;
            }
            $dataRet = $dataRet->select($mFieldIndexDb);
        }
        else {
            foreach ($mFieldIndexDb AS $id=>$fieldx){
                $mFieldIndexDb[$id] = $tblName.".".$fieldx;
            }
            $dataRet = $dataRet->select($mFieldIndexDb);
        }

        //Nếu có join
        //if($tblName == 'news')
        {
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($mJoinField);
//            echo "</pre>";
//            die();
            if($mJoinField){
                foreach ($mJoinField AS $alias=>$mInfo){

                    $tblJ = $mInfo['table'];
                    //$dataRet->leftJoin($tblJ, $tblName.".".$mInfo['field_local'], '=', $tblJ.'.'.$mInfo['field_remote'])->addSelect($tblJ.'.'.$mInfo['field'], $tblJ.'.email as '.$alias);

                    if(isset($mInfo['field_local1']) && isset($mInfo['field_remote1'])) {
//                        if(isIPDebug()){
//
////                            die($tblName.".".$mInfo['field_local1']. '='. $tblJ.'.'.$mInfo['field_remote1']);
//                        }
                        $dataRet->leftJoin($tblJ, function($join) use ($tblName, $mInfo, $tblJ) {
                            $join->on($tblName.".".$mInfo['field_local'], '=', $tblJ.'.'.$mInfo['field_remote'])->
                            on($tblName.".".$mInfo['field_local1'], '=', $tblJ.'.'.$mInfo['field_remote1']);
                        });
                    }
                    else
                    {
                        $dataRet->leftJoin($tblJ, $tblName.".".$mInfo['field_local'], '=',
                            $tblJ.'.'.$mInfo['field_remote']);
                    }

                    $dataRet->addSelect($tblJ.'.'.$mInfo['field'].' as '.$alias);
//                $dataRet->leftJoin('users', 'news.user_id', '=', 'users.id')->addSelect('users.email', 'users.email as email1');
                }
            }
//                die("$tblName");
//                die("xxx $ipx");
        }

//        if(isDebugIp())
        {
            $objMeta->getJoinExtra($dataRet);
//            $dataRet->addSelect($objMeta->getJoinExtra($dataRet, 1));
        }

        $mMapFieldAlias = $objMeta->getMapJoinFieldAlias();


        $fieldUID = 'user_id';

        if($objPr->set_field_uid)
            $fieldUID = $objPr->set_field_uid;

        if($objPr->need_set_uid)
            $dataRet = $dataRet->where($tblName.".".$fieldUID, $objPr->need_set_uid);

        $haveSort = 0;
        //Tìm tất cả sort, search để đưa vào query
        foreach ($params as $param => $val) {

            //Nếu là search:
            $field = null;

            if(str_starts_with($param, DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX)) {
//                $sname = substr($param, strlen(DEF_PREFIX_SEARCH_OPT_URL_PARAM_GLX));
//                $field = $objMeta->getFieldFromShortName($sname);
            }

//            if(!$field)
            if(str_starts_with($param, DEF_PREFIX_SEARCH_URL_PARAM_GLX)) {
                $sname = substr($param, strlen(DEF_PREFIX_SEARCH_URL_PARAM_GLX));
                $field = $objMeta->getFieldFromShortName($sname);
            }




            if($field)
            {
                //các trường này sẽ là join pivotable
                if($field[0] == '_') {
                    if(method_exists($this, $field)){
                        //Xem pivot trên trường nào
                        $joinInfo = \LadLib\Laravel\Database\DbHelperLaravel::getRelationshipsBaseModel($this);
                        //Nếu có join function:
                        //Cần xem thêm các kieu join khac
                        //    "_roles" => array:9 [▼
                        //    "type" => "BelongsToMany"
                        //    "table" => "role_user"
                        //    "parent_table" => "users"
                        //    "related_table" => "roles"
                        //    "model" => "App\Models\Role"
                        //    "parent_key" => "users.id"
                        //    "related_key" => "id"
                        //    "foreign_key" => "role_user.user_id"
                        //    "relate_pivote_key" => "role_user.role_id"
                        //  ]
                        if($joinInfo[$field]  ?? ''){
                            $pivotField = $joinInfo[$field]['relate_pivote_key'];

//                            $usersWithRole = User::whereHas('_roles', function($query) {
//                                $query->where('role_id', 1);
//                            })->get();

                            $dataRet->whereHas($field, function($query) use ($val, $pivotField){
                                $query->where($pivotField, $val);
                            })->get();

                        }

                    }
//                    continue;
                }

                if(!isset($mMetaAll[$field]))
                    loi("Not found key meta: $field");

                $objMeta = $mMetaAll[$field];


                if(!$objMeta->isShowIndexField($field, $gid))
                    continue;

                if ($objMeta->isSearchAbleField($field, $gid)) {
                    //$sname = str_replace(DEF_PREFIX_SEARCH_URL_PARAM_GLX, '', $param);

                    $mRandField = null;
                    if($objMeta->isUseRandId())
                        $mRandField = $objMeta->getRandIdListField();

                    if($mRandField && in_array($field, $mRandField) && $val && !is_numeric($val))
                        $val = ClassRandId2::getIdFromRand($val);


                    $isNumberField = $objMeta->isNumberField($field);


                    $optSearch = $objMeta->getOptSearchOfField($params, $field);


                    if (!array_key_exists($optSearch, MetaOfTableInDb::getArrayFilterOperator()))
                        loi2("Not valid Search Operator ($optSearch)!");

                    $field5 = $tblName.".".$field;
                    //Nếu là field của join table
                    if($field[0] == '_') {
                        if($mMapFieldAlias[$field] ?? '') {
                            $field5 = $mMapFieldAlias[$field];
                            $optSearch = "C";
                        }
                        else
                            $field5 = $field;
                    }

                    if($mJoinField){
                        foreach ($mJoinField AS $alias=>$mInfo){
                            if($alias == $field){
                                $field5 = $mInfo['table'].'.'.$mInfo['field'];
                                break;
                            }
                        }
                    }

                    //Todo:
                    //Chú ý về DOS, DDOS..., cần LIMIT số query vào đây/1 ip...?
                    if ($optSearch) {

                        if ($optSearch == 'eq')
                            $dataRet = $dataRet->where($field5, "=", $val);
                        elseif ($optSearch == 'S' && !$isNumberField)
                            $dataRet = $dataRet->where($field5, "LIKE", "$val%");
                        elseif ($optSearch == 'C' && !$isNumberField)
                            $dataRet = $dataRet->where($field5, "LIKE", "%$val%");
                        elseif ($optSearch == 'ne'){
                            $dataRet = $dataRet->where($field5, "!=", $val);
                        }
                        elseif ($optSearch == 'in'){
                            if($val && is_string($val)){
                                if($mVal = explode(",", $val))
                                    $dataRet = $dataRet->whereIn($field5, $mVal);
                            }
                            else{
//                                $dataRet = $dataRet->whereIn($field5, '');
                            }
                        }
                        elseif ($optSearch == 'gte')
                            $dataRet = $dataRet->where($field5, ">=", $val);
                        elseif ($optSearch == 'gt')
                            $dataRet = $dataRet->where($field5, ">", $val);
                        elseif ($optSearch == 'lt')
                            $dataRet = $dataRet->where($field5, "<", $val);
                        elseif ($optSearch == 'lte')
                            $dataRet = $dataRet->where($field5, "<=", $val);
                        elseif ($optSearch == 'N' || $optSearch == 'E'){
                            $dataRet = $dataRet->whereRaw(" ($field5 IS NULL OR $field5 = '') ");
                        }
                        elseif ($optSearch == 'NE'){
                            $dataRet = $dataRet->whereRaw(" ($field5 IS NOT NULL AND $field5 <> '') ");
                        }
                        elseif ($optSearch == 'B1') {
                            if (!strstr($val, ','))
                                loi2("Not valid between param, not , in '$val' to separate 2 value");
                            $val = trim(preg_replace('/\s+/', ',', $val)); //Remove all doule space
                            $dataRet = $dataRet->where($field5, ">=", explode(',', $val)[0]);
                            $dataRet = $dataRet->where($field5, "<=", explode(',', $val)[1]);
                        }
                        elseif ($optSearch == 'B2') {
                            if (!strstr($val, ';'))
                                loi2("Not valid between param, not ; in '$val' to separate 2 value");
                            $val = trim(preg_replace('/\s+/', ';', $val)); //Remove all doule space
                            $dataRet = $dataRet->where($field5, ">=", explode(';', $val)[0]);
                            $dataRet = $dataRet->where($field5, "<=", explode(';', $val)[1]);
                        }
                        elseif ($optSearch == 'B') {
                            if (!strstr($val, ' '))
                                loi2("Not valid between param, not space in '$val' to separate 2 value");
                            $val = trim(preg_replace('/\s+/', ' ', $val)); //Remove all doule space
                            $dataRet = $dataRet->where($field5, ">=", explode(' ', $val)[0]);
                            $dataRet = $dataRet->where($field5, "<=", explode(' ', $val)[1]);
                        } else
                            $dataRet = $dataRet->where($field5, "=", $val);


                    } else{
                        if($objMeta->isMultiValueField($field)){
                            //Bỏ đoạn cũ  này, để dùng sang fulltext search:
//                            if(!isSupperAdmin_()){
//                                $dataRet = $dataRet->where(
//                                    function($query) use ($field5, $val){
//                                        $query->orWhere($field5, "=", $val)->orWhere($field5, "LIKE", "$val,%")->orWhere($field5, "LIKE", "%,$val,%")->orWhere($field5, "LIKE", "%,$val");
//                                    }
//                                );
//                            }else
                            {
//                                if(isSupperAdmin_()){
//                                    die("xxx2");
//                                }

                                //        $query->whereRaw("MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)", $this->fullTextWildcards($term));
                                $dataRet = $dataRet->whereRaw("MATCH $field5 AGAINST (? IN BOOLEAN MODE)", $val);
                            }
                        }
                        else{

                            if($objMeta->isStatusField($field) && $val == 0){
                                $dataRet = $dataRet->where(
                                    function($query) use ($field5, $val){
                                        $query->orWhere($field5, "=", $val)->orWhere($field5, "=", null);
                                    }
                                );
                            }
                            else{


                                //Nếu là string thì search LIKE
                                //Todo:
                                //Chú ý về DOS, DDOS...
                                if($objMeta->data_type_in_db == 'string' || $objMeta->data_type_in_db == 'text' || $objMeta->data_type_in_db == 'datetime'){
                                    $dataRet = $dataRet->where($field5, "LIKE", "%$val%");
                                }
                                else
                                    $dataRet = $dataRet->where($field5,$val);
                            }


//                            die(" xx  $val  / $field / $optSearch");

                        }
                    }
                }
            }


//            if(isDebugIp())
            {
                $txt = $params['full_search_join'] ?? null;
                $mFieldJoinSearch = $objMeta->getFullSearchJoinField();
                if($txt && $mFieldJoinSearch)
                $dataRet = $dataRet->where(
                    function($query) use ($txt, $mFieldJoinSearch){
                        foreach ($mFieldJoinSearch AS $field1){
                            $query->orWhere($field1, "LIKE", "%$txt%");
                        }
                    }
                );

//                echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                print_r($mJoinField);
//                echo "</pre>";
//                die();
            }

            //Nếu có thêm sort:
            if(str_starts_with($param, DEF_PREFIX_SORTBY_URL_PARAM_GLX)){
                $sname = substr($param, strlen(DEF_PREFIX_SORTBY_URL_PARAM_GLX));


                $sname = strip_tags($sname);

                $field = $objMeta->getFieldFromShortName($sname);

                if(isDebugIp()){
//                    die("SNAME = $sname / $field");
                }


                if (!$field)
                    continue;
                if(!$objMeta->isShowIndexField($field, $gid))
                    continue;
                if(!isset($mMetaAll[$field]))
                    loi("Not found key meta: $field");


                $field5 = $field;
                if($mJoinField){
                    foreach ($mJoinField AS $alias=>$mInfo){
                        if($alias == $field){
                            $field5 = $mInfo['table'].'.'.$mInfo['field'];
                            break;
                        }
                    }
                }

                $objMeta = $mMetaAll[$field];

                if ($objMeta->isSortAbleField($field, $gid)) {

                    if(isDebugIp()){
//                        die($field5);
                    }
                    $haveSort = 1;
//                    $sname = str_replace(DEF_PREFIX_SORTBY_URL_PARAM_GLX, '', $param);
//
//                    $field = $objMeta->getFieldFromShortName($sname);
//                    if (!$field)
//                        continue;
                    //$dataRet = $dataRet->orderBy($tblName.".".$field5, $val);
                    $dataRet = $dataRet->orderBy($field5, $val);
                }
            }

            //Nếu là join thì sẽ thay đổi val???
            if($joinRelate = $objMeta->join_relation_func){
                $val = $this->$joinRelate;
            }
        }


        if($latest) {
            if(!$haveSort)
                //Nếu có sort thì sẽ ko lastest nữa
                $dataRet = $dataRet->orderBy($tblName.'.id', 'desc')->paginate($nPerPage);
            else
                $dataRet = $dataRet->paginate($nPerPage);
        }
        else
            $dataRet = $dataRet->paginate($nPerPage);

        $objMeta->excuteAfterQueryIndex($dataRet);

        if(isDebugIp()){

//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r(($mMetaAll));
//            echo "</pre>";

            \clsDebugHelper::$lastQuery = DB::getQueryLog();

//            dump(DB::getQueryLog());
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($dataRet->toArray());
//            echo "</pre>";

        }

//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r(($mMetaAll));
//        echo "</pre>";
//        die();
        //Nếu API thì sẽ trả lại các field mở rộng tương ứng:
//        if($objPr->is_api)
        {


            foreach ($dataRet->items() AS $obj){

                if(isDebugIp())
                {
//                    $obj->id = 1;
                }

                foreach ($mMetaAll AS $field=>$objMeta){


                    if(!$objMeta->isShowIndexField($field, $gid))
                        continue;
                    if($objMeta->isPassword($field)){
                        $obj->$field = null;
                    }


//                    echo "<br/>\n $field";
//                    $fieldEx = "_".$field;
//                    //Nếu có join function
//                    if(0)
//                    if($funcJoin = $objMeta->checkJoinFuncExistAndGetName())
//                    {
//                        /////Trả về đúng như UI cần để hiển thị
//                        //Ví dụ Tag thì có mảng ID->Name
//                        //Ảnh thì có ID->Link ảnh
//                        $obj->$fieldEx = $objMeta->callJoinFunction($obj, $obj->$field, $field);
//                    }

                    //các trường mở rộng kiểu 1, nối _ vào tên trường trong db
                    /////Trả về đúng như UI cần để hiển thị
                    //Ví dụ Tag thì có mảng ID->Name
                    //Ảnh thì có ID->Link ảnh
                    $fieldEx = "_".$field;
                    //$obj->$fieldEx = $objMeta->callJoinFunction($obj, $obj->$field, $field);

                    if(method_exists(get_class($objMeta), $fieldEx)) {


//                        if($fieldEx== '_parent_id')
//                            continue;
                        if(isIPDebug() || isCli()) {
//                            if ($fieldEx == '_name')
//                                continue;
//                            if ($fieldEx == '_id')
//                                continue;
                        }
//                        if($fieldEx== '_file_size')
//                            continue;



                        $obj->$fieldEx = $objMeta->$fieldEx($obj, $obj->$field, $field);
                        if(isIPDebug() || isCli()){
//                        echo ("\n\n Field OK = $fieldEx\n\n");
//                            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                            print_r($obj->toArray());
//                            echo "</pre>";
                        }
                    }

                    if(isIPDebug() || isCli()){
//                        echo ("\n\n Field OK = $fieldEx\n\n");
//                        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                        print_r($obj->toArray());
//                        echo "</pre>";
                    }
                    //Trường hợp method mở rộng, có gạch chân ở dưới, không có trường trong db:
                    //Kết hợp luôn join function:

                    if($field[0] == '_'){
                        if(method_exists(get_class($objMeta), $field)){
                            $obj->$field = $objMeta->$field($obj, $obj->$field, $field);
//                            $obj->$field ='xxx1';
                        }
                        else{
//                            $obj->$field ='xxx';
                        }
                    }


                    if(method_exists(get_class($objMeta), "get__$field")){
                        $mt = "get__$field";
                        $obj->$field = $objMeta->$mt($obj->id, $params);
                    }

                    //có lúc bị gắn joinfunc Eloquen value vào thuộc tính của obj, thì gỡ ra
                    if($objMeta->join_relation_func)
                        unset($obj->{$objMeta->join_relation_func});


                }
            }
        }

        if(isIPDebug() || isCli()){
//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($dataRet->toArray());
//            echo "</pre>";
        }

//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($dataRet->items());
//        echo "</pre>";
//
//        die('xxx');

        {

            if(isDebugIp()){

//                dump(DB::getQueryLog());

//                echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                dump($dataRet);
//                echo "</pre>";
            }
        }

        return $dataRet;
    }

    //Kiểm tra ID là con của 1 id khác
    function checkIdIsChildsOfPid($id, $pid, &$mAllData)
    {
        if ($pid == 0)
            return 1;
        //Kiểm tra xem nếu $m1 không thuộc pid thì bỏ qua
        for ($i = 0; $i < 1000; $i++) {
            foreach ($mAllData as $m2) {
                if ($m2['id'] == $id) {
                    $id = $m2['parent_id'];
                    if ($id == 0)
                        return 0;
                    if ($id == $pid) {
                        return 1;
                    }
                }
            }
        }
    }

    /**
     * Lấy ra mảng id, và name (email...) để phục vụ test auto select...
     * @return array
     */
    static function getIdAndNameNotEmptyForTest($limit = 5, $field = 'name'){
        $mm = self::limit($limit)->get();
        $ret = [];
        foreach ($mm AS $obj){
            if($obj->$field)
                $ret[$obj->id] = $obj->$field;
        }
        return $ret;
    }

    /**
     * @param $param
     * $param['_get_raw_data'] =
     * @param $objParam
     *
     *
     *
     * @return array|mixed
     * @throws \Exception
     */
    function queryIndexTree($param, $objParam){

        $objMeta = $this::getMetaObj();
        $mMeta = $objMeta->getMetaDataApi();

        if (isset($mMeta['user_id']))
            $objParam->setUidIfMust();
//        die("UID: " . Auth::user()->id);
//

//        die();

//        DB::enableQueryLog();
        //$dataRet = $dataRet->orderBy($field, $val);
        $pid0 = $pid = 0;

        if ($tmp = $param['pid'] ?? null)
            $pid0 = $pid = $tmp;

        $cls = get_class($this);
        $dataGet = new $cls;

        //Chống scan id:
        if($objMeta->isUseRandId() && $pid && is_numeric($pid)){
            loi("Not valid pid, is must rand number ($pid)");
        }

        if($pid){

            if(!is_numeric($pid) && strlen($pid >= 8)){
                $pid = ClassRandId2::getIdFromRand($pid);
            }

            $parentObj = $dataGet::find($pid);
            if(!$parentObj){
                loi("Not found obj2: $pid0");
            }
        }

        $withBrother = 0;
        if(isset($param['include_brother']))
            $withBrother = 1;


        $getAll = $param['get_all'] ?? 0;


        if (isset($mMeta['user_id']))
            if ($objParam->need_set_uid){

                if($pid){

                    //nếu có rằng buộc user_id, thì phải kiểm tra có thuộc uid không:
                    if(!$objParam->ignore_check_userid)
                    if($parentObj->user_id != $objParam->need_set_uid){
                        loi("Not your data: $pid0");
                    }
                }

                $dataGet = $dataGet::where("user_id", $objParam->need_set_uid);

            }

        if (!$objParam->set_gid) {
            loi("Not set GID!");
        }


        $haveOrderParam = 0;
        if (isset($param['order_by'])) {
            $orderByField = $param['order_by'];
            if ($orderByField) {
                if (!isset($param['order_type'])) {
                    $dataGet = $dataGet->orderBy($orderByField, 'ASC');
                    $haveOrderParam = 1;
                } else {
                    if ($param['order_type'] && ($param['order_type'] == 'ASC' || $param['order_type'] == 'DESC')) {
                        $dataGet = $dataGet->orderBy($orderByField, $param['order_type']);
                        $haveOrderParam = 1;
                    }
                }
            }
        }


        if (!$haveOrderParam) {
            $mm = $dataGet->orderBy('name', 'ASC');
        }

        //Lấy hết kết quả
        if ($getAll){
            if($objParam->need_set_uid > 0)
                $mm = $dataGet->limit(1000)->where('user_id',$objParam->need_set_uid)->get()->toArray();
            else
                $mm = $dataGet->limit(1000)->get()->toArray();
        }
        elseif (isset($param['get_tree_all'])){
            $mm = $this->getAllTreeDeep($pid);
            if($withBrother){
            }
        }
        else {
            //Chỉ lấy của pid hiện tại
            $mm = $dataGet->limit(1000)->where('parent_id', $pid)->get()->toArray();
        }

        if(isset($param['_get_raw_data'])){
            return $mm;
        }

        $ret = [];
        //Duyệt lại hết data, để điền trường has_child, và trả lại các Field mà index show
        foreach ($mm as $m1) {


            if ($pid && $getAll){


                //Bỏ qua các con cháu không thuộc PID gốc
                if (!$this->checkIdIsChildsOfPid($m1['id'], $pid, $mm)){

                    //Nếu lấy anh em
                    if($withBrother){

                        //Nếu ko phải các obj có cùng parent thì mới bỏ qua, lấy cả obj PID
                        if($m1['parent_id'] == $parentObj->parent_id || $m1['id'] == $pid){

                        }else
                            continue;
                    }
                    else
                        continue;
                }

            }



            $hasChild = 0;
            if ($objParam->need_set_uid && isset($mMeta['user_id']))
                $mm2 = $this::where('user_id', $objParam->need_set_uid)->where('parent_id', $m1['id'])->first();
            else
                $mm2 = $this::where('parent_id', $m1['id'])->first();
            if ($mm2)
                $hasChild = 1;

            $mRet = [];

            foreach ($mMeta as $field => $objMeta) {
                if ($objMeta->isShowIndexField($field, $objParam->set_gid)) {

                    if($field[0] == '_'){

//                        $obj = new $model;
                        $obj = $this::find($m1['id']);
//                        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                        print_r($obj);
//                        echo "</pre>";
//                        die();
                        if(method_exists(get_class($objMeta), $field)){
//                            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                            print_r($objMeta);
//                            echo "</pre>";
                            $fieldOrg = substr($field,1);
                            $ret0 = $objMeta->$field($obj, ($obj->$fieldOrg), $field);
//                            die("xxx1  $fieldOrg / " . substr($obj->$field,1)." -" . $ret);
//                            $obj->$field = $objMeta->$field($obj, $obj->$field, $field);
                            $mRet[$field] = $ret0;
//                            $obj->$field ='xxx1';
                        }
                        else{
//                            $obj->$field ='xxx';
                        }
                    }
                    elseif(method_exists(get_class($objMeta), "get__$field")){
                        $mt = "get__$field";
                        $mRet[$field] = $objMeta->$mt($m1['id'], $param);
                    }else{
                        //tạm xóa xóa trường created_at, deleted_at nếu có, đỡ nhiều data
                        if($field != 'deleted_at')
                            $mRet[$field] = $m1[$field];
                    }
                }
            }





//            if(isset($m1['user_id']))
//                $mRet['user_id'] = $m1['user_id'];

            $mRet['parent_id'] = $m1['parent_id'];

            $mRet['has_child'] = $hasChild;
            $mRet['name'] = $m1['name'];

            if($objMeta->isUseRandId()) {
                $mRet['id'] = ClassRandId2::getRandFromId($m1['id']);
                $mRandRield = $objMeta->getRandIdListField();
                if($mRandRield)
                foreach ($mRandRield AS $rField){
                    if(isset($m1[$rField]) && $m1[$rField])
                        $mRet[$rField] = ClassRandId2::getRandFromId($m1[$rField]);
                }
            }
            else
                $mRet['id'] = $m1['id'];


            $mRet['_public_link'] = $objMeta->getPublicLink($mRet['id']);

            //Nếu có xly RAND thì cần mã hóa RAND:

//            $ret[] = ['id' => $m1['id'], 'parent_id' => $m1['parent_id'], 'has_child' => $hasChild, 'name' => $m1['name']];
            $ret[] = $mRet;
        }

        //Bổ xung thêm thông tin Ex với tên của PID hiện tại
        $payloadEx = null;
        if ($pid) {
//            die('xxxx');
            if ($obj = $dataGet->where('id', $pid)->first()) {
                $payloadEx = ['name' => $obj->name, 'parent_id' => $obj->parent_id];
            }
        }

        $qr = '';

//        $qr = DB::getQueryLog();
//
//        echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//        print_r($qr);
//        echo "</pre>";
//
//        die();

        return [$ret, $payloadEx];

//
    }

    function queryGetOne($id, clsParamRequestEx $objParam){

        //Todo: kiểm tra nếu thuộc UID
        if(isDebugIp()) {
            \clsDebugHelper::$lastQuery = DB::getQueryLog();
        }
        try {


            $objMeta = $this::getMetaObj();
            if ($objMeta instanceof MetaOfTableInDb) ;

            $mMeta = $objMeta->getMetaDataApi();

            if ($objMeta->isUseRandId()) {
                if (!is_numeric($id)) {
                    $id = ClassRandId2::getIdFromRand($id);
                }
            }

            if (!is_numeric($id)) {
                return rtJsonApiError("Need input Id number!");
            }


            if (isset($mMeta['user_id']))
                $objParam->setUidIfMust();
            $tblName = $objMeta->table_name_model;
//            dump($objParam);
//

//            echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//            print_r($objParam);
//            echo "</pre>";
////            die();
//
            $gid = $objParam->set_gid;

            DB::enableQueryLog();

            $mFieldShow = $objMeta->getShowGetOneAllowFieldList($gid, 0);

            $mFieldShowWithEx = $objMeta->getShowGetOneAllowFieldList($gid, 1);

            $kJoinField = null;
            if($mJoinField = $objMeta->getJoinField())
                $kJoinField = array_keys($mJoinField);

//            if(isDebugIp())
            {
                for ($i = 0; $i< count($mFieldShow); $i++) {
                    $field = $mFieldShow[$i];
                    $mFieldShow[$i] = $tblName.".".$field;
                    foreach ($mJoinField as $alias => $mInfo) {
                        if ($alias == $field) {
                            $mFieldShow[$i] = $mInfo['table'] . '.' . $mInfo['field'];
                            break;
                        }
                    }
                }
            }

            if (isset($mMeta['user_id']) && $objParam->need_set_uid){
//                echo "\nxxx1";
                $qr = $this->select($mFieldShow)->where('user_id', $objParam->need_set_uid);

                $obj = $qr->find($id);
            }
            else{
//                echo "\nxxx2";
                $qr = $this->select($mFieldShow);
                if($mJoinField){
                    foreach ($mJoinField AS $alias=>$mInfo){
                        $tblJ = $mInfo['table'];
                        $qr->leftJoin($mInfo['table'],
                            $tblName.".".$mInfo['field_local'], '=',$tblJ.'.'.$mInfo['field_remote'])
                            ->addSelect($tblJ.'.'.$mInfo['field'], $tblJ.'.'.$mInfo['field'].' as '.$alias);
                    }
                }
                $obj = $qr->find($id);
            }


//            echo "\n TK API = " . request()->bearerToken();;
//            echo "\n FileID =  $obj->id , UID = $objParam->need_set_uid";
//            echo "\n";
//            return("---xxxxxx");
            if (!$obj)
                if($objParam->return_laravel_type)
                    return null;
                else
                    return rtJsonApiError("Not found item '$id' of " . basename($this::class));
//            $obj = $dataRet;


//                echo "<pre> >>> " . __FILE__ . "(" . __LINE__ . ")<br/>";
//                print_r(array_keys($mMeta));
//                echo "</pre>";
//                die();

            //if($objParam->is_api)
            {
                //foreach ($dataRet->items() AS $obj)
                {
                    foreach ($mMeta as $field => $objMeta) {

//                        echo "<br/>\n $field ...0";
                        if (!$objMeta->isShowGetOne($field, $gid))
                            continue;

                        if ($objMeta->isPassword($field)) {
                            $obj->$field = null;
                        }


                        //các trường mở rộng kiểu 1, nối _ vào tên trường trong db
                        /////Trả về đúng như UI cần để hiển thị
                        //Ví dụ Tag thì có mảng ID->Name
                        //Ảnh thì có ID->Link ảnh
                        $fieldEx = "_" . $field;
                        //$obj->$fieldEx = $objMeta->callJoinFunction($obj, $obj->$field, $field);
                        if (method_exists(get_class($objMeta), $fieldEx)) {
                            $obj->$fieldEx = $objMeta->$fieldEx($obj, $obj->$field, $field);
                        }

                        //các trường mở rộng kiểu 2, ko có trong db:
                        if ($field[0] == '_')
                            if (method_exists(get_class($objMeta), $field)) {
                                $obj->$field = $objMeta->$field($obj, $obj->$field, $field);
                            }

                        //có lúc bị gắn joinfunc Eloquen value vào thuộc tính của obj, thì gỡ ra
                        if ($objMeta->join_relation_func)
                            unset($obj->{$objMeta->join_relation_func});
                    }
                }
            }

//            die('xxxx');

            $qr = null;
            if(isDebugIp()){
            //Bảo mật: nếu ko phải debug thì cần bỏ cái này, đi, ví dụ ifHasAdmin Token
                $qr = \clsDebugHelper::$lastQuery = DB::getQueryLog();
            }
            //Trả lại kiểu laravel, là đối tượng Paginator, được serialize, mục đích để nếu dùng Laravel View có thể lấy đối tượng Pagniator để sử dụng
            if (\request()->get("return_laravel_type") == 1)
                return rtJsonApiDone(serialize($obj), null, 1, $qr);

            if($objParam->return_laravel_type)
                return $obj;

            //Nếu API thông thường, ko cần return laraveltype, thì ko cần serialize
            return rtJsonApiDone($obj, null, 1, $qr);

        } catch (\Throwable $e) {
            return rtJsonApiError($e->getMessage() ."\n". $e->getTraceAsString());
        }
    }
}

