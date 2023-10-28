<?php

use App\Models\Paciente;
use App\Models\Problema;
use App\Models\Psicologo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Psicologo::class);
            $table->foreignIdFor(Paciente::class)->nullable();
            $table->foreignIdFor(Problema::class)->nullable();
            $table->string("nome")->nullable();
            $table->string("apelido")->nullable();
            $table->string("email")->nullable();
            $table->string("contacto")->nullable();
            $table->string("contacto2")->nullable();
            $table->integer('estado_id')->default(1);
            $table->date('data');
            $table->string("hora");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultas');
    }
};
