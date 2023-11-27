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
        Schema::create('problemas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        DB::table('problemas')->insert(
            array(
                [
                    'nome' => 'Pessoal',
                ],
                [
                    'nome' => 'Familiar/Conjugal',
                ],
                [
                    'nome' => 'Laboral',
                ],
                [
                    'nome' => 'Academico',
                ],
                [
                    'nome' => 'Outro',
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
        Schema::dropIfExists('problemas');
    }
};
