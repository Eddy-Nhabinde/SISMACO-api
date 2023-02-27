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
            $table->foreignIdFor(Paciente::class);
            $table->foreignIdFor(Problema::class);
            $table->text('descricaoProblema');
            $table->string('estado_id');
            $table->date('data');
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
