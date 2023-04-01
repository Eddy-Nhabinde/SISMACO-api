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
}
