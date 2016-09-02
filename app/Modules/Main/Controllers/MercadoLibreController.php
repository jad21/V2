<?php

namespace App\Modules\Main\Controllers;

use Illuminate\Http\Request;
use App\Modules\Main\Repositories\MercadoLibreRepository;
use App\Core\Controllers\ControllerCore;
class MercadoLibreController extends ControllerCore
{
    private $mercadoLibreRep;

    public function __construct()
    {
        $this->mercadoLibreRep = new MercadoLibreRepository;
    }

    public function index()
    {
        return $this->view("template_mercadolibre");
    }
    public function save($req)
    {
        try{
        	$res = $this->mercadoLibreRep->saveTemplate($req->parameters);
        	if($res["code"]=="OK"){
        		return ([
        			"code"=>"OK",
        			"data"=>$res,
        			"msg"=>"Guardado!"
        		]);
        	}else{
        		throw new \Exception($res["msg"], 1);
        	}

        }catch(\Exception $e){
        	return $this->failJson([
        		"code"=>"ERROR",
        		"msg"=>$e->getMessage(),
        	]);
        }
    }
    public function getDefault()
    {
        return $this->mercadoLibreRep->getDefault();
        
    }

}
