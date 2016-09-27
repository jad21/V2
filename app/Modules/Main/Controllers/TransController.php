<?php
namespace App\Modules\Main\Controllers;

use App\Modules\Main\Services\TransService;
use V2\Core\Controllers\ControllerCore;

class TransController extends ControllerCore
{
    private $service = null;
    
    // Middleware before any methods
    protected function before($request) {}
    
    public function index($request)
    {
        $this->service = new TransService();
        return $this->service->trans("Hello Yaxa");
    }
   
}
