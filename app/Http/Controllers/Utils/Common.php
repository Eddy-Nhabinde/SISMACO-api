<?php

namespace App\Http\Controllers\Utils;

use Exception;
use Illuminate\Support\Facades\DB;
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

    function getPacienteId($id)
    {
        try {
            $pID = DB::table('pacientes')
                ->select('id')
                ->where('user_id', $id)
                ->get();

            return $pID[0]->id;
        } catch (Exception $th) {
            dd($th);
        }
    }

    function getContacts($id)
    {
        try {
            $contactos = DB::table('contactos')
                ->select('contacto', 'principal')
                ->where('user_id', $id)
                ->get();

            return $contactos;
        } catch (Exception $th) {
            dd($th);
        }
    }
}
