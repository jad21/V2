<?php
namespace App\Modules\Main\Helpers;

use App\Utils;
use App\Core\Utils\Collection;
use App\Core\Rest\Api;
use App\Modules\Main\Models\YaxaWsModel;
use App\Modules\Main\Models\MagentoModel;
use Exception;
class MagentoApi extends Api
{
    protected $debug = true;
   
    public $domain_mc2            = "";
    protected $base_ws            = "";
    private $ATTR_SET_CONFIGURABLE_ID;
    private $ATTR_SET_GROUP_CONFIGURABLE_ID;
    private $username             = "";
    private $password             = "";
    private $PREFIX_PAGE_PRODUCT  = "";
    private $categoriesMemoryPath = [];

    private $configfile = CONFIG_DIRECTORY . CONFIG_FILE;

    private $yaxawsModel = null;
    public function __construct()
    {
        parent::__construct();
        if (file_exists($this->configfile)) {
            $xml = simplexml_load_file($this->configfile);
            $this->ATTR_SET_CONFIGURABLE_ID       = (string)$xml->magento->ATTR_SET_CONFIGURABLE_ID;
            $this->ATTR_SET_GROUP_CONFIGURABLE_ID = (string)$xml->magento->ATTR_SET_GROUP_CONFIGURABLE_ID;
            $this->domain_mc2                     = (string)$xml->magento->DOMAIN_MC2;
            $this->base_ws                        = (string)$xml->magento->DOMAIN_MC2 . "/index.php/rest/V1/";
            $this->username                       = (string)$xml->magento->USER_API_MC2;
            $this->password                       = (string)$xml->magento->PASS_API_MC2;
            if (isset($xml->magento->PREFIX_PAGE_PRODUCT) and $xml->magento->PREFIX_PAGE_PRODUCT!="") {
                $this->PREFIX_PAGE_PRODUCT = (string)$xml->magento->PREFIX_PAGE_PRODUCT;
            }
        }else {
            throw new Exception('get config file database error');
        }
        $this->categoriesMemoryPath           = new Collection([]);
        $this->yaxawsModel = new YaxaWsModel;
        $this->magentoModel = new MagentoModel;
    }

    private function _getToken($uri = "integration/admin/token", $user = null, $pass = null)
    {
        if (!is_null($user) or !is_null($pass)) {
            $data = array("username" => $user, "password" => $pass);
        } else {
            $data = array("username" => $this->getUser(), "password" => $this->getPassword());
        }
        $token = $this->post($uri, $data);

        return $token;
    }
    public function getToken($ip = "127.0.0.1")
    {
        $tokenConfg = $this->yaxawsModel->getToken($this->getUser(),$ip);           
        if (is_null($tokenConfg)) {
            $token        = $this->_getToken();
            $fecha_limite = strtotime("+8 hour");
            $tokenConfg   = [
                "ip_origin" => $ip,
                "token"     => $token,
                "user"      => $this->getUser(),
                "expire_in"   => $fecha_limite,
            ];
            $this->yaxawsModel->insertToken($tokenConfg);

        } else {
            
            $token        = $tokenConfg->token;
            $fecha_limite = (int)$tokenConfg->expire_in;
            $ahora        = time();
            // si ya pasaron las 8 horas
            if ($ahora > $fecha_limite) {
                $token        = $this->_getToken();
                $fecha_limite = strtotime("+8 hour");
                $tokenConfg   = [
                    "token"   => $token,
                    "user"    => $this->getUser(),
                    "expire_in" => $fecha_limite,
                ];
                // $model->where("ip_origin", $ip)->update($tokenConfg);
                $this->yaxawsModel->updateToken($tokenConfg,["ip_origin"=>$ip,"user"=>$this->getUser()]);
            }
        }
        
        return $token;
    }
    public function login($ip)
    {
        $token = $this->getToken($ip);
        $this->setHeader('Authorization' , 'Bearer ' . $token);
        return $token;
    }
    private function getUser()
    {
        return $this->username;
    }
    private function getPassword()
    {
        return $this->password;
    }

