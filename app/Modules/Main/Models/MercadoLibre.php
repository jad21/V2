<?php
namespace App\Modules\Main\Models;

use App\Core\Models\CoreModel;
class MercadoLibre extends CoreModel
{

    public function getTemplateDefault()
    {
        $this->table("yaxaws_mercadolibre_template");
        return $this->where("default",1)->first();
        // return DB::table("yaxaws_mercadolibre_template")->where("default", 1)->first();
    }
    public function saveTemplate($data)
    {
        $this->table("yaxaws_mercadolibre_template");
        $this->find($data["id"]);
        $this->html = $data["html"];
        return $this->save();
        // return $this->updateData([ "html"=> $data["html"] ],["id"=>$data["id"]]);
        // return  DB::table("yaxaws_mercadolibre_template")
        //                 ->where("id",$data["id"])
        //                 ->update([ "html"=> $data["html"] ]);
    }

    public function getBySku($sku)
    {
        $this->table("yaxaws_mercadolibre_mc2");
        return $this->where("sku",$sku)->first();
        // return DB::table("yaxaws_mercadolibre_mc2")->where("sku", $sku)->first();
    }

    public function saveInYaxaws($data)
    {
        $this->table("yaxaws_mercadolibre_mc2");
        $this->sku = $data["sku"];
        $this->ml_id = $data["ml_id"];
        $this->url_mc2 = $data["url_mc2"];
        $this->url_ml = $data["url_ml"];
        return $this->create();
        // return DB::table("yaxaws_mercadolibre_mc2")->insert([
        //     "sku"     => $data["sku"],
        //     "ml_id"   => $data["ml_id"],
        //     "url_mc2" => $data["url_mc2"],
        //     "url_ml"  => $data["url_ml"],
        // ]);
    }

    
}
