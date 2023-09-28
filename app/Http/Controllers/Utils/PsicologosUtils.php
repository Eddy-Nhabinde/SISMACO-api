<?php

namespace App\Http\Controllers\Utils;

use Exception;
use Illuminate\Support\Facades\DB;

class PsicologosUtils
{
    function renameStatus($data)
    {
        foreach ($data as $key) {
            if ($key->estado == 1) {
                $key->estado = 'Activo';
                $key->estadoId = 1;
            } else {
                $key->estado = 'Desativado';
                $key->estadoId = 0;
            }
        }
        return $data;
    }

    function getPsicologId($userId)
    {
        try {
            $id = DB::table('psicologos')
                ->where('psicologos.user_id', $userId)
                ->select('id')
                ->get();

            return $id[0]->id;
        } catch (Exception $th) {
            return 0;
        }
    }
}