    public function assigCategotyToProduct($categoryId, $sku)
    {
        // catalogCategoryLinkRepositoryV1
        // POST /V1/categories/{categoryId}/products

        return $this->post("categories/{$categoryId}/products", [
            "productLink" => [
                "category_id" => $categoryId,
                "sku"         => $sku,
            ],
        ]);
    }
    public function createCategory($name, $parent_id = 1)
    {
        // catalogCategoryRepositoryV1
        // POST /V1/categories

        return $this->post("categories", [
            "category" => [
                "parent_id"       => $parent_id,
                "name"            => $name,
                "is_active"       => true,
                "include_in_menu" => true,
            ],
        ]);
    }
    public function getCategories($rootCategoryId = null, $depth = 1)
    {
        // catalogCategoryManagementV1
        // GET /V1/categories

        return $this->get("categories", [
            "rootCategoryId" => $rootCategoryId,
            "depth"          => $depth,
        ]);
    }

    public function getCategoriesInDb($categoryPath)
    {
        $_categoryPath = $categoryPath;
        $resto         = [];
        $categoryId    = null;

        for ($i = 0; $i < count($categoryPath); $i++) {
            $urlPath = $this->pathToUrlString($_categoryPath);
            if ($this->categoriesMemoryPath->offsetExists($urlPath)) {
                $categoryId = $this->categoriesMemoryPath->offsetGet($urlPath);
                $resto      = array_reverse($resto);
                // echo "no fue a base de datos";
                break;
            } else if ($res = $this->magentoModel->getCategoryEntity($urlPath)) 
            {
                $categoryId = $res->entity_id;
                $resto      = array_reverse($resto);
                $this->categoriesMemoryPath->offsetSet($urlPath, $categoryId);
                break;
            } else {
                $resto[] = array_pop($_categoryPath);
            }
        }
        return [
            "categoryId"   => $categoryId,
            "pathRestante" => $resto,
        ];
    }
    private function pathToUrlString($arrayPath)
    {
        // $arrayPath = array_map(function ($item) {
        //     return trim($item);
        // }, $arrayPath);
        $_arrayPath = [];
        foreach ($arrayPath as $item) {
            $_arrayPath[] = trim($item);
        }
        $urlstring = join($_arrayPath, "/");
        $urlstring = strtolower($urlstring);
        $urlstring = strtr($urlstring, [" " => "-"]);
        $urlstring = Utils::quitarCarcteresEspeciales($urlstring);
        return $urlstring;
    }

    public function getIdCategoryOrCreate($categoryPath, $rootCategoryId = null)
    {

        $categoryParent = $this->getCategories($rootCategoryId);
        $category_name  = array_shift($categoryPath);
        if (isset($categoryParent->children_data)) {
            $children_data = new Collection($categoryParent->children_data);
            $categoryChild = $children_data->where("name", $category_name);

            if (count($categoryPath) > 0 and !is_null($categoryChild->first())) {
                return $this->getIdCategoryOrCreate($categoryPath, $categoryChild->first()->id);
            } else if (is_null($categoryChild->first())) {
                $categoryChild = $this->createCategory($category_name, $categoryParent->id);
                if (count($categoryPath) > 0) {
                    return $this->getIdCategoryOrCreate($categoryPath, $categoryChild->id);
                }
                return $categoryChild;
            } else {
                return $categoryChild->first();
            }

        } else {

        }
    }

    public function catalogProductTypeListV1()
    {
        // get /V1/products/types
        return $this->get("products/types");
    }

    public function catalogProductAttributeGroupRepositoryV1()
    {
        $pageSize    = 10;
        $currentPage = 1;
        // catalogProductAttributeGroupRepositoryV1
        // GET /V1/products/attribute-sets/groups/list
        return $this->get("products/attribute-sets/groups/list", [
            "searchCriteria" => [
                "filterGroups" => [
                    'filters' => [

                        "field"          => "catalog_product",
                        "value"          => "algo",
                        "condition_type" => "eq",

                    ],
                ],
                "pageSize"     => $pageSize,
                "currentPage"  => $currentPage,
            ],
        ]);
    }
    /**
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function createAttributeGroupRepositoryV1($attributeSetId, $data)
    {
        // catalogProductAttributeGroupRepositoryV1
        // POST /V1/products/attribute-sets/groups
        return $this->post("products/attribute-sets/groups", [
            "group" => [
                // "attribute_group_id"=> $data["attribute_group_id"],
                "attribute_group_name" => $data["attribute_group_name"],
                "attribute_set_id"     => $attributeSetId,
            ],
        ]);
    }
    /**
     *     des: Metodo para traer todos los attr de un set de mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function getAttrsFromAttrsSet($attributeSetId)
    {
        // catalogProductAttributeManagementV1
        // GET /V1/products/attribute-sets/{attributeSetId}/attributes
        return $this->get("products/attribute-sets/{$attributeSetId}/attributes");
    }

    /**
     *     des: Metodo para insertar un attr en un un set de mc2
     *    @author Jose Angel Delgado
     *  @param Int $attributeSetId
     *  @param String $attributeCode
     *    @return response Api
     */
    public function insertAttrToAttributeSet($attributeCode, $attributeSetId, $attributeGroupId)
    {
        // catalogProductAttributeManagementV1
        // POST /V1/products/attribute-sets/attributes
        return $this->post("products/attribute-sets/attributes", [
            "attributeCode"    => $attributeCode,
            "attributeSetId"   => $attributeSetId,
            "attributeGroupId" => $attributeGroupId,
            "sortOrder"        => 0,
        ]);
    }

