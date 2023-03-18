<?php

namespace App\Http\Controllers;

use App\Models\Psicologo;
use Exception;
use Illuminate\Http\Request;

class PsicologoController extends Controller
{
    function store($userid, $request)
    {
        try {
            if ($this->validating($request)) {
                Psicologo::create([
                    'user_id' => $userid,
                    'especialidade' => $request->especialidade
                ]);

                $contacts = new ContactosController();
                $contacts->store($request, $userid);

                $mail = new MailController();
                if (!$mail->newPsicologo($request->email, $request->nome)) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 0;
            }
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }


    function validating($request)
    {
        try {
            $request->validate([
                'especialidade' => 'required',
                'contacto1' => 'required'
            ]);
            return true;
        } catch (\Illuminate\Validation\ValidationException $th) {
            return false;
        }
    }
}
