<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;

    protected $fillable = [
        'psicologo_id',
        'paciente_id',
        'problema_id',
        'descricaoProblema',
        'estado_id',
        'data',
        'nome',
        'apelido',
        'conatacto1',
        'contacto2',
        "hora"
    ];
}
