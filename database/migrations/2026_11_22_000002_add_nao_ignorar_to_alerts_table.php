<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca alertas reativados manualmente depois de ignorados, para que a
     * verificação periódica das regras para ignorar não os ignore de novo.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->boolean('nao_ignorar')->default(false)->after('ignore_rule_id');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('nao_ignorar');
        });
    }
};
