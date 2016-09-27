<?php
namespace App\Modules\Main\Services;

use App\Modules\Main\Helpers\TestApi;
use App\Modules\Main\Models\Tokens;
use Exception;

class TransService
{
    private $api    = null;

    public function __construct()
    {
        $this->api = new TestApi();
    }

    /**
     *      Traducir a un string
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     */
    public function trans($string)
    {
        return $this->api->trans($string);
    }
    
}
