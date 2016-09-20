<?php
namespace App\Modules\Bitgo\Models;

use V2\Core\Models\CoreModel;
class Tokens extends CoreModel
{
	protected $table = 'tokens';
	protected $connection_name = "bitinkaws";
}