<?php

namespace App\Core\Controllers;
use App\Core\Views\View;
class ControllerCore
{
	protected $parameters = [];

	public function __construct()
	{
		# code...
	}
	private static $view = null;
	
	public function view($view,$data=null)
	{
		if (!self::$view) {
			self::$view =  new View();
		}
		self::$view->setTemplate($view,$data);
		return self::$view;
	}
	public function fail($value)
	{
		header('HTTP/1.1 500 Error Server');
		return $value;
	}
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
	public function getParameters()
	{
		return $this->parameters;	
	}
}