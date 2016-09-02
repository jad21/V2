<?php
namespace App\Modules\Main\Helpers;

use App\Core\Rest\Api;

class YaxaWs extends Api
{
    protected $base_ws = "http://yaxaws.com/aws/";
    // protected $base_ws = "http://yaxaws.intra/aws/";
    protected $intento_RequestThrottled = 0;
    protected $limit_RequestThrottled = 1000;
    protected $email = "amazonisnotfake5@cuvox.de";
    public function test()
    {
        return $this->get();
    }

    public function item($ItemId, $IdType = "ASIN")
    {
        // products/item?ItemId=B00005NVT1&email=amazonisnotfake4@cuvox.de&ResponseGroup=Large,EditorialReview,Offers,Variations,VariationImages
        $res = $this->get("products/item", [
            "IdType"        => $IdType,
            "ItemId"        => $ItemId,
            "email"         => $this->email,
            "ResponseGroup" => "Large,EditorialReview,Offers,Variations,VariationImages",
        ]);
        if ($this->validError($res)) {
            return $this->item($ItemId, $IdType);
        }
        return $res;
    }

    public function search($index, $keywords, $current_page = 1)
    {

        // products/search?index=Books&email=amazonisnotfake4@cuvox.de&Keywords=harry&&ResponseGroup=Large,EditorialReview,Offers,Variations,VariationImages&Sort=salesrank
        // http://yaxaws.com/aws/products/search?index=Apparel&email=amazonisnotfake4@cuvox.de&Keywords=rola play costume&&ResponseGroup=Large,EditorialReview,Offers,Variations,VariationImages&Sort=salesrank
        $res = $this->get("products/search", [
            "index"         => $index,
            "Keywords"      => $keywords,
            "email"         => $this->email,
            "ResponseGroup" => "Large,EditorialReview,Offers,Variations,VariationImages",
            "VariationPage" => $current_page,
            "Sort"          => $this->buildSort($index),
        ]);
        if ($this->validError($res)) {
            return $this->search($index, $keywords, $current_page);
        }
        return $res;
    }
    private function buildSort($index = '')
    {
        $sort = "None";
        switch ($index) {
            case 'UnboxVideo':   case 'Appliances':   case 'MobileApps':
            case 'ArtsAndCrafts':case 'Automotive':   case 'Books':
            case 'Fashion':      case 'FashionBaby':  case 'FashionGirls':
            case 'FashionBoys':  case 'FashionMen':   case 'FashionWomen':
            case 'Collectibles': case 'MP3Downloads': case 'GiftCards':
            case 'Grocery':      case 'KindleStore':  case 'Luggage':
            case 'Movies':       case 'LawnAndGarden':case 'PetSupplies':
            case 'Pantry':       case 'SportingGoods':case 'Wine':
            case "Apparel":      case "Jewelry":
                $sort = "relevancerank";
                break;
            case "HomeGarden":case "Watches":case "Kitchen":
            case "Toys":case "OfficeProducts":case "Baby":
            case "Beauty":case "Electronics":case "HealthPersonalCare":
            case "Industrial":
                $sort = "salesrank";
                break;
        }
        return $sort;
    }
    private function validError($res)
    {
        if (isset($res->Error->Code) and $res->Error->Code == "RequestThrottled" and $this->intento_RequestThrottled <= $this->limit_RequestThrottled) {
            $this->intento_RequestThrottled++;
            sleep(10);
            return true;
        }
        if (isset($res->body->b) and $res->body->b == "Http/1.1 Service Unavailable") {
            sleep(10);
            return true;
        }
        return false;
    }

    public function getSearchIndices()
    {
        // amazon/getSearchIndices
        return ["Apparel", "Automotive", "Baby", "Books", "DVD", "Electronics", "ForeignBooks", "KindleStore", "Kitchen", "MobileApps", "MP3Downloads", "Music", "Shoes", "Software", "Toys", "Video", "VideoGames", "Jewelry", "Watch", "Watches"];
        return $this->get("amazon/getSearchIndices");
    }
    private $intento_trans = 0;
    private function _trans($txt)
    {
        $return = $this->get("http://yaxaws.com/API/V1/Translate/bingtranslate", ["inputStr" => $txt]);
        if (isset($return->response)) {
            $this->intento_trans = 0;
            return $return->response;
        } else if ($this->intento_trans < 22) {
            $this->intento_trans++;
            if (is_string($txt)) {
                return $this->trans($txt);
            }
        } else {
            throw new \Exception("Error Al traducir .".json_encode($return), -10);
        }

    }
    public function trans($txt)
    {
        // TranslateController
        if (is_string($txt)) {
            if (strlen($txt) > 1000) {
                $str_array = explode(PHP_EOL, $txt);
                $txt_res = "";
                foreach ($str_array as $word) {
                    $txt_res .= $this->_trans($word);
                }
                return $txt_res;
            } else {
                return $this->_trans($txt);
            }
        }else{
            return $txt;
        }
    }

    public function generatePrice($precio)
    {
        // https://yaxaws.com/aws/prices/generate?cost=45
        $r = $this->get("prices/generate", ["cost" => $precio]);
        if (isset($r->error)) {
            return null;
        }
        return $r;
    }

    public function trmCop()
    {
        // http://yaxaws.com/API/V1/trm/cop
        $base_ws       = $this->base_ws;
        $this->base_ws = "http://yaxaws.com/API/V1/trm/";
        $return        = $this->get("cop");
        $this->base_ws = $base_ws;
        return $return;
    }

    public function calculatePrice($usd)
    {
        $trm            = $this->trmCop();
        $generatedPrice = $this->generatePrice((float) $usd);

        $copCost = (float) $generatedPrice->special_price * (float) $trm->cop;
        $copCost = (float) $copCost * 1.05;
        $cop     = (round(round($copCost, 0), -3)) + 900;

        return $cop;
    }
}
