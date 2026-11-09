<?php

namespace app\modules\auth\controllers;

use app\core\Controller;
use app\models\UsersModel;
use app\modules\admin\Admin;

class loginController extends Controller
{

    function insertAction()
    {
        try {

            $user = UsersModel::login(inputPost('username'), inputPost('password'));

            UsersModel::setLogged($user);

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            if (PHP_VERSION_ID >= 70300) {
                setcookie('user', $user->getToken(), [
                    'expires' => strtotime('+30 days'),
                    'path' => '/',
                    'secure' => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie('user', $user->getToken(), strtotime('+30 days'), '/; samesite=Lax', '', $isHttps, true);
            }

            return [
                'message' => "{$this->tratamento()} {$user->getNome()}",
                'url' => url(null, null, (Admin::isMaster() or Admin::isGerente()) ? 'admin' : 'site'),
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function tratamento()
    {
        $hr = (int)date('H');

        if ($hr >= 12 and $hr <= 18) {
            return 'Boa tarde';
        } else if ($hr >= 5 and $hr <= 11) {
            return 'Bom dia';
        } else {
            return 'Boa noite';
        }
    }

}