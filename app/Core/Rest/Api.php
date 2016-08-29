<?php
namespace App\Core\Rest;

class Api
{
	const RESPONSE_TYPE_JSON = "json";
	const RESPONSE_TYPE_RAW = "html";
	/**
	 * Base del WebService
	 *
	 * @author  Jose Angel Delgado
	 */
	protected $base_ws = "";
	private $headers = [];
	private $responseType = Api::RESPONSE_TYPE_JSON;


	function __construct()
	{
		/*set limit for el curl*/
        set_time_limit(3000);		
	}
	/**
	 * Method Get HTTP
	 *
	 * @author  Jose Angel Delgado
	 * @param  string url 
	 * @return string reponseCurl
	 */

	public function get($methodURl = '', $data = [])
	{
		$parse_url = parse_url($methodURl);
        if (!isset($parse_url["scheme"]) ) {
            $methodURl = $this->base_ws . $methodURl;          
        }
        $url = $this->buildUrl($methodURl, $data);
        return $this->handle($url,"GET");
	}

	public function handle($url, $method = null, $fields = null)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            if (!empty($method)) {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            }
            if (!empty($fields)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            }
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->formatHeaders());

            $data = curl_exec($curl);
            $err = curl_error($curl);
            if ($err) {
            	throw new \Exception($err, 1);
            }
            curl_close($curl);
            return $this->builResponse($data);
        } catch (\Exception $exc) {
            return [
            	"code"=>"ERROR",
            	"msg"=>$exc->getMessage()
            ];
        }
    }
    protected function builResponse($res)
    {
        if ($this->getResponseType()== self::RESPONSE_TYPE_JSON) {
            $obj = json_decode($res);
            if (json_last_error() == 0) {
                return $obj;
            }
        }
        return $res;
    }
    
    public function setResponseType($type = Api::RESPONSE_TYPE_JSON)
    {
    	$this->responseType = $type;
    }
    public function getResponseType()
    {
    	return $this->responseType;
    }

    public function setHeader(string $key, string $value)
    {
    	$this->headers[$key] = $value;
    }
    /**
	 * Format the headers to an array of 'key: val' which can be passed to
	 * curl_setopt.
	 *
	 * @return array
	 */
	private function formatHeaders()
	{
		$headers = array();

		foreach ($this->headers as $key => $val) {
			if (is_string($key)) {
				$headers[] = $key . ': ' . $val;
			} else {
				$headers[] = $val;
			}
		}

		return $headers;
	}
	/**
	 * build params pass get
	 *
	 * @author  Jose Angel Delgado
	 * @param  string $url
	 * @param  string $params
	 * @return string $url_build
	 */
	public function buildUrl($url, array $query)
	{
		if (empty($query)) {
			return $url;
		}

		$parts = parse_url($url);

		$queryString = '';
		if (isset($parts['query']) && $parts['query']) {
			$queryString .= $parts['query'].'&'.http_build_query($query);
		} else {
			$queryString .= http_build_query($query);
		}

		$retUrl = $parts['scheme'].'://'.$parts['host'];
		if (isset($parts['port'])) {
			$retUrl .= ':'.$parts['port'];
		}

		if (isset($parts['path'])) {
			$retUrl .= $parts['path'];
		}

		if ($queryString) {
			$retUrl .= '?' . $queryString;
		}

		return $retUrl;
	}

}