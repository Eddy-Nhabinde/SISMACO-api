<?php

namespace App\Http\Controllers;

use App\Models\Psicologo;
use Exception;
use Illuminate\Http\Request;

class PsicologoController extends Controller
{
    function store($userid, $especialidade, $email, $nome)
    {
        try {
            Psicologo::create([
                'user_id' => $userid,
                'especialidade' => $especialidade
            ]);
            $mail = new MailController();
            $mail->newPsicologo( $email, $nome);
            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }
}
