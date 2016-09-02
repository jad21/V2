<?php
namespace App\Core\Rest;

class Api
{
    const RESPONSE_TYPE_JSON = "json";
    const RESPONSE_TYPE_RAW  = "html";
    const ENCODING_JSON  = "json";
    const ENCODING_QUERY   = "html";

    /**
     * Base del WebService
     *
     * @author  Jose Angel Delgado
     */
    protected $base_ws    = "";
    private $headers      = [];
    private $responseType = self::RESPONSE_TYPE_JSON;
    private $requestType  = self::ENCODING_JSON;
    #@array data body json
    private $data = null;
    #@int last code status http response
    private $lastHttpCode = null;
    /**
     * Configuration for CURL
     */
    private $curl_opts_default = [
        CURLOPT_USERAGENT      => "YAXA-REST-1.0.0",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYHOST => 0,
    ];

    public function __construct()
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
        if (!isset($parse_url["scheme"])) {
            $methodURl = $this->base_ws . $methodURl;
        }
        $url = $this->buildUrl($methodURl, $data);
        return $this->handle($url, "GET");
    }
    public function delete($methodURl = '', $data = [])
    {
        $parse_url = parse_url($methodURl);
        if (!isset($parse_url["scheme"])) {
            $methodURl = $this->base_ws . $methodURl;
        }
        $url = $this->buildUrl($methodURl, $data);
        return $this->handle($url, "DELETE");
    }


    public function post($methodURl = '', $data = [],$requestType = self::ENCODING_JSON)
    {
        $parse_url = parse_url($methodURl);
        if (!isset($parse_url["scheme"])) {
            $url = $this->base_ws . $methodURl;
        }
        if($requestType == self::ENCODING_JSON){
        	$this->setHeader('Content-Type', 'application/json');
        	$data = json_encode($data);
        }
        $opts = array(
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $data,
        );
        return $exec = $this->execute($url, $opts);
        // return $this->handle($url,"POST",$data);
    }
    public function put($methodURl = '', $data = [])
    {
        $parse_url = parse_url($methodURl);
        if (!isset($parse_url["scheme"])) {
            $url = $this->base_ws . $methodURl;
        }
        $this->setHeader('Content-Type', 'application/json');
        $body = json_encode($data);
        $opts = array(
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS    => $body,
        );

        $exec = $this->execute($url, $opts);
        return $exec;
    }

    public function setData($fields = null)
    {
        $this->data = $fields;
    }
    public function getDataFormat($fields = null)
    {
        return $this->data;
    }
    public function handle($url, $method = null)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            // if (!empty($method)) {
            //     curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            // }
            if ($method === 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            } elseif ($method !== 'GET') {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            }
            $fields = $this->getDataFormat();
            if (!empty($fields)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->formatHeaders());

            $data = curl_exec($curl);
            $err  = curl_error($curl);
            if ($err) {
                throw new \Exception($err, 1);
            }
            curl_close($curl);
            return $this->builResponse($data);
        } catch (\Exception $exc) {
            return [
                "code" => "ERROR",
                "msg"  => $exc->getMessage(),
            ];
        }
    }
    /**
     * Execute all requests and returns the json body and headers
     *
     * @param string $url
     * @param array $opts
     * @return mixed
     */
    public function execute($uri, $opts = array())
    {
        $curl = curl_init($uri);
        curl_setopt_array($curl, $this->curl_opts_default);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->formatHeaders());

        if (!empty($opts)) {
            curl_setopt_array($curl, $opts);
        }

        $return             = curl_exec($curl);
        $this->lastHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $this->builResponse($return);
    }
    protected function builResponse($res)
    {
        if ($this->getResponseType() == self::RESPONSE_TYPE_JSON) {
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

    public function setHeader($key, $value = false)
    {
        if ($value) {
            $this->headers[$key] = $value;
        } else {
            $this->headers[] = $key;
        }
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
            $queryString .= $parts['query'] . '&' . http_build_query($query);
        } else {
            $queryString .= http_build_query($query);
        }

        $retUrl = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $retUrl .= ':' . $parts['port'];
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
