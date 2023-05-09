<?php

namespace App\Http\Controllers\Utils;

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
}
