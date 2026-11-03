<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 1024);
            $table->string('id_externo', 1024);
            $table->string('service_name', 1024)->nullable();
            $table->timestampTz('data_ocorrencia');
            $table->text('mensagem');
            $table->string('status_resolucao', 20)->default('pendente');
            $table->text('comentario')->nullable();
            $table->unsignedBigInteger('atribuido')->nullable();
            $table->unsignedBigInteger('resolvido_por_id')->nullable();
            $table->timestamps();

            $table->unique(['type', 'id_externo']);

            $table->foreign('atribuido')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolvido_por_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};