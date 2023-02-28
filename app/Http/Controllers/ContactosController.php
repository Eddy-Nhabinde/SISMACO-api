<?php

namespace App\Http\Controllers;

use App\Models\Contactos;
use Exception;
use Illuminate\Http\Request;

class ContactosController extends Controller
{
    function store($contactos, $user_id)
    {
        try {
            foreach ($contactos as $key) {
                Contactos::create([
                    'user_id' => $user_id,
                    'contacto' => $key['numero'],
                    'principal' => $key['principal']
                ]);
            }
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }
}
