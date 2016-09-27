<?php
namespace App\Modules\Main\Helpers;

use ErrorHandler;
use V2\Core\Rest\Api;

class TestApi extends Api
{
   
    protected $base_ws = "https://yaxaws.com/API/V1/";
   
    public function trans($string)
    {
        return $this->get("Translate/bingtranslate",["inputStr"=>$string]);
    }


}
