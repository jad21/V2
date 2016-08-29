<?php
namespace App\Modules\Front\Models;
use App\Core\Models\CoreModel;

class KeywordModel extends CoreModel
{
	protected $table = "keywords_aws";
	protected $connection_name = "yws";
}
