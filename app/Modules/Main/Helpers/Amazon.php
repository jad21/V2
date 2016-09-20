<?php
namespace App\Modules\Main\Helpers;

use App\Core\Rest\Api;
class Amazon extends Api
{
	public function getIndexSearch()
	{
		return $this->get("http://yaxaws.com/V2/main/catalogos");
	}
}