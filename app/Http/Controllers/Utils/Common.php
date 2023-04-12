<?php

namespace App\Http\Controllers\Utils;

use Tymon\JWTAuth\Facades\JWTAuth;

class Common
{
    function getUserId()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return $user->id;
    }

    function getAcesso()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return $user->acesso;
    }

    function returnRandomString()
    {
        return sprintf(
            '%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );
    }
}
