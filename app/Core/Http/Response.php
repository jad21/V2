<?php
namespace App\Core\Http;

class Response
{
    private $result = "";
    /**
     * Constructor.
     *
     * @param string $data
     * @param string $format
     */
    public function __construct($data, $format,$code=200)
    {
        switch ($format) {
            case 'application/json':
            default:
                $this->result = new ResponseJson($data);
            break;
        }
        
    }

    public function __tostring() {
        
        return $this->result->__tostring();
    }
}