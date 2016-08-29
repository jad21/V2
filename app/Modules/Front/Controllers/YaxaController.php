<?php
namespace App\Modules\Front\Controllers;

use App\Modules\Main\Helpers\Amazon;
use App\Core\Database\DB;
use App\Modules\Front\Models\KeywordModel;
class YaxaController 
{
	public function index()
	{
		// "http://127.0.0.1/yaxaws/API/interfaces/amazon/get-search-indices"
		$k = new KeywordModel;
		return [
			"all"=>$k->all()
		];
	}
}