    /**
     *     des: Metodo para traer los productos de mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function catalogProductRepositoryV1()
    {
        // catalogProductRepositoryV1
        // GET /V1/products
        return $this->get("products", [
            "searchCriteria" => [
                'filterGroups' => [
                    0 => [
                        'filters' => [
                            0 => [
                                'field'          => 'type_id',
                                'value'          => 'configurable',
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
                "pageSize"     => 10,
            ],
        ]);
    }
    private function getMediaGalleryEntries($medias, $name = "")
    {
        $entries = [];
        // $iterator = 0;
        $medias = array_reverse($medias);
        foreach ($medias as $iterator => $media) {
            foreach ($media as $type => $urlFile) {
                if ("all" == $type) {
                    $types = ["image", "small_image", "thumbnail"];
                } else {
                    $types = [$type];
                }
                $entries[] = [
                    'position'   => $iterator,
                    'media_type' => 'image',
                    'disabled'   => false,
                    'label'      => $name . "-" . $iterator,
                    'types'      => $types,
                    'content'    => [
                        'type'                => 'image/jpeg',
                        'name'                => pathinfo($urlFile, PATHINFO_FILENAME) . '.' . pathinfo($urlFile, PATHINFO_EXTENSION),
                        'base64_encoded_data' => base64_encode(file_get_contents($urlFile))
                    ],
                ];
                $iterator++;
            }
        }
        return $entries;
    }
    /**
     *     des: Agregar productos a mc2
     *    @author Jose Angel Delgado
     *    @param Array $params[producto[productData],detalles]
     */
    public function addProduct($data, $variations_attributes = [], $categoriesId = [])
    {

        $self   = $this;
        $result = [];

        $sku = $data["product"]["sku"];

        $attrSetId      = $this->ATTR_SET_CONFIGURABLE_ID;
        $attrSetGroupId = $this->ATTR_SET_GROUP_CONFIGURABLE_ID;

        $is_configurable        = false;
        $_variations_attributes = new Collection($variations_attributes);

        /*verificar que tipo de producto es*/
        if ($data["product"]["type_id"] == "configurable") {
            $is_configurable = true;

            $_variations_attributes = new Collection($data["variations_attributes"]);
            if (!isset($data["variations"])) {
                $data["variations"] = [];
            }

            $variaciones     = new Collection($data["variations"]);
            $products_linked = $variaciones->map(function ($item) use ($self, $data, $_variations_attributes, $categoriesId)
            {
                // $item["categoryPath"] = $data["categoryPath"];
                $res = $self->addProduct($item, $_variations_attributes);
                if (isset($res["categoriesId"])) {
                    $categoriesId = $res["categoriesId"];
                }
                return $res["product_created"];
            });
            
            if (is_null($products_linked)) {
                $products_linked = [];
            }
            // $result["products_variacion_create"] = $products_linked;//solo para debug
            $data["product"]["product_links"]        = [];
            $data["product"]["extension_attributes"] = ["configurable_product_links" => []];
            foreach ($products_linked as $linked_product) {
                if (isset($linked_product->sku)) {
                    $data["product"]["product_links"][] = [
                        "sku"                 => $sku,
                        "link_type"           => "related",
                        "linked_product_sku"  => $linked_product->sku,
                        "linked_product_type" => $data["product"]["type_id"], //"configurable"
                    ];
                }
            }

            $_variations_attributes->each(function($item,$key) use($_variations_attributes)
            {
                if ($item=="customerpackagetype" OR $item=="style") {
                    $_variations_attributes->offsetUnset($key);
                } 
            });
            
            
            $configurableProductOptions = $_variations_attributes->map(function ($attr_variante) use ($self,$_variations_attributes) {

                $attr         = $self->getAttribute($attr_variante);
                $attr_options = $self->getAttrOptions($attr_variante);
                if (!isset($attr_options)) {
                    throw new \Exception("Este error paso porque el attr es extraño:{$attr_variante}", 3);
                    // dd([$attr_variante,$attr_options,$_variations_attributes->offsetExists("customerpackagetype")]);
                }
                $options      = [];
                foreach ($attr_options as $item) {
                    if ($item->value != "") {
                        $options[] = [
                            "value_index" => $item->value,
                        ];
                    }
                }
                return [
                    "attribute_id" => $attr->attribute_id,
                    "label"        => $attr_variante,
                    "position"     => 0,
                    "values"       => $options,
                ];
            });
            $data["product"]["extension_attributes"] = array_merge($data["product"]["extension_attributes"], [
                "stock_item"                   => [
                    "is_in_stock"             => 1,
                    "manage_stock"            => 1,
                    "use_config_manage_stock" => 0,
                ],
                "configurable_product_options" => $configurableProductOptions,
                "configurable_product_links"   => $products_linked->map(function ($item) {
                    return $item->id;
                }),
            ]);
            /*productos simples relacionados al configurable*/
            if (array_key_exists("configurable_product_links", $data)) {
                $data["product"]["extension_attributes"]["configurable_product_links"] = array_merge(
                    $data["product"]["extension_attributes"]["configurable_product_links"]->toArray(),
                    $data["configurable_product_links"]
                );
            }
        }

        /*agregar attr des corta*/
        $existDescription = (new Collection($data["product"]["custom_attributes"]))
            ->where("attribute_code", "description")
            ->first();

        if (!is_null($existDescription)) {
            $short_description                      = strip_tags($existDescription["value"]);
            $short_description                      = substr($short_description, 0, 255) . "...";
            $data["product"]["custom_attributes"][] = [
                "attribute_code" => "short_description",
                "value"          => $short_description,
            ];
        }

        /*add los precios*/
        if (isset($data["product"]["price"]) && $data["product"]["price"] > 0) {
            $yaxaWs = new YaxaWs;

            $pricesData                             = $yaxaWs->generatePrice($data["product"]["price"]);
            $data["product"]["custom_attributes"][] = [
                "attribute_code" => "cost",
                "value"          => $pricesData->cost,
            ];
            $data["product"]["price"]               = $pricesData->price;
            $data["product"]["custom_attributes"][] = [
                "attribute_code" => "special_price",
                "value"          => $pricesData->special_price,
            ];
        }

        // $attrsDefaul = new Collection($this->getAttrsFromAttrsSet($attrSetId));
        foreach ($data["product"]["custom_attributes"] as $index => $custom_attribute) {
            $data["product"]["custom_attributes"][$index] = $this->verifyAttrAndOption(
                $custom_attribute,
                $_variations_attributes->toArray(),
                $attrSetId,
                $attrSetGroupId
            );
        }

        $data["product"]["attribute_set_id"] = $attrSetId;

        /*add images*/
        if (isset($data["mediaGallery"])) {
            $data["product"]["media_gallery_entries"] = $this->getMediaGalleryEntries($data["mediaGallery"], $data["product"]["name"]);
        }

        /*busca o crea las categories*/
        if (isset($data["categoryPath"])) {
            if (count($categoriesId) > 0) {
                $result["categoriesId"] = $categoriesId;
            } else {
                $categoryPath           = new Collection($data["categoryPath"]);
                $result["categoriesId"] = $categoriesId = $categoryPath->map(function ($categoryPath) {
                    /*buscamos en la base de datos, primero, para acelerar los tiempos*/
                    $res_category = $this->getCategoriesInDb($categoryPath);
                    if (is_null($res_category["categoryId"]) || count($res_category["pathRestante"]) > 0) {
                        $category = $this->getIdCategoryOrCreate($res_category["pathRestante"], $res_category["categoryId"]);
                        if (isset($category->id)) {
                            $categoryId = $category->id;
                        }else{
                            throw new Exception("Error en crear una categoria trace:".json_encode($category), 2);
                        }
                    } else {
                        $categoryId = $res_category["categoryId"];
                    }
                    // $categoryId = $this->getIdCategoryOrCreate($categoryPath)->id;
                    return $categoryId;
                });
            }
        }

        /*add las categorias*/
        if (count($categoriesId) > 0) {
            $data["product"]["custom_attributes"][] = [
                "attribute_code" => "category_ids",
                "value"          => $categoriesId,
            ];
        }

        $result["product"] = $data["product"];
        // catalogProductRepositoryV1
        // post /V1/products
        $product_created           = $this->put("products/{$sku}", $data);
        $result["product_created"] = $product_created;
        if (isset($product_created->id)) {
            $res_url_key = (new Collection($product_created->custom_attributes))
                ->where("attribute_code", "url_key")
                ->first();
            if (!is_null($res_url_key)) {
                $result["product_url"] = $this->domain_mc2 . "/" . $res_url_key->value . $this->PREFIX_PAGE_PRODUCT;
            }
        }
        return $result;
    }

