<?php

namespace App\Http\Controllers\Utils;

use App\Http\Controllers\EspecialidadeController;
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

    function formatSpecility($speciality)
    {
        $response = "";
        for ($i = 0; $i < sizeof($speciality); $i++) {
            $response = $response . "" . $speciality[$i] . ",";
        }
        return $response;
    }

    function formatPsychoList($psychos)
    {
        try {
            $especialidades = DB::table('especialidades')
                ->select('id', 'nome')
                ->get()->toArray();

            foreach ($psychos as $psycho) {
                $ids = explode(",", $psycho->especialidade_id);
                $especilidadeLabel = "";
                $especialidadeIds = [];

                foreach ($ids as $id) {
                    foreach ($especialidades as $espe) {
                        if ((int)$id == $espe->id) {
                            array_push($especialidadeIds, (int)$id);
                            if (sizeof($ids) > 2) {
                                $especilidadeLabel = $especilidadeLabel . "" . $espe->nome . ", ";
                            } else {
                                $especilidadeLabel = $espe->nome;
                            }
                        }
                    }
                }
                $psycho->especialidade = $especilidadeLabel;
                $psycho->especialidade_id = $especialidadeIds;
            }

            return $psychos;
        } catch (Exception $th) {
            return 0;
        }
    }

    function getUserId($id)
    {
        try {
            $id = DB::table('psicologos')
                ->where('id', $id)
                ->select('user_id')
                ->get();

            return $id[0]->user_id;
        } catch (Exception $th) {
            return 0;
        }
    }
}
