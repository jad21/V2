<?php
namespace App\Modules\Main\Repositories;

use App\Modules\Main\Helpers\MagentoApi;
use App\Modules\Main\Helpers\YaxaWs;
use App\Modules\Main\Models\YaxaWsModel;
use ErrorException;
use Exception;

class AmazonRepository
{
    private $yaxaWs;
    public $keysAmazonWs = [
        "obligatorios"    => ["EAN", "UPC"],
        "obligatorios_es" => ["EAN", "UPC"],
        "extras"          => ["Color", "Size", "Brand", "Manufacturer", "Model", "Department"],
        "extras_es"       => ["Color", "Tamaño", "Marca de fábrica", "Fabricante", "Modelo", "Departamento"],
    ];
    public function __construct()
    {
        $this->yaxaWs                 = new YaxaWs();
        $this->yaxaWsModel            = new YaxaWsModel();
        $this->mercadoLibreRepository = new MercadoLibreRepository();
    }
    /**
     *     des: Metodo para la busqueda de los productos
     *    @author Jose Angel Delgado
     *    @param Illuminate\Http\Request $req = []
     *    @return mixed json
     */
    public function search($params)
    {
        try {
            $msg       = "";
            $code      = "OK";
            $ws        = $this->yaxaWs;
            $res_array = [];
            /*1:Por producto|2:masiva*/

            if ($params["selectedSearchtype"] == 1) {

                $res = $ws->item(trim($params["itemId"]), $params["IdType"]);
                if (!isset($res->Items)) {
                    /*si es configurable*/
                    if ($params["tipo_producto"] == "configurable") {
                        if (isset($res->ParentASIN) && $res->ParentASIN != $res->ASIN) {
                            $res = $ws->item(trim($res->ParentASIN), $params["IdType"]);
                        }
                    }
                    $res_array["items"] = [$this->buildProductItems($res)];
                } else {
                    $res_array["items"] = [];
                }

            } else if ($params["selectedSearchtype"] == 2) {

                $current_page = isset($params["current_page"]) ? $params["current_page"] : null;
                $res          = $ws->search($params["search_indice"], trim($params["keywords"]), $current_page);
                // $res_array = $res;

                if (isset($res->Items->Request) && $res->Items->Request->IsValid != "False" and isset($res->Items->Item)) {
                    $res_array["items"]        = collect($res->Items->Item);
                    $res_array["TotalResults"] = $res->Items->TotalResults;
                    $res_array["TotalPages"]   = $res->Items->TotalPages;

                    foreach ($res_array["items"] as $index => $producto) {
                        if ($params["tipo_producto"] == "configurable") {
                            if (isset($res->ParentASIN) && $producto->ParentASIN != $producto->ASIN) {
                                $producto = $ws->item(trim($producto->ParentASIN), $params["IdType"]);
                            }
                        }
                        $res_array["items"][$index] = $this->buildProductItems($producto);
                    }
                } else if (isset($res->Items->Request) && isset($res->Items->Request->Errors->Error)) {
                    $code = "ERROR";
                    if (is_array($res->Items->Request->Errors->Error)) {
                        $msg = json_encode($res->Items->Request->Errors->Error);
                    } else {
                        $msg = $res->Items->Request->Errors->Error->Message;
                    }
                    $res_array = ["case" => 1];
                } else {
                    $code      = "ERROR";
                    $res_array = ["case" => 1, "search" => $res];
                    $msg       = "";
                }
            }
            $response = [
                "data" => $res_array,
                "code" => $code,
                "msg"  => $msg,
            ];
            return $response;
        } catch (ErrorException $e) {
            $response = [
                "data" => ["case" => -1],
                "code" => "ERROR",
                "msg"  => $e->getMessage() . " {$e->getFile()}:{$e->getLine()} ",
            ];
            return $response;
        }

    }
    public function saveProduct($product, $variations_attributes = [], $categoriesId = [])
    {
        $ws = new MagentoApi();
        $ws->login("127.0.0.1");
        try {
            $product         = $ws->addProduct($product, $variations_attributes, $categoriesId);
            $product_created = $product["product_created"];
            if (isset($product_created->id)) {
                return [
                    "data" => $product,
                    "code" => "OK",
                ];
            } else {
                return [
                    "code" => "ERROR",
                    "data" => [
                        "case" => 500,
                        "msg"  => json_encode($product_created),
                    ],
                ];
            }
        } catch (Exception $e) {
            return [
                "code" => "ERROR",
                "data" => [
                    "case" => 1,
                    "msg"  => $e->getMessage(),
                ],
            ];
        }

    }
    private function buildProductItems($prod)
    {
        if (is_null($prod)) {
            return $prod;
        }
        if (isset($prod->Variations)) {
            if (!is_array($prod->Variations->Item)) {
                $prod->Variations->Item = [$prod->Variations->Item];
            }
            if (isset($prod->Variations->VariationDimensions) and
                !is_array($prod->Variations->VariationDimensions->VariationDimension)
            ) {
                $prod->Variations->VariationDimensions->VariationDimension = [$prod->Variations->VariationDimensions->VariationDimension];
            }
            $prod->Variations->Item = array_map(function ($item) use ($prod) {
                $simple = $item;
                if (!is_null($simple)) {
                    return $this->buildProductItems($simple);
                }
            }, $prod->Variations->Item);
            $prod->type_id = "configurable";
        } else {
            $prod->type_id = "simple";
        }
        if (isset($prod->ItemAttributes->Feature) and !is_array($prod->ItemAttributes->Feature)) {
            $prod->ItemAttributes->Feature = [$prod->ItemAttributes->Feature];
        }
        if (isset($prod->ImageSets->ImageSet) and !is_array($prod->ImageSets->ImageSet)) {
            $prod->ImageSets->ImageSet = [$prod->ImageSets->ImageSet];
        }
        if (isset($prod->BrowseNodes)) {
            $prod->CategoryPath = $this->buildCategory($prod->BrowseNodes);
        }
        /*clean tags htlm en destription prod*/
        if (isset($prod->EditorialReviews->EditorialReview->Content)) {
            $prod->EditorialReviews->EditorialReview->Content = $this->cleanTags($prod->EditorialReviews->EditorialReview->Content);
        }

        return $prod;
    }
    private function cleanTags($string = '')
    {
        $string = strip_tags($string, "<p><ul><li><ul><br><b>");
        $string = str_replace("</p>", "</p>" . PHP_EOL, $string);
        return $string;
    }
    public function recursiveCategory($category, $paths = [], $pathIndex = 0)
    {

        $_pathIndex          = $pathIndex;
        $_paths              = $paths;
        $_paths[$_pathIndex] = isset($_paths[$_pathIndex]) ? $_paths[$_pathIndex] : [];
        if (is_array($category)) {
            for ($i = 0; $i < count($category); $i++) {
                $_paths = $this->recursiveCategory($category[$i], $_paths, $i);
            }
        } else {
            if (isset($category->Ancestors)) {
                $_paths = $this->recursiveCategory($category->Ancestors->BrowseNode, $_paths, $_pathIndex);
            }
            if (isset($category->IsCategoryRoot)) {
                if ($category->IsCategoryRoot != 1) {
                    if (isset($category->Name)) {
                        $_paths[$_pathIndex][] = $category->Name;
                    }
                }
            } else {
                if (isset($category->Name)) {
                    $_paths[$_pathIndex][] = $category->Name;
                }
            }
        }
        return $_paths;
    }

