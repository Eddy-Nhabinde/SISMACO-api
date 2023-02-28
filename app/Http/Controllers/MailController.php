<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    function newPsicologo($email, $nome)
    {
        $object[] = ['key' => 'subject', 'value' => "Wellcome"];
        $object[] = ['key' => 'recipient', 'value' => $email];
        $object[] = ['key' => 'nome', 'value' => $nome];

        if (!$this->sendEmail($this->getMailData($object))) {
            return 0;
        }
        return 1;
    }

    function sendPassword($email, $cod)
    {
        $object[] = ['key' => 'cod', 'value' => $cod];
        $object[] = ['key' => 'recipient', 'value' => $email];
        if ($this->sendEmail($this->getMailData($object)) == 1) {
            return 1;
        } else {
            return 0;
        }
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

    function sendEmail($email_data)
    {
        try {
            Mail::send('new_request', $email_data, function ($message) use ($email_data) {
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
