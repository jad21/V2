<?php
namespace App\Core\Http;
use App\Core\Views\View;
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
        if ($data instanceof View) {
            return $this->result = $data;
        }else{
            switch ($format) {
                case 'application/json':
                default:
                    $this->result = new ResponseJson($data);
                break;
            }
        }
        
    }

    public function __tostring() {
        
        return $this->result->__tostring();
    }
}