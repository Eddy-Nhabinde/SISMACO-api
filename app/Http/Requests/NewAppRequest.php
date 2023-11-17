<?php

namespace App\Http\Requests;

use App\Http\Controllers\Utils\Common;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class NewAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            "validation" => true,
            'warning' => $validator->errors()
        ]));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = new Common();
        $access = $user->getAcesso();

        if ($access != 'paciente')
            return [
                'nome' => 'required|string',
                'apelido' => 'required|string',
                'email' => 'required|email',
                'contacto1' => 'required|string|min:9|max:13',
                'problema' => 'required|integer',
                'data' => 'required|date',
                'psicologo' => 'required|integer',
                'hora' => 'required|string|min:5|max:5',
                "tipoConsulta" => 'requited',
                "tipoPaciente" => 'requited'
            ];
        else if ($access == 'paciente')
            return [
                'problema' => 'required|integer',
                'data' => 'required|date',
                'psicologo' => 'required|integer',
                'hora' => 'required|string|min:5|max:5',
                "tipoConsulta" => 'requited',
                "tipoPaciente" => 'requited'
            ];
    }
}
