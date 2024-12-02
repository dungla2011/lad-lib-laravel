<?php

namespace LadLib\Laravel\Database;

use App\Models\DemoFolderTbl;
use App\Models\ModelGlxBase;
use Base\ClassString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LadLib\Common\cstring2;
use LadLib\Common\Database\MetaOfTableInDb;
use function Clue\StreamFilter\fun;

trait TraitModelTree
{


    /** //Lấy cả bố
     * @param $id
     * @param null $mField
     * @return array
     */
    function getAllTreeDeep($pid, $mField = null, $getLevel = null){
        if(!$obj = $this->find($pid))
            loi("Not found $pid!");
        $opt = null;
        if($getLevel)
            $opt = ['get_level'=>1];

        $mm = $this->getChildRecursive($pid, $mField, $opt);

        $mObj = $obj->toArray();

        if($getLevel)
            $mObj['level'] = 1;
        array_unshift($mm, $mObj);
        return $mm;
    }

    function getName(){
        return strip_tags($this->name);
    }

    function checkIsLoopParent(){

        $pid = $this->parent_id;
        if(!$pid)
            return 0;
        $mId = [$this->id];
        while (1){
            if($obj = $this->where('id', $pid)->first(['id', 'parent_id'])){
                if(in_array($obj->id, $mId))
                    return 1;
                $mId[] = $obj->id;
                $pid = $obj->parent_id;
                if(!$pid)
                    break;
            }
            else
                break;
        }
        return 0;
    }

    function getBreakumPathHtml($limitChar = 0, $skipTo = 0, $seperator = '/'){
        $mObj = $this->getListParentId(1,1);

        $mObj = array_reverse($mObj);
        $html = "<div class='brc_path'>";
        $tt = count($mObj);
        $cc = 0;
        foreach ($mObj AS $obj){
            $cc++;
            if($skipTo && $skipTo >= $cc){
                continue;
            }

            if($obj instanceof ModelGlxBase);
            $link = $obj->getLinkPublic();


            $name0 = $name = $obj->name = strip_tags($obj->name);

            if($limitChar)
                $name = cstring2::substr_fit_char_unicode($name, 0, $limitChar, 1);

            $html .= " <span> <a data-code-pos='ppp16899462192601' class='brc_link' title='$name0' href='$link'>$name</a> </span>";
            if($cc < $tt )
                $html .= " <span class='seperate'> $seperator </span> ";
        }
        $html .= "</div>";
        return $html;
    }

    /**
     * @param string[] $mField
     * @param int $inCludeThis
     * OPT  get_obj
     * @return array
     */
    //Hàm này chạy tốt hơn hàm bên dưới : getFullPathParentObj
    //Có chứa cả parent folder
    function getListParentId($inCludeThis = 0, $getObj = 0){

        $mId = [];
        if($inCludeThis){
            if($getObj){
                $mId = [$this];
            }else
                $mId = [$this->id];
        }
        $pid = $this->parent_id;
        if(!$pid)
            return $mId;

        $meta = $this::getMetaObj();

        $cc = 0;
        while (1){
            if(!$pid)
                break;
            $cc++;
            if($cc > 30)
                loi("Error loop parent_id, over 30 level?");
            usleep(1);
            //Nếu có định nghĩa parent thì có thể lấy theo parent
            if($meta::$folderParentClass)
                $obj = $meta::$folderParentClass::where('id', $pid)->first();
            else
                $obj = $this->where('id', $pid)->first();

            if($obj)
            {
                if($getObj){
                    //Kiểm tra loop hay không
                    foreach ($mId AS $objx1){
                        //Phải thêm cả class mới xác định trùng ok
                        if($objx1->id == $obj->id && $objx1::class == $obj::class) {
                            return $mId;
                        }
                    }
                }
                else
                    //Chỗ này có thể có lỗi vd: ID news trùng Id của FolderNews
                if(in_array($obj->id, $mId)) {
                    return $mId;
                }
                if($getObj)
                    $mId[] = $obj;
                else
                    $mId[] = $obj->id;
                $pid = $obj->parent_id;
                if(!$pid)
                    break;
            }
            else
                break;
        }
        return $mId;
    }

    /**
     * $mField = ['id','parent_id']
     * @param $id
     * @param null $mField
     * @return array
     */
    function getChildRecursive($id = null, $mField = null, $opt = null){

        if(isset($opt['get_level'])){
            if(!$opt['get_level'])
                $opt['get_level'] = 1;
            else
                $opt['get_level']++;
        }

        if(!$id)
            $id = $this->id;

        if($mField)
            $mm = $this->where('parent_id', $id)->get($mField)->toArray();
        else
            $mm = $this->where('parent_id', $id)->get()->toArray();

        if($mm)
        foreach ($mm AS $key => $objXX){
            if(isset($opt['get_level']))
            if($opt['get_level']){
                $mm[$key]['level'] = $opt['get_level'];
            }
            //Kiểm tra obj bị loop parent hay không:
            $obj0 = $this->find($mm[$key]['id']);
            if($obj0 instanceof ModelGlxBase);
            //nếu bị loop thì dừng luôn
            if($obj0->checkIsLoopParent())
                return $mm;
            $m1 = $this->getChildRecursive($mm[$key]['id'], $mField, $opt);
            if($m1)
                $mm = array_merge($mm, $m1);
        }

        return $mm;
    }

    function countChild(){
        $ret = $this->getChildRecursive($this->id, ['id','parent_id', 'name']);
        if(is_array($ret))
            return count($ret);
        return 0;
    }

    /**
     * @param int $getIdOrNameArray = 1: get id , =2: get name
     * @return ModelGlxBase[]
     */
    //Nên thay = hàm bên trên getListParentId
    function getFullPathParentObj($getIdOrNameArray = 0){
        //Limit 100 only
        $ret = [];
        $pid = $this->id;
//        dump($this);
//        //dump(" THIS ID = $this->id " );
//        return;

        $mPidTest = [];
        for($i = 0; $i< 100; $i++){
            if($obj = $this->find($pid)){

//                dump($obj);
                $m1 = $obj->toArray();

                //Kiểm tra loop parent
                if(in_array($m1['id'], $mPidTest))
                    break;

                $mPidTest[] = $m1['id'];

                if($getIdOrNameArray == 1){
                    $ret[] = $m1['id'];
                }
                elseif($getIdOrNameArray == 2){
                    $ret[] = @$m1['name'];
                }
                else{
                    $ret[] = $obj;
                }



                $pid = @$m1['parent_id'];
//                dump( " PID = $pid ");
            }
            else
                break;
        }
        $ret = array_reverse($ret);
        return $ret;
    }


}

