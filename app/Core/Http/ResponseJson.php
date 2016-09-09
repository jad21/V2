<?php
namespace App\Core\Http;
use Exception;
class ResponseJson
{
    /**
     * Response data.
     *
     * @var string
     */
    protected $data;
    
    /**
     * Constructor.
     *
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    /**
     * Render the response as JSON.
     * 
     * @return string
     */
    public function toJson()
    {
        header('Content-Type: application/json');
        if (is_object($this->data) OR is_array($this->data) ) {
            return json_encode($this->data);
        }
        if ($this->is_json_valid($this->data)) {
            return json_encode($this->data);
        }
        return json_encode(["data"=>$this->data]);

    }
    
    public function is_json_valid($value)
    {
        try {
            json_decode($value);
            return (json_last_error()===JSON_ERROR_NONE);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function __tostring()
    {
        return (string)$this->toJson();
    }
}