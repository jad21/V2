<?php
namespace App\Modules\Main\Controllers;

use App\Modules\Main\Helpers\Amazon;
use App\Modules\Main\Models\UserModel;
use App\Core\Logs\Logger;

class MainController 
{
	function __construct()
	{
		
	}
	public function index()
	{
		
		$am = new Amazon();
		return $am->getIndexSearch();
	}
	public function log($req)
	{	
		Logger::log("como estas");
		dd($req);
		
		return $req;
	}
}
