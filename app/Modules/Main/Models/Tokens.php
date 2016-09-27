<?php
namespace App\Modules\Main\Models;

use V2\Core\Models\CoreModel;
class Tokens extends CoreModel
{
	protected $table = 'tokens';
	protected $connection_name = "table";
}