<?php
namespace App\Core;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Headers;
use App\Core\Logs\Logger;
use App\Core\Utils\Result;
use Exception;

class Application
{
    public function run()
    {
        /**
         * Parse the incoming request.
         */
        $request = new Request();
        if (isset($_SERVER['PATH_INFO'])) {
            $request->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        }
        $request->method = strtoupper($_SERVER['REQUEST_METHOD']);
        switch ($request->method) {
            case 'GET':
                $request->parameters = $_GET;
                break;
            case 'POST':
            	if (count($_POST)==0) {
                    $request->parameters = json_decode(file_get_contents('php://input'),true) ;
            		// parse_str(, $request->parameters);
            	}else{
                	$request->parameters = $_POST;
            	}
                break;
            case 'PUT':
                $request->parameters = json_decode(file_get_contents('php://input'),true);
                break;
        }


        /**
         * Route the request.
         */
        $module_name = MODULE_MAIN;
        $arguments_method = [];
        $arguments_method[] = $request;
        $size_urls = sizeof($request->url_elements);
        switch (true) {
            case $size_urls==0:
                $controller_name = CTRL_MAIN;
                $method_name = "index";
                break;
            case $size_urls==1:
                $controller_name = $request->url_elements[0];
                $method_name = "index";
                break;
            case $size_urls==2:
                $controller_name = $request->url_elements[0];
                $method_name = $request->url_elements[1];
                break;
            case $size_urls>=3:
                $module_name = $request->url_elements[0];
                $controller_name = $request->url_elements[1];
                $method_name = $request->url_elements[2];
                
                if (count($request->url_elements)>3) {
                    for ($i=3; $i < count($request->url_elements); $i++) { 
                        $arguments_method[] = $request->url_elements[$i];
                    }
                }
                break;
        }
        

        $module_name = ucfirst($module_name);
        $controller_name = ucfirst($controller_name) . 'Controller';
        
        $class =  "\App\Modules\\{$module_name}\Controllers\\{$controller_name}";

        if (class_exists($class)) {
            $controller   = new $class;
            $action_name  = strtolower($request->method);
            $action_name  = strtolower($method_name);
            if(method_exists($controller, $action_name)){
                try {
                    $response_str = call_user_func_array(array($controller, $action_name), $arguments_method);
                } catch (Exception $e) {
                    $body_exception = 
                            $e->getMessage()." ".
                            $e->getFile().":".$e->getLine()." \n".
                            $e->getTraceAsString();
                    $response_str = Result::error($body_exception,null, $code = "ERROREXCEPTION");
                    Logger::error($body_exception);
                    header('HTTP/1.1 500 Error Server');
                }
            }else{
                header('HTTP/1.1 404 Not Found');
                $response_str = 'Unknown request: ' . join("/",$request->url_elements);
            }
                
        } else {
            header('HTTP/1.1 404 Not Found');
            $response_str = 'Unknown request: ' . join("/",$request->url_elements);
        }
        
        /**
		* Send the response to the client.
		*/
        $response_obj = new Response($response_str, @$_SERVER['HTTP_ACCEPT']);
		//ftp_alloc(ftp_stream, filesize)w Origin: Necessary to consuming from JS - ajax and others async. 
        $headersConfig = new Headers();
        $headersConfig->cors();
        return $response_obj;
    }
}
