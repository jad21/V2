<?php
namespace App\Modules\Main\Controllers;

class MainController extends ControllerCore
{
	function __construct()
	{
		parent::__construct();	
	}
	public function index()
	{
		return $this->view("index");
	}
}
