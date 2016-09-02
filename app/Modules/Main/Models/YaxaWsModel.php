<?php
namespace App\Modules\Main\Models;

use App\Core\Models\CoreModel;
class YaxaWsModel extends CoreModel
{
    public function getToken($user,$ip="127.0.0.1")
    {
        return $this->table("yaxaws_mce_token")
            ->where("ip_origin", $ip)
            ->where("user", $user)
            ->first();
    }
    public function insertToken($data)
    {
        return $this->table("yaxaws_mce_token")->create($data);
    }
    public function updateToken($data,$where)
    {
        
        $this->table("yaxaws_mce_token");
        $this->where("ip_origin",$where["ip_origin"]);
        $this->where("user",$where["user"]);
        $r = $this->update($data);
        return $r;
    }
    public function saveProductQueue($prod)
    {
        $res = $this->table("queue_product_amazon")->where("sku", $prod["product"]["sku"])->first();

        if (!is_null($res)) {
            return $this->table("queue_product_amazon")
                        ->where("sku", $prod["product"]["sku"])
                        ->update([
                            "sku"  => $prod["product"]["sku"],
                            "json" => json_encode($prod),
                        ]);
        }
        return $this->table("queue_product_amazon")->create([
            "sku"  => $prod["product"]["sku"],
            "json" => json_encode($prod),
        ]);

    }
    public function getProductQueueBySku($sku)
    {
        return $this->table("queue_product_amazon")->where("sku", $sku)->first();
    }
    public function getProductQueueById($id)
    {
        return $this->table("queue_product_amazon")->find($id);
    }
    public function getProductQueueMl()
    {
        return $this->table("queue_product_amazon")->where("status_ml", 0)->first();
    }
    public function setPendingProductQueueMl($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_ml" => 1]);
    }
    public function setCompleteProductQueueMl($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_ml" => 2]);
    }
    public function getProductQueueMC2()
    {
        return $this->table("queue_product_amazon")->where("status_mc2", 0)->first();
    }
    public function setPendingProductQueueMC2($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_mc2" => 1]);
    }
    public function setCompleteProductQueueMC2($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_mc2" => 2]);
    }
    public function markErrorProductQueueMC2($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_mc2" => -1]);
    }
    public function markErrorProductQueueML($id)
    {
        return $this->table("queue_product_amazon")->where("id", $id)->update(["status_ml" => -1]);
    }
}
