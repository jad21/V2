<?php
namespace App\Modules\Bitgo\Controllers;

use V2\Core\Controllers\ControllerCore;
use App\Modules\Bitgo\Services\BitgoService;

class MainController extends ControllerCore
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {
        return (new BitgoService("testdev"))->ping();
    }
    public function user()
    {
        return (new BitgoService("testdev"))->currentUserProfile();
    }
    public function login()
    {
        $ip      = REMOTE_IP;
        $service = new BitgoService("testdev");
        $data    = [
            "email"    => "bitgobitinka@gmail.com",
            "password" => "bitinka2016$$",
            "otp"      => "0000000",
        ];
        return $service->login($ip,$data);
    }
}
