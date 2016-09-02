<?php
namespace App\Modules\Main\Models;

use App\Core\Models\CoreModel;
class testModel extends CoreModel
{
	protected $table = 'users';
	protected $connection_name = "test";
}