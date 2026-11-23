<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O texto a ignorar pode ser um trecho longo da mensagem do alerta.
     * Coluna TEXT não aceita índice único sem prefixo no MariaDB, então a
     * unicidade passa a ser garantida só pela validação.
     */
    public function up(): void
    {
        Schema::table('ignore_rules', function (Blueprint $table) {
            $table->dropUnique(['pattern']);
        });

        Schema::table('ignore_rules', function (Blueprint $table) {
            $table->text('pattern')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ignore_rules', function (Blueprint $table) {
            $table->string('pattern')->change();
            $table->unique('pattern');
        });
    }
};
