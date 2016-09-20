<?php
namespace App\Modules\Bitgo\Services;

use App\Modules\Bitgo\Helpers\Bitgo;
use App\Modules\Bitgo\Models\Tokens;
use Exception;

class BitgoService
{
    private $api = null;
    public function __construct($name_config)
    {
        $this->api = new Bitgo;
        $this->api->setNameCredentials($name_config);
    }
    /**
     *      Info de ambiente de conexion hacia bitgo.
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     */
    public function ping()
    {
        return $this->api->ping();
    }
    /**
     *    obtiene todos los datos de la sesion actual
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     */
    public function currentUserProfile()
    {
        return $this->api->currentUserProfile();
    }
    /**
     *      Generar un nuevo token desde bitgo.
     *    @author Jose Angel Delgado <esojangel@gmail.com>
     *    @param string $ip
     *    @param array $data [email,password,opt]
     *    @return string $token
     */
    public function login($ip, $data)
    {
        $tokenModel = new Tokens;
        $result     = $tokenModel
            ->where("user", $data["email"])
            ->where("ip", $ip)
            ->first();
        $now = time();
        if (is_null($result) or (is_not_null($result) and $now > $result->expires_at)) {
            $result_login = $this->api->login($data);
            if (isset($result_login->error)) {
                throw new Exception("Error al crear el token " . je($result_login), 1);
            }
            $tokenNew             = new Tokens;
            $tokenNew->ip         = $ip;
            $tokenNew->user       = $data["email"];
            $tokenNew->token      = $result_login->access_token;
            $tokenNew->expires_at = $result_login->expires_at;
            $tokenNew->saveOrCreate();
            return $result_login->access_token;
        } else {
            return $result->token;
        }
    }

}
