<?php

namespace App\Http\Controllers;

use App\Models\Contactos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactosController extends Controller
{
    function store($request, $user_id)
    {
        try {
            Contactos::insert($this->getContacts($request->contacto1, $request->contacto2, $user_id));
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }

    function updateContacts($request)
    {
        try {
            DB::table('contactos')
                ->where('user_id', $request->user_id)
                ->delete();

            $this->store($request, $request->user_id);
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }

    function getContacts($contacto1, $contacto2, $user_id)
    {
        $contactos[] = ['user_id' => $user_id, 'principal' => 1, 'contacto' => $contacto1];
        if (isset($contacto2) && $contacto2 != "")
            $contactos[] = ['user_id' => $user_id, 'principal' => 0, 'contacto' => $contacto2];

        return $contactos;
    }
}
