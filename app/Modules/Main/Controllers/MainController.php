<?php
namespace App\Modules\Main\Controllers;

use App\Modules\Main\Helpers\Amazon;
use App\Modules\Main\Models\YaxaWsModel;
use App\Core\Logs\Logger;
use App\Core\Controllers\ControllerCore;
use App\Modules\Main\Helpers\MagentoApi;

class MainController extends ControllerCore
{
	function __construct()
	{
		parent::__construct();	
	}
	public function index()
	{
		return $this->view("product_index");
	}
	public function log($req)
	{	
		// Logger::log("como estas");
		// dd($req);
		// $model = new MagentoApi();

		// dd($_SERVER);

		// REQUEST_URI
		return [
			__dir__,
			class_exists("ErrorException")
			// date("Y-m-d h:i:s",time()),date("Y-m-d h:i:s",strtotime('+1 hour'))
			// $model->getToken(),
			// $model->getBySku("B01CRSI5OK"),
			// $model->getLastQuery()
		];
	}
}
