<?php

namespace App\Modules\Main\Controllers;

use App\Modules\Main\Helpers\MagentoApi;
use App\Modules\Main\Helpers\YaxaWs;
use App\Modules\Main\Repositories\AmazonRepository;
use App\Modules\Main\Repositories\MercadoLibreRepository;
use App\Core\Controllers\ControllerCore;
class ProductController extends ControllerCore
{
    private $yaxaWs;
    private $amazon;
    private $mercadoLibreRepository;

    public function __construct()
    {
        $this->amazon = new AmazonRepository;
        $this->yaxaWs = new YaxaWs();
        $this->mercadoLibreRepository = new MercadoLibreRepository();
    }


    public function index($req)
    {
        // $tipo_productos = [
        //     ["id"  => 1, "des" => "Producto Simple", ],
        //     ["id"  => 2, "des" => "Producto Configurable", ],
        //     ["id"  => 3, "des" => "Paquete de producto", ],
        //     ["id"  => 4, "des" => "Producto de agrupado", ],
        //     ["id"  => 5, "des" => "Producto de virtual", ],
        //     ["id"  => 6, "des" => "Producto de descargable", ],
        // ];

        $tipo_items_id = ["ASIN", "SKU", "UPC", "EAN", "ISBN"];

        // $search_indices =    $this->yaxaWs->getSearchIndices();

        return $this->view("product_add", compact("tipo_items_id"));
    }
    public function categories()
    {
        return $this->yaxaWs->getSearchIndices();
    }
    public function tipos_productos( $req)
    {
        $ip         = REMOTE_IP;
        $magentoapi = new MagentoApi();
        $magentoapi->login($ip);
        $tipo_productos = $magentoapi->catalogProductTypeListV1();
        
        if (empty($tipo_productos) OR !isset($tipo_productos)) {
            return $this->fail(["code"=>"ERROR","msg"=>"No se conecto a magento"]);
        }
        return $tipo_productos;
    }
    /**
     *     des: Metodo para la busqueda de los productos
     *    @author Jose Angel Delgado
     *    @param Illuminate\Http\Request $req = []
     *    @return mixed json
     */
    public function search($req)
    {
        $response = $this->amazon->search($req->parameters);
        return $response;
    }

    /**
     *     des: Metodo para la inserccion de productos
     *    @author Jose Angel Delgado
     *    @param param1
     *    @return mixed
     */
    public function addProduct($req)
    {
        $ws = new MagentoApi();

        $params = $req->parameters;
        $ip     = REMOTE_IP;
        $ws->login($ip);
        
        $prod = $params["producto"];
        $res             = $ws->addProduct($params["producto"]);
        $product_created = $res["product_created"];
        if (!isset($product_created->statusCode)) {
            $res["code"]="OK";
            $prod["url_mc2"] = $res["product_url"];
            $result_ml = [];
            $_variations= [];
            if (isset($prod["variations"])) {
                $_variations= $prod["variations"];
            }
            $res_prod_ml = $this->mercadoLibreRepository->saveProduct($prod, $_variations);
            if ($res_prod_ml["code"] == "OK") {
                if (isset($res_prod_ml["data"])) {
                    $result_ml["links"] = [];
                    $result_ml["error_links"] = [];
                    $result_ml["code"] = "OK";
                    foreach ($res_prod_ml["data"] as $res_prod) {
                        if (isset($res_prod->permalink)) {
                            $result_ml["links"][] = $res_prod->permalink;
                        }else{
                            $result_ml["error_links"][] = $res_prod;
                        }
                    }
                }else{
                    $result_ml["data"] = $res_prod_ml;
                }
            } else {
                $result_ml["code"] = "ERROR";
                $result_ml["data"] = $res_prod_ml;
            }  
            $res["res_ml"] = $result_ml;
        } else {
            $res = [
                "code"=>"ERROR",
                "data"=>$res,
            ] ;
        }
        return $res;
    }
    /**
     *     des: Metodo para la eliminacion de productos
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function removeProduct($sku, $req)
    {
        $ws = new MagentoApi();

        $params = $req->parameters;
        $ip     = REMOTE_IP;
        $ws->login($ip);

        $res = $ws->removeProduct($sku);
        return $res;
    }

    public function trans($req)
    {
        $string = $req->parameters["inputStr"];
        return [
            "response" => $this->yaxaWs->trans($string),
        ];
    }
    
    
}
