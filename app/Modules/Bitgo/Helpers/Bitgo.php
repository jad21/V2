<?php
namespace App\Modules\Bitgo\Helpers;

use V2\Core\Rest\Api;
use Exception;

class Bitgo extends Api
{
    const ENV_TEST      = "TEST";
    const ENV_PROD      = "PROD";
    const TEST_ENDPOINT = 'https://test.bitgo.com/api/v1/';
    const PROD_ENDPOINT = 'https://www.bitgo.com/api/v1/';

    protected $base_ws    = "";
    private $configData   = null;
    private $access_token = null;
    private $walletId     = null;

    public function __construct($ENVIRONMENT = self::ENV_TEST)
    {
        if ($ENVIRONMENT == self::ENV_TEST) {
            $this->base_ws = self::TEST_ENDPOINT;
        } else if ($ENVIRONMENT == self::ENV_PROD) {
            $this->base_ws = self::PROD_ENDPOINT;
        } else {
            throw new Exception("No existe ese tipo de ENVIRONMENT", 1);
        }
    }
    public function setNameCredentials($name)
    {
        $this->configData = env("bitgo.json");
        $this->setToken($this->configData->{$name}->token);
        $this->setWalletId($this->configData->{$name}->wallet);
    }
    /**
     *    Ping hacia bitgo para saber si hay conexion y que ENVIRONMENT esta.
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     */
    public function ping()
    {
        return $this->get("ping");
    }

    /**
     *    setear el token para la sesion
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     *    @param string $token
     *    @return null
     */
    public function setToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     *    setear la wallet
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     *    @param string $walletId
     *    @return null
     */
    public function setWalletId($walletId)
    {
        $this->walletId = $walletId;
    }

    /**
     *    Colocar el token en la cabecera para hacer las peticiones privadas
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     *    @return null
     */
    public function setTokenHeader()
    {
        $this->setHeader("Authorization", "bearer " . $this->access_token);
    }

    public function login($data)
    {
        $body = [
            "email"    => $data["email"],
            "password" => $this->encryptPassword($data["password"], $data["email"]),
            "otp"      => $data["otp"],
        ];
        return $this->post("user/login", $body);
    }
    private function encryptPassword($password, $email)
    {
        return hash_hmac("sha256", $password, $email);
    }

    public function currentUserProfile()
    {
        $this->setTokenHeader();
        return $this->get("user/me");
    }

}