    public function buildCategory($BrowseNodes)
    {
        return $this->recursiveCategory($BrowseNodes->BrowseNode);
    }

    public function buildProductToSave($prod, $details = [])
    {
        $_data       = [];
        $_visibility = 4; /*todo*/
        if (array_key_exists("visibility", $details)) {
            $_visibility = $details["visibility"];
        }
        $_data = array_merge($_data, [
            "categoryPath" => isset($prod->CategoryPath) ? $prod->CategoryPath : null,
            "product"      => [
                "sku"        => $prod->ASIN,
                "name"       => $prod->ItemAttributes->Title,
                "status"     => 1,
                "visibility" => $_visibility,
                "type_id"    => $details["type_id"], //d.tipo_producto,
            ],
            "saveOptions"  => true,
        ]);
        if (isset($prod->ItemAttributes->ItemDimensions->Height)) {
            $_data["product"]["weight"] = $prod->ItemAttributes->ItemDimensions->Height;
        } else {
            $_data["product"]["weight"] = 1;
        }

        if (isset($prod->Offers->Offer->OfferListing->Price->Amount)) {
            $_data["product"]["price"] = (float) ($prod->Offers->Offer->OfferListing->Price->Amount / 100);
        } else if (isset($prod->OfferSummary->LowestNewPrice->Amount)) {
            $_data["product"]["price"] = (float) ($prod->OfferSummary->LowestNewPrice->Amount / 100);
        }
        /*add imagenes*/
        $_data["mediaGallery"] = [];

        if (isset($prod->ImageSets->ImageSet)) {
            foreach ($prod->ImageSets->ImageSet as $index => $i) {
                if (property_exists($i, "@attributes")) {
                    if ($i->{"@attributes"}->Category == "primary") {
                        $_data["mediaGallery"][] = [
                            "all" => $i->LargeImage->URL,
                        ];
                    } else {
                        $_data["mediaGallery"][] = [
                            "image" => $i->LargeImage->URL,
                        ];
                    }
                }
            }
        }
        if (count($_data["mediaGallery"]) == 0) {
            if (isset($prod->MediumImage)) {
                $thumbnail = $prod->MediumImage->URL;
            }
            if (isset($prod->LargeImage)) {
                $image = $prod->LargeImage->URL;
            }
            if (isset($prod->MediumImage) or isset($prod->LargeImage)) {
                $_data["mediaGallery"][] = [
                    "all" => isset($image) ? $image : $thumbnail,
                ];
            }
        }

        if ($details["type_id"] == "simple") {
            $_data["product"]["extension_attributes"] = [
                "stock_item" => [
                    "qty"                     => 8,
                    "is_in_stock"             => 1,
                    "manage_stock"            => 1,
                    "use_config_manage_stock" => 0,
                ],
            ];
        }
        /*agregadon variaciones y traduciendolas*/
        if ($details["type_id"] == "configurable") {
            $_data["product"]["extension_attributes"] = [
                "stock_item" => [
                    "is_in_stock"  => 1,
                    "manage_stock" => 1,
                ],
            ];

            $_data["variations_attributes"] = array_map(function ($key) {
                return strtolower($key);
            }, $prod->Variations->VariationDimensions->VariationDimension);

            $_data["variations"] = array_map(function ($item) use ($prod) {
                $simple = $item;
                if (!is_null($simple)) {
                    if (isset($prod->EditorialReviews->EditorialReview->Content)) {
                        // $simple->EditorialReviews = json_decode("{'EditorialReview':'Content':{$prod->EditorialReviews->EditorialReview->Content}}");
                        // $simple->EditorialReviews = json_decode("{}");
                        // $simple->EditorialReviews->EditorialReview = json_decode("{}");
                        // $simple->EditorialReviews->EditorialReview->Content = $prod->EditorialReviews->EditorialReview->Content;

                    }
                    // $simple->CategoryPath = $prod->CategoryPath;
                    return $this->buildProductToSave($simple, ["type_id" => "simple", "visibility" => 1]);
                }
            }, $prod->Variations->Item);
        }

        $array_attrs = collect([]);

        $attrkeysArray    = array_merge($this->keysAmazonWs["obligatorios"], $this->keysAmazonWs["extras"]);
        $attrkeysArray_es = array_merge($this->keysAmazonWs["obligatorios_es"], $this->keysAmazonWs["extras_es"]);

        if (isset($prod->EditorialReviews->EditorialReview->Content)) {
            $description = $prod->EditorialReviews->EditorialReview->Content;
            $array_attrs->push([
                "attribute_code" => "description",
                "value"          => $description,
            ]);
        }

        if (isset($prod->ItemAttributes->Feature)) {

            $array_attrs->push([
                "attribute_code_es" => "Características",
                "attribute_code"    => "feature",
                "value"             => "<ul>" .
                join("", array_map(function ($li) {
                    return "<li>" . $li . "</li>";
                }, $prod->ItemAttributes->Feature))
                . "</ul>",
            ]);
        }
        foreach ($attrkeysArray as $index => $key) {
            if (isset($prod->ItemAttributes->{$key})) {
                $array_attrs->push([
                    "attribute_code_es" => $attrkeysArray_es[$index],
                    "attribute_code"    => strtolower($key),
                    "value"             => $prod->ItemAttributes->{$key},
                ]);
            }

        }

        if (isset($prod->ItemAttributes->ItemDimensions)) {
            $obj = $prod->ItemAttributes->ItemDimensions;

            $valueIl = "";
            if (isset($obj->Height)) {
                $valueIl .= "<li><b>Alto</b>:" . $this->toCm($obj->Height) . " cm</li>";
            }

            if (isset($obj->Length)) {
                $valueIl .= "<li><b>Largo</b>:" . $this->toCm($obj->Length) . " cm</li>";
            }

            if (isset($obj->Weight)) {
                $valueIl .= "<li><b>Peso</b>:" . ($obj->Weight) . "</li>";
            }

            if (isset($obj->Width)) {
                $valueIl .= "<li><b>Ancho</b>:" . $this->toCm($obj->Width) . " cm</li>";
            }

            $array_attrs->push([
                "attribute_code_es" => "Tamaño del Articulo",
                "attribute_code"    => "item_dimensions",
                "value"             => "<ul>{$valueIl}</ul>",
            ]);
        }
        if (isset($prod->ItemAttributes->PackageDimensions)) {
            $obj = $prod->ItemAttributes->PackageDimensions;

            $valueIl = "";
            if (isset($obj->Height)) {
                $valueIl .= "<li><b>Alto</b>:" . $this->toCm($obj->Height) . " cm</li>";
            }

            if (isset($obj->Length)) {
                $valueIl .= "<li><b>Largo</b>:" . $this->toCm($obj->Length) . " cm</li>";
            }

            if (isset($obj->Weight)) {
                $valueIl .= "<li><b>Peso</b>:" . ($obj->Weight) . "</li>";
            }

            if (isset($obj->Width)) {
                $valueIl .= "<li><b>Ancho</b>:" . $this->toCm($obj->Width) . " cm</li>";
            }

            $array_attrs->push([
                "attribute_code_es" => "Tamaño del Paquete",
                "attribute_code"    => "package_dimensions",
                "value"             => "<ul>{$valueIl}</ul>",
            ]);
        }
        $_data["product"]["custom_attributes"] = $array_attrs->toArray();

        return $_data;
    }