    /* verificar si los attr no existe y los crea, tambien si son attr configurables les asigna los valores*/
    public function verifyAttrAndOption($custom_attribute, $_variations_attributes, $attrSetId = null, $attrSetGroupId = null)
    {
        $prefixConfigurable = "_configurable";
        $result             = [];
        $attribute_code     = $custom_attribute["attribute_code"];
        $value              = $custom_attribute["value"];

        $is_attr_configurable           = in_array($attribute_code, $_variations_attributes);
        $result["is_attr_configurable"] = $is_attr_configurable;
        if (array_key_exists("attribute_code_es", $custom_attribute)) {
            $label = $custom_attribute["attribute_code_es"];
        } else {
            $label = $custom_attribute["attribute_code"];
        }

        // $isDefaul = $attrsDefaul ->where("attribute_code", $custom_attribute["attribute_code"]) ->first();
        $resAttr = $this->getAttribute($attribute_code);

        if (!is_null($resAttr) && isset($resAttr->frontend_input)  and $resAttr->frontend_input == "select") {
            $is_attr_configurable = true;
        }
        if (!is_null($resAttr) AND $is_attr_configurable && isset($resAttr->frontend_input) and $resAttr->frontend_input == "text") {
            // $resAttrConfigurable = $attrsDefaul->where("attribute_code", $custom_attribute["attribute_code"] . $prefixConfigurable)->first();
            $resAttrConfigurable = $this->getAttribute($attribute_code . $prefixConfigurable);
            if (!is_null($resAttrConfigurable)) {
                $resAttr        = $resAttrConfigurable;
                $attribute_code = $custom_attribute["attribute_code"] = $attribute_code . $prefixConfigurable;
            }
        }
        /*crera attr sino exite*/
        if (is_null($resAttr)) {
            if ($is_attr_configurable) {
                $attr_created = $this->create_attr(
                    [
                        "attribute_code" => $attribute_code,
                        "type_input"     => "select",
                        "label"          => $label,
                        "options"        => [],
                    ]
                );

            } else {
                $attr_created = $this->create_attr(
                    [
                        "attribute_code" => $attribute_code,
                        "type_input"     => (strlen($value) > 100) ? "textarea" : "text",
                        "label"          => $label,
                    ]
                );
            }

            // $result["insertAttrToAttributeSet_" . $attribute_code] =
            $this->insertAttrToAttributeSet($attribute_code, $attrSetId, $attrSetGroupId);
        } else if ($resAttr->frontend_input == "text" && $is_attr_configurable) {
            $attr_created = $this->create_attr(
                [
                    "attribute_code" => $attribute_code . $prefixConfigurable,
                    "type_input"     => "select",
                    "label"          => $label,
                    "options"        => [$value],

                ]
            );

            // $result["insertAttrToAttributeSet_" . $attribute_code] =
            $this->insertAttrToAttributeSet($attribute_code, $attrSetId, $attrSetGroupId);
        }

        // $result["attr_created"] = $attr_created;
        /*si es attr configurable verificar si tiene el valor de la opction*/
        if ($is_attr_configurable) {
            $options = $this->getAttrOptions($attribute_code);
            // $result["getAttrOptions"] = $options;
            if (!is_null($options)) {
                $res_value = $options->where("label", $value)->first();
                if (is_null($res_value)) {
                    /*agregar opcion*/
                    if ($this->pushOptionAttr($attribute_code, ["label" => $value])) {
                        $options   = $this->getAttrOptions($attribute_code);
                        $res_value = $options->where("label", $value)->first();
                    }
                }
                if (isset($res_value)) {
                    $custom_attribute["value"] = $res_value->value;
                }else{

                }
            }
        }
        // $result["custom_attribute"] = $custom_attribute;

        return $custom_attribute;
    }
    /**
     *     des: Metodo para eliminar productos a mc2
     *    @author Jose Angel Delgado
     *    @param String $sku
     *    @return mixed
     */
    public function removeProduct($sku)
    {
        // catalogProductRepositoryV1
        // delete /V1/products
        return $this->delete("products/{$sku}");
    }
    /**
     *     des: Agregar set atributes a mc2
     *    @author Jose Angel Delgado
     *    @param Array $product
     */
    public function addAttributeSet($name, $skeletonId = 4/*Default*/)
    {
        // catalogAttributeSetManagementV1
        // POST /V1/products/attribute-sets
        return $this->post("products/attribute-sets", [
            "attributeSet" => [
                "attribute_set_name" => $name,
            ],
            "skeletonId"   => $skeletonId,
        ]);

    }
    public function getCatalogAttributeSetRepositoryV1($currentPage = 1, $pageSize = 10)
    {
        // catalogAttributeSetRepositoryV1
        // get /V1/products/attribute-sets/sets/list
        return $response = $this->get("products/attribute-sets/sets/list", [
            "searchCriteria" => [
                // "filterGroups"=>[
                //     'filters' => [
                //         [
                //             // "field"=> "entity_type_code",
                //             // "value"=> "catalog_product",
                //             // "condition_type"=> "eq",
                //         ]
                //     ]
                // ],
                "pageSize"    => $pageSize,
                "currentPage" => $currentPage,
            ],
        ]);
    }
    public function searchAttributeSetId($name, $currentPage = 1, $pageSize = 10)
    {

        // catalogAttributeSetRepositoryV1
        // get /V1/products/attribute-sets/sets/list
        $response = $this->get("products/attribute-sets/sets/list", [
            "searchCriteria" => [
                // "filterGroups"=>[
                //     'filters' => [
                //         [
                //             // "field"=> "entity_type_code",
                //             // "value"=> "catalog_product",
                //             // "condition_type"=> "eq",
                //         ]
                //     ]
                // ],
                "pageSize"    => $pageSize,
                "currentPage" => $currentPage,
            ],
        ]);

        $items   = new Collection($response->items);
        $attrSet = $items->where("attribute_set_name", $name)->first();
        if (is_null($attrSet)) {

            if ($currentPage < ($response->total_count / $pageSize)) {
                $currentPage++;
                return $this->searchAttributeSetId($name, $currentPage);
            } else {
                return $attrSet;
                return null;
            }
        } else {
            return $attrSet;
        }
    }

