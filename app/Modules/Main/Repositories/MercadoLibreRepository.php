<?php
namespace App\Modules\Main\Repositories;

use App\Modules\Main\Models\MercadoLibre;
use App\Modules\Main\Helpers\MercadoLibre as MercadoLibreHelper;
use App\Modules\Main\Helpers\YaxaWs;

class MercadoLibreRepository
{
    private $yaxaWs;

    public function __construct()
    {

        $this->yaxaWs = new YaxaWs();
        $this->ml     = new MercadoLibre();
        $this->mlHelper     = new MercadoLibreHelper();
    }

    public function saveProduct($data, $variations = [], $html_template = null)
    {
        try {
            if (is_null($html_template)) {
                $html_template = $this->ml->getTemplateDefault();
            }
            // return $this->buildProductToSave($data, $variations, $html_template->html);
            $verificar_db = $this->getBySku($data["product"]["sku"]);
            if (is_null($verificar_db)) {
                $prod_build = $this->buildProductToSave($data, $variations, $html_template->html);
                
                $prod_response = [];
                
                    $prod_response = $this->mlHelper->publish($prod_build);

                $url_mc2 = "";
                if (isset($data["url_mc2"])) {
                    $url_mc2 = $data["url_mc2"];
                }
                 /*guardamos registro en yaxaws*/
                if (isset($prod_response->id)) {
                    $this->saveInYaxaws([
                        "sku"     => $data["product"]["sku"],
                        "url_mc2" => $url_mc2,
                        "ml_id"   => $prod_response->id,
                        "url_ml"  => $prod_response->permalink,
                    ]);
                }

                return [
                    "code" => "OK",
                    "data" => $prod_response,

                ];

            } else {
                return [
                    "code"   => "ERROR",
                    "data"   => ["case" => 1], //ya fue publicado
                    "msg"    => "No se publico, ya estaba publicado\n ver en {$verificar_db->url_ml}",
                    "url_ml" => "{$verificar_db->url_ml}",
                ];
            }
        } catch (\Exception $e) {
            return [
                "code" => "ERROR",
                "msg"  => $e->getMessage() . " {$e->getFile()}:{$e->getLine()}",
                "data" => ["case" => -1], //error
            ];
        }

    }

    public function buildProductToSave($data, $variations = [], $html)
    {
        $prod            = $data["product"];
        $is_configurable = count($variations) > 0;
        $pictures        = $this->getMediaGallery($data["mediaGallery"]);

        $predition = $this->getCategoryAndAttrs($data);

        $description         = $this->buildDescription($prod, $html, $pictures);
        
        $_prod_request = null;
        if ($is_configurable) {
            $_prod_request               = $this->buildProductRequest($prod, $predition->category_id, $description, $pictures);
            $_prod_request["variations"] = [];
            $attrPredominante = $this->attrPredominante($variations,$data["variations_attributes"]);
            foreach ($variations as $simple) {
                $_prod_request["variations"][] = $this->buildProductVariation($simple, $predition,$data["variations_attributes"],$attrPredominante);
            }
            $price                  = collect($_prod_request["variations"])->max("price");
            $_prod_request["price"] = $price;
            
        } else {
            if (isset($prod["price"]) and $prod["price"] > 0) {
                $_prod_request          = $this->buildProductRequest($prod, $predition->category_id, $description, $pictures);
                $_prod_request["price"] = $this->yaxaWs->calculatePrice($prod["price"]);  
            }
        }

        return $_prod_request;
    }

    public function buildProductRequest($prod, $category_id, $description, $pictures)
    {
        return [
            'title'               => substr($prod["name"], 0, 60),
            'category_id'         => $category_id, //'MCO162628',
            'currency_id'         => 'COP',
            'available_quantity'  => '5',
            'listing_type_id'     => 'gold_special', //'silver',//'gold_special',
            'condition'           => 'new',
            'description'         => $description,
            'warranty'            => '1',
            "seller_custom_field" => $prod['sku'],
            'pictures'            => $pictures,
        ];
    }
    private function getMaxProbability($_category_path_probability)
    {
        $_category_path_probability = collect($_category_path_probability)->sortByDesc("prediction_probability");
        foreach ($_category_path_probability->toArray() as $category) {
            return $category;
        }
        return null;
    }
    public function getCategoryAndAttrs($data)
    {
        $obj                 = json_decode("{}");
        $obj->category_id    = null;
        $obj->attrs_required = [];
        $res                 = json_decode("{}");
        if (isset($data["category_id_ml"][0]) and !is_null($data["category_id_ml"][0])) {
            $obj->category_id = $data["category_id_ml"][0];
            $res->variations  = $this->mlHelper->getCategoryAttributes($data["category_id_ml"][0]);
        } else {
            $name                       = $data["product"]["name"];
            $categoryPath               = $data["categoryPath"];
            $_category_path_probability = [];
            $URLS                       = [];
            foreach ($categoryPath as $_categoryPath) {
                $_categoryPath                = join(" > ", $_categoryPath);
                $word                         = $_categoryPath . " " . $name;
                if (strlen($name)>199) {
                    $word = substr($name,0,199);
                }else if (strlen($word)>199) {
                    $word = substr($word,-200);
                    $word = explode(">", $word);
                    if (sizeof($word)>1) {
                        array_shift($word);
                    }
                    $word = join(">", $word);
                }

                $_category_path_probability[] = $this->mlHelper->predictionCategory($word);
            }
            // $_category_path_probability = collect($_category_path_probability)->sortByDesc("prediction_probability");
            // $res = $this->ml->predictionCategory($this->yaxaWs->trans($categoryPath . $name));
            $res              = $this->getMaxProbability($_category_path_probability); //first
            $obj->category_id = $res->id;
        }
        if (isset($res->variations)) {
            $variations     = collect($res->variations);
            $attrs_required = $variations->filter(function ($item) {
                $attrs_comunes = ["color","size"];
                return (isset($item->tags->required) and $item->tags->required);
            });
            foreach ($attrs_required as $i => $item) {
                $itemRes                          = json_decode("{}");
                $itemRes->id                      = $item->id;
                $itemRes->name                    = $item->name;
                $itemRes->type                    = $item->type;
                $obj->attrs_required[$item->type] = $itemRes;
            }
        } else {
            // $res_category_attrs = $this->ml->getCategoryAttributes($obj->category_id);
        }
        return $obj;
    }
    public function buildDescription($prod, $html, $pictures)
    {
        $attrs       = collect($prod["custom_attributes"]);

        $res_attr    = $attrs->where("attribute_code", "description")->first();
        $description = "";
        if (isset($res_attr)) {
            $description = $res_attr["value"];
        }
        $res_attr_features = $attrs->where("attribute_code", "feature")->first();
        $features          = "";
        if (isset($res_attr_features)) {
            $features = $res_attr_features["value"];
        }
        $productimages = "";
        if (isset($pictures[0])) {
            $url           = $pictures[0]["source"];
            $productimages = '<img align="middle" class="fr-dii fr-draggable" src="{{url}}" />';
            $productimages = strtr($productimages, ["{{url}}" => $url]);
        }
        $keys = [
            "{{productname}}"     => $prod["name"],
            "{{productimages}}"   => $productimages,
            "{{productdesc}}"     => $description,
            "{{productfeatures}}" => $features,
            "{{productdetails}}"  => $this->getProductDetails($prod["custom_attributes"], $prod["sku"]),
        ];

        return strtr($html, $keys);
    }
    private function getProductDetails($custom_attributes, $sku)
    {
        $attr_ignore = ["description", "feature"]; //ignore attrs
        $txt         = "<ul>";
        $txt .= "<li><b>Código</b>:{$sku}</li>";
        foreach ($custom_attributes as $attr) {
            if (!in_array($attr["attribute_code"], $attr_ignore)) {
                $key   = isset($attr["attribute_code_es"]) ? $attr["attribute_code_es"] : $attr["attribute_code"];
                $value = $attr["value"];
                $txt .= "<li><b>{$key}</b>:{$value}</li>";
            }
        }
        return $txt . "</ul>";
    }
    private function attrPredominante($variations,$variations_attributes = [],$attrs = ["size","color"]){
        $array_attr_values = [];
        if (count($variations_attributes)==1) {
            return $variations_attributes[0];
        }
        foreach ($attrs as $key) {
            if (in_array($key, $variations_attributes)) {
                $array_attr_values[$key] = array_map(function($simple) use($key)
                {
                    $attrs = collect($simple["product"]["custom_attributes"]);
                    $res = $attrs->where("attribute_code", $key)->first();        
                    if (isset($res->value)) {
                        return $res->value;
                    }
                }, $variations);
                $array_attr_values[$key] = count(array_unique($array_attr_values[$key]));
            }
        }
        $values = array_values($array_attr_values);
        $max = arsort($values);
        foreach ($array_attr_values as $key => $value) {
            if ($max==$value){
                return $key;
            }
        }
        return $variations_attributes[0];
    }
    private function buildProductVariation($simple, $predition,array $variations_attributes= [],$attrPredominante=false)
    {
        $attrs = collect($simple["product"]["custom_attributes"]);

        $_prod_simple = [
            'attribute_combinations' => [],
            'available_quantity'     => 5,
            'price'                  => $this->yaxaWs->calculatePrice($simple["product"]["price"]),
            'seller_custom_field'    => $simple["product"]["sku"],
            'picture_ids'            => $this->getMediaGalleryVariations($simple["mediaGallery"]),
            '_cant_attribute_combinations_new'=>0
        ];
        $array_attrs_variation = [["en"=>"size","es"=>"Talla"],["en"=>"color","es"=>"Color"]];
        foreach ($array_attrs_variation as $attr) {
            $res_attr  = $attrs->where("attribute_code", $attr["en"])->first();
            if (isset($res_attr) and in_array($attr["en"],$variations_attributes)) {
                
                if (isset($predition->attrs_required[$attr["en"]])) {
                    $attr_id                                  = $predition->attrs_required[$attr["en"]]->id;
                    $_prod_simple["attribute_combinations"][] = [
                        'id'         => $attr_id,
                        'value_name' => $res_attr["value"],
                    ];
                } else if ($_prod_simple["_cant_attribute_combinations_new"]==0) {
                    if (count($variations_attributes)>0 and $attrPredominante==$attr["en"]) {
                        $_prod_simple["attribute_combinations"][] = [
                            "name"       => $attr["es"],
                            // "type"       => "color",
                            'value_name' => $res_attr["value"],
                        ];
                        $_prod_simple["_cant_attribute_combinations_new"]++;
                    }
                }
            }        
        }
        
        return $_prod_simple;

    }
    private function getMediaGallery($medias)
    {
        $sources = [];
        $medias  = array_reverse($medias);
        foreach ($medias as $media) {
            foreach ($media as $type => $urlFile) {
                $sources[]["source"] = $urlFile;
                if (count($sources) >= 5) {
                    return $sources;
                }
            }
        }
        return $sources;
    }
    private function getMediaGalleryVariations($medias)
    {
        $sources = [];
        $medias  = array_reverse($medias);
        foreach ($medias as $media) {
            foreach ($media as $type => $urlFile) {
                $sources[] = $urlFile;
                if (count($sources) >= 5) {
                    return $sources;
                }
            }
        }
        return $sources;
    }

    public function getBySku($sku)
    {
        return $this->ml->getBySku($sku);
    }
    public function saveInYaxaws($sku)
    {
        return $this->ml->saveInYaxaws($sku);
    }

    public function getDefault()
    {
        return $this->ml->getTemplateDefault();
    }
    public function saveTemplate($data)
    {
        try {
            $res = $this->ml->saveTemplate($data);
            if ($res) {
                return [
                    "code" => "OK",
                    "data" => $res,
                ];
            } else {
                return [
                    "code" => "ERROR",
                    "msg"  => $res,
                ];
            }

        } catch (\Exception $e) {
            return [
                "code" => "ERROR",
                "msg"  => $e->getMessage() . $e->getFile() . ":" . $e->getLine(),
            ];
        }
    }

}