    public function transProduct($data)
    {

        $data["product"]["name"] = $this->yaxaWs->trans($data["product"]["name"]);
        foreach ($data["product"]["custom_attributes"] as $i => $item) {
            $data["product"]["custom_attributes"][$i]["value"] = $this->yaxaWs
                ->trans($item["value"]);
        }

        if (isset($data["categoryPath"])) {
            foreach ($data["categoryPath"] as $i => $item) {
                if (is_array($item)) {
                    foreach ($item as $j => $itemChild) {
                        $data["categoryPath"][$i][$j] = $this->yaxaWs->trans($itemChild);
                    }
                } else {
                    $data["categoryPath"][$i] = $this->yaxaWs->trans($item);
                }
            }
        }
        return $data;
    }
    public function transProductVariation($data, $attributes)
    {
        // $data["product"]["name"] = $this->yaxaWs->trans($data["product"]["name"]);
        foreach ($attributes as $atrr_variation) {
            foreach ($data["product"]["custom_attributes"] as $i => $item) {
                if ($atrr_variation == $item["attribute_code"]) {
                    $data["product"]["custom_attributes"][$i]["value"] = $this->yaxaWs->trans($item["value"]);
                }
            }
        }
        return $data;
    }
    public function productValid($d = '')
    {
        /*validar si posee precio*/
        if ($d["product"]["type_id"] == "simple" and
            !isset($d["product"]["price"]) or
            (isset($d["product"]["price"]) and $d["product"]["price"] <= 0)
        ) {
            return [
                "code" => "ERROR",
                "msg"  => " {$d["product"]["sku"]} no posee precio, no se puede guardar",
            ];
        } else if ($d["product"]["type_id"] == "configurable") {

        }
        return ["code" => "OK"];
    }
    public function scheduling_products($items, $log, $keyword = false, $keywordsModel = null)
    {

        $contador = 0;
        if ($keyword) {
            $contador = $keyword->current_page_item;
        }

        foreach ($items as $index => $item) {
            $_produc_variation_saved = [];
            $saved                   = false;
            if ($index < $contador) {
                continue;
            }
            if (is_null($item)) {
                continue;
            }

            $variations_attributes = [];
            $prod                  = $this->buildProductToSave($item, ["type_id" => $item->type_id]);

            $res_valid = $this->productValid($prod);
            if ($res_valid["code"] == "ERROR") {
                $log->error($res_valid["msg"]);
                continue;
            }

            /*trans producto*/
            $cant_total = 1;
            if ($item->type_id == "configurable") {
                foreach ($prod["variations"] as $i => $item_simple) {
                    $res_valid = $this->productValid($item_simple);
                    if ($res_valid["code"] == "ERROR") {
                        unset($prod["variations"][$i]);
                    }
                }
                $cant = count($prod["variations"]);
                if ($cant == 0) {
                    $log->info("producto {$prod["product"]["sku"]} no tiene variaciones");
                    continue;
                }
                $cant_total += $cant;
                $variations_attributes = $prod["variations_attributes"];
                $log->info("producto {$prod["product"]["sku"]} tiene {$cant} variaciones");
            }

            $log->info("traduciendo... ");
            // $log->line("\n");
            // $bar = $log->getOutput()->createProgressBar($cant_total);
            // $bar->start();
            // $bar->setMessage("traduciendo...");
            // $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
            // $bar->advance();
            $prod = $this->transProduct($prod);

            if ($item->type_id == "configurable") {

                foreach ($prod["variations"] as $i => $prod_simple) {
                    $next_item = $i + 1;
                    // $bar->setMessage("traduciendo variacion... {$next_item}");
                    $log->info("traduciendo variacion... {$next_item}");
                    // $bar->advance();
                    $prod["variations"][$i] = $this->transProductVariation($prod_simple, $variations_attributes);
                }
                // $bar->finish();
                // $log->line("\n");

                $log->info("guardando...");

                // $log->line("\n");
                // $bar = $log->getOutput()->createProgressBar($cant_total);
                // $bar->start();
                // $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
                $configurable_product_links = [];
                foreach ($prod["variations"] as $i => $prod_simple) {

                    $next_item = $i + 1;

                    // $bar->setMessage("guardando variacion {$next_item}: {$prod_simple["product"]["sku"]}...");
                    $log->info("guardando variacion {$next_item}: {$prod_simple["product"]["sku"]}...");
                    // $bar->advance();
                    $prod_simple_result = $this->saveProduct($prod_simple, $variations_attributes);

                    if ($prod_simple_result["code"] == "OK") {

                        $_produc_variation_saved[] = $prod_simple;

                        $configurable_product_links[] = $prod_simple_result["data"]["product_created"]->id;
                        // $bar->setMessage("guardado");
                    } else {
                        $log->error("Error:" . $prod_simple_result["data"]["msg"]);
                        $cant_total--;
                        // if ($prod_simple_result["data"]["case"]==500) {
                        //     throw new \ErrorException($prod_simple_result["data"]["msg"], 500);//error de conexion con magento
                        // }
                        // $log->error($prod_simple_result["data"]["msg"]);
                    }
                }
                $prod["variations"]                 = [];
                $prod["configurable_product_links"] = $configurable_product_links;
                // $bar->setMessage("guardando el configurable: {$prod["product"]["sku"]}...");
                $log->info("guardando el configurable: {$prod["product"]["sku"]}...");

            } else {
                // $bar->finish(); //finaliza la traducción
                // $log->line("\n");

                $log->info("guardando: {$prod["product"]["sku"]}...");
                // $log->line("\n");
                // $bar = $log->getOutput()->createProgressBar($cant_total);
                // $bar->start();

                // $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %message%');
                // $bar->setMessage("guardando...");

            }
            // $bar->advance();
            $prod_result = $this->saveProduct($prod, $variations_attributes);

            // $bar->finish();
            // $log->line("\n");

            if ($prod_result["code"] == "ERROR") {

                $log->error($prod_result["data"]["msg"]);
                if ($prod_result["data"]["case"] == 500) {
                    // throw new \ErrorException($prod_result["data"]["msg"], 500); //error de conexion con magento
                }
            } else {
                $log->info("Guardado!, ver en: " . $prod_result["data"]["product_url"]);
                $saved = true;
                /*Publicar a mercado libre*/
                $log->info("Publicando en Mercado Libre, ");

                $prod["url_mc2"] = $prod_result["data"]["product_url"];
                $res_prod_ml     = $this->mercadoLibreRepository->saveProduct($prod, $_produc_variation_saved);

                if ($res_prod_ml["code"] == "OK" and isset($res_prod_ml["data"])) {
                    $res_prod = $res_prod_ml["data"];
                    if (isset($res_prod->permalink)) {
                        $log->info("Ver en: " . $res_prod->permalink);
                    } else {
                        $log->error(json_encode($res_prod));
                    }
                } else {
                    $log->error("No se pudo publicar");
                    $log->error(json_encode($res_prod_ml));
                }

            }
            $contador++;
            if ($keyword) {
                $keywordsModel->updatePageItem($keyword->id, $contador);
                if ($saved == true) {
                    $keywordsModel->updatePageProductStock($keyword->id, $cant_total);
                }
            }
        }
        return true;
    }

    public function push_queue($items, $log)
    {
        $cant = count($items);
        $log->info("#{$cant} prod recibidos");
        try {
            foreach ($items as $index => $item) {
                if (is_null($item)) {
                    continue;
                }
                $exits_product = $this->yaxaWsModel->getProductQueueBySku($item->ASIN);
                if (!is_null($exits_product)) {
                    $log->info("prod {$exits_product->sku} ya existe");
                    continue;
                } else {
                    $log->info("build prod {$item->ASIN}...");
                }

                $prod = $this->buildProductToSave($item, ["type_id" => $item->type_id]);

                $res_valid = $this->productValid($prod);

                if ($res_valid["code"] == "ERROR") {
                    $log->error($res_valid["msg"]);
                    if ($cant == 1) {
                        throw new \Exception($res_valid["msg"], 4);
                    }
                    continue;
                }
                /*trans producto*/
                if ($item->type_id == "configurable") {
                    foreach ($prod["variations"] as $i => $item_simple) {
                        $res_valid = $this->productValid($item_simple);
                        if ($res_valid["code"] == "ERROR") {
                            unset($prod["variations"][$i]);
                        }
                    }
                    $variations_attributes = $prod["variations_attributes"];
                }
                $log->info("traduciendo prod {$prod["product"]["sku"]}...");
                $prod = $this->transProduct($prod);
                if ($item->type_id == "configurable") {

                    foreach ($prod["variations"] as $i => $prod_simple) {
                        $next_item = $i + 1;

                        $prod["variations"][$i] = $this->transProductVariation($prod_simple, $variations_attributes);
                    }
                }
                $this->yaxaWsModel->saveProductQueue($prod);
            }
            return [
                "code" => "OK",
                "msg"  => "",
            ];
        } catch (Exception $e) {
            return [
                "code" => "ERROR",
                "msg"  => $e->getMessage(),
            ];
        }
    }
    public function toCm($num)
    {
        return (float) ((float) $num / 100) * 2.54;
    }
}
