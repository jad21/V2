<?php
namespace App\Core\Views;

class View 
{
	private $basepath = VIEW_DIRECTORY;
	private $data = [];
	private $views_template = [];
	private $output;
	public function setTemplate($template,$data=null)
	{
		$this->views_template[] = $template;
		if (is_array($data)) {
			$this->data = array_merge($this->data,$data);
		}
	}

	public function render()
	{
		$data = $this->data;
		$basepath = $this->basepath;
		$views_template = $this->views_template;
		$output = "";
		ob_start();
		extract($data);
		foreach ($views_template as $i => $template) {
			$template = strtr($template,[".","/"]);
			include $basepath . $template . ".php";
		}
	    $output = ob_get_contents();
	    ob_end_clean();
	    
		$this->output = $output;
	}

	public function __tostring()
	{
		$this->render();
		return $this->output;
	}
}