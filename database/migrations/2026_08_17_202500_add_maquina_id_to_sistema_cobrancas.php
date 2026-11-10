<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sistema_cobrancas') || Schema::hasColumn('sistema_cobrancas', 'maquina_id')) {
            return;
        }

        Schema::table('sistema_cobrancas', function (Blueprint $table) {
            $table->foreignId('maquina_id')
                ->nullable()
                ->after('id')
                ->constrained('maquinas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sistema_cobrancas') || ! Schema::hasColumn('sistema_cobrancas', 'maquina_id')) {
            return;
        }

        Schema::table('sistema_cobrancas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maquina_id');
        });
    }
};
