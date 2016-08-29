<?php
namespace App\Modules\Main\Helpers;

use App\Core\Rest\Api;
class Amazon extends Api
{
	public function getIndexSearch()
	{
		return $this->get("http://127.0.0.1/yaxaws/API/interfaces/amazon/get-search-indices");
	}
}