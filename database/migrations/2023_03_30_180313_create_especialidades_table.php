<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        DB::table('especialidades')->insert(
            array(
                [
                    'nome' => 'Clinica (Adulto)',
                ],
                [
                    'nome' => 'Clinica (Crianca)',
                ],
                [
                    'nome' => 'Clinica (Laboral)',
                ],
                [
                    'nome' => 'Familia',
                ],
                [
                    'nome' => 'Trabalho',
                ],
                [
                    'nome' => 'Outra',
                ]
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('especialidades');
    }
};
