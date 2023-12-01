<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    function newPsicologo($email, $nome, $password)
    {
        $object[] = ['key' => 'subject', 'value' => "Wellcome"];
        $object[] = ['key' => 'recipient', 'value' => $email];
        $object[] = ['key' => 'nome', 'value' => $nome];
        $object[] = ['key' => 'password', 'value' => $password];

        if (!$this->sendEmail($this->getMailData($object), 'new_request')) {
            return false;
        }
        return true;
    }

    function RescheduleAppointment($data, $request, $paciente)
    {
        $object[] = ['key' => 'subject', 'value' => "Consulta Remarcada"];
        $object[] = ['key' => 'recipient', 'value' => $data[0]->email];
        $object[] = ['key' => 'nome', 'value' => 'Prezado ' . $data[0]->nome];
        $object[] = ['key' => 'dataNova', 'value' => Carbon::parse($request->data)->format('Y-m-d')];
        $object[] = ['key' => 'data', 'value' => $request->prevDate];
        $object[] = ['key' => 'hora', 'value' => $request->prevTime];
        $object[] = ['key' => 'horaNova', 'value' => $request->hora];
        $object[] = ['key' => 'reschedule', 'value' => true];

        if ($this->sendEmail($this->getMailData($object), 'newAppointment') == 1) {
            $object[] = ['key' => 'recipient', 'value' => $paciente[0]->email];
            $object[] = ['key' => 'nome', 'value' => 'Prezado/a ' . $paciente[0]->nome];

            $this->sendEmail($this->getMailData($object), 'newAppointment');
            return true;
        }
        return false;
    }

    function sendPassword($email, $cod)
    {
        $object[] = ['key' => 'password', 'value' => $cod];
        $object[] = ['key' => 'recipient', 'value' => $email];
        $object[] = ['key' => 'subject', 'value' => "Reposicao da Senha"];
        $object[] = ['key' => 'recover', 'value' => true];

        if ($this->sendEmail($this->getMailData($object), 'new_request') == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    function newAppointment($email, $data, $hora, $nome)
    {
        $object[] = ['key' => 'subject', 'value' => "Nova Consulta"];
        $object[] = ['key' => 'recipient', 'value' => $email];
        $object[] = ['key' => 'data', 'value' => $data];
        $object[] = ['key' => 'hora', 'value' => $hora];
        $object[] = ['key' => 'nome', 'value' => 'Prezado/a.'  . $nome];

        if ($this->sendEmail($this->getMailData($object), 'newAppointment') == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    function cancelAppointment($data, $psyData)
    {
        $object[] = ['key' => 'subject', 'value' => "Consulta Cancelada"];
        $object[] = ['key' => 'recipient', 'value' => $data[0]->email];
        $object[] = ['key' => 'data', 'value' => $data[0]->data];
        $object[] = ['key' => 'hora', 'value' => $data[0]->hora];
        $object[] = ['key' => 'nome', 'value' => 'Sr(a). ' . $data[0]->nome];
        $object[] = ['key' => 'cancel', 'value' => true];

        if ($this->sendEmail($this->getMailData($object), 'newAppointment') == 1) {
            $object[] = ['key' => 'recipient', 'value' => $psyData[0]->email];
            $object[] = ['key' => 'nome', 'value' => 'Prezado/a ' . $psyData[0]->nome];

            $this->sendEmail($this->getMailData($object), 'newAppointment');
            return 1;
        }
        return 0;
    }

    function getMailData($data)
    {
        $mailData = [
            'from' => 'dlabteamsd@gmail.com',
            'fromname' => 'SISMACO',
        ];

        foreach ($data as $key) {
            $mailData[$key['key']] = $key['value'];
        }

        return $mailData;
    }

    function sendEmail($email_data, $component)
    {
        try {
            Mail::send($component, $email_data, function ($message) use ($email_data) {
                $message->to($email_data['recipient'])
                    ->from($email_data['from'], $email_data['fromname'])
                    ->subject($email_data['subject']);
            });
            return 1;
        } catch (Exception $e) {
            dd($e);
        }
    }
}
