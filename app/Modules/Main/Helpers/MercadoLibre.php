<?php
namespace App\Modules\Main\Helpers;

use App\Core\Rest\Api;

class MercadoLibre extends Api
{
    protected $base_ws = "http://yaxaws.com/MercadoLibre/API/";

    protected $app_id = "1127372430811135";//prod
    // protected $app_id = "4502282040261443";//test china

    public function getCategoryAttributes($category_id)
    {
        // https://api.mercadolibre.com/categories/MCO155478/attributes
        // Category attributes
        $res =  $this->get("https://api.mercadolibre.com/categories/{$category_id}/attributes");
        if (isset($res->error)) {
            return null;
        }
        return $res;
    }

    public function getCategoryData($category_id)
    {
        // https://api.mercadolibre.com/categories/
        $res =  $this->get("https://api.mercadolibre.com/categories/{$category_id}");
        if (isset($res->error)) {
            return null;
        }
        return $res;
    }

    public function publish($prod)
    {
        // http://yaxaws.com/MercadoLibre/API/items/publish/4502282040261443/
        $app_id = $this->app_id;
        $return = $this->_post("items/publish/{$app_id}/", ["item" => json_encode($prod)]);
        return $return;
    }
    public function predictionCategory($txt)
    {
        // http://yaxaws.com/MercadoLibre/API/category/prediction?index=Harry%20Potter%20camiseta
        $r = $this->get("category/prediction", ["index" => $txt]);
        if (isset($r->error)) {
            return null;
        }
        return $r;
    }
    public function _post($url='',$data,$encoding = self::ENCODING_QUERY)
    {
        return $this->post($url,$data,$encoding);
    }
    

}