    /**
     *     Implementation Notes
     *    Retrieve list of product attribute types
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function catalogProductAttributeTypesListV1()
    {
        // catalogProductAttributeTypesListV1
        // GET /V1/products/attributes/types
        return $this->get("products/attributes/types");
    }

    /**
     *     des: Metodo para listar attributos a mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function catalogProductAttributeRepositoryV1()
    {
        // catalogProductAttributeRepositoryV1
        // GET /V1/products/attributes
        return $this->get("products/attributes", [
            "searchCriteria" => [
                "pageSize"    => 5,
                "currentPage" => 1,
            ],
        ]);
    }

    /**
     *     des: Metodo para obtener attributos a mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function get_catalogProductAttributeRepositoryV1($attributeCode)
    {
        // catalogProductAttributeRepositoryV1
        // GET /V1/products/attributes/{attributeCode}
        return $this->get("products/attributes/{$attributeCode}");
    }
    public function getAttrOptions($attributeCode)
    {
        // catalogProductAttributeRepositoryV1
        // GET /V1/products/attributes/{attributeCode}
        $res = $this->get_catalogProductAttributeRepositoryV1($attributeCode);
        if (isset($res->frontend_input) && $res->frontend_input == "select") {
            return new Collection($res->options);
        }
        return null;
    }
    /**
     *     des: Metodo para borrar attributos a mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function delete_catalogProductAttributeRepositoryV1($attributeCode)
    {
        // catalogProductAttributeRepositoryV1
        // delete /V1/products/attributes/{attributeCode}
        return $this->delete("products/attributes/{$attributeCode}");
    }
    /**
     *     des: Metodo para obtener attributos a mc2
     *    @author Jose Angel Delgado
     *    @return mixed
     */
    public function create_attr($params)
    {
        // create_catalogProductAttributeRepositoryV1
        $data = [
            "attribute" => [
                "is_wysiwyg_enabled"            => false,
                "is_html_allowed_on_front"      => true,
                "used_for_sort_by"              => true,
                "is_filterable"                 => true,
                "is_filterable_in_search"       => true,
                "is_used_in_grid"               => false,
                "is_visible_in_grid"            => false,
                "is_filterable_in_grid"         => false,
                "apply_to"                      => ["simple", "configurable"],
                "is_searchable"                 => "1",
                "is_visible_in_advanced_search" => "1",
                "is_comparable"                 => "1",
                "is_used_for_promo_rules"       => "0",
                "is_visible_on_front"           => "1",
                "used_in_product_listing"       => "1",
                "is_visible"                    => true,
                "scope"                         => "global",
                "attribute_id"                  => 0,
                "attribute_code"                => $params["attribute_code"],
                "frontend_input"                => $params["type_input"], //text,dropdown...
                "entity_type_id"                => 4, //???
                "is_required"                   => false,
                "options"                       => [],
                // "is_user_defined"=> true,
                "default_frontend_label"        => $params["label"],
                "backend_type"                  => $params["type_input"] == "select" ? "int" : "varchar", //"int"

            ],
        ];
        if ($params["type_input"] == "select") {
            $data["attribute"]["options"] = $params["options"];
            if (count($params["options"]) > 0) {
                $data["attribute"]["default_value"] = $params["options"][0];
            }
        }
        // catalogProductAttributeRepositoryV1
        // PUT /V1/products/attributes
        $response = $this->put("products/attributes/" . $params["attribute_code"], $data);

        return $response;
    }
    /*Add option to attribute*/
    public function pushOptionAttr($attribute_code, $option)
    {
        // catalogProductAttributeOptionManagementV1
        // POST /V1/products/attributes/{attributeCode}/options
        $option = [
            "option" => [
                "label" => $option["label"],
            ],
            // "value"=> $option["value"],
        ];
        return $this->post("products/attributes/{$attribute_code}/options", $option);
    }

    public function search()
    {
        // /GET /V1/search
        return $this->get("search", [
            "searchCriteria" => [
                "requestName"  => "advanced_search_container",
                "filterGroups" => [
                    0 => [
                        'filters' => [
                            0 => [

                                "field"          => "category_ids",
                                "value"          => "1",
                                "condition_type" => "like",
                            ],
                        ],
                    ],
                ],
                "pageSize"     => 10,
            ],
        ]);
    }

    public function getAttribute($attribute_code)
    {
        // select * from eav_attribute where attribute_code = "color"
        return $this->magentoModel->getAttribute($attribute_code);
    }
}
