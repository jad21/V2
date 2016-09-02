<?php
namespace App\Modules\Main\Models;

use Carbon\Carbon;
use App\Core\Models\CoreModel;

class MagentoModel extends CoreModel
{
    protected $connection_name = "mc2";
    protected $table = "";
       
    public function getCategoryEntity($urlPath)
    {
        $this->table("catalog_category_entity_varchar");
        return $this->where("value",$urlPath)->first();
        // $sql = "SELECT * FROM {$table} where value = :url limit 1";
        // $res = $this->query($sql,["url"=>$urlPath]);
        // if (count($res)>0) {
        //     return $res[0];
        // }
        // return null;
    }




    public function getAttribute($attribute_code)
    {
        // select * from eav_attribute where attribute_code = "color"
        $this->table("eav_attribute");
        return $this->where("attribute_code",$attribute_code)->first();
        // $sql = "SELECT * FROM {$table} where attribute_code = :value limit 1";
        // $res = $this->query($sql,["value"=>$attribute_code]);
        // if (count($res)>0) {
        //     return $res[0];
        // }
        // return null;
        // return DB::connection('mysql_mc2.1')->table("eav_attribute")
        //     ->where("attribute_code", "=", $attribute_code)->first();
    }
}
