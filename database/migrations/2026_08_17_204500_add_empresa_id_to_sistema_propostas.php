<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sistema_propostas') || Schema::hasColumn('sistema_propostas', 'empresa_id')) {
            return;
        }

        Schema::table('sistema_propostas', function (Blueprint $table) {
            $table->foreignId('empresa_id')
                ->nullable()
                ->after('id')
                ->constrained('empresas')
                ->nullOnDelete();
        });

        DB::table('empresas')
            ->select(['id', 'nome'])
            ->orderBy('id')
            ->get()
            ->each(function ($empresa) {
                DB::table('sistema_propostas')
                    ->whereNull('empresa_id')
                    ->where('cliente_nome', $empresa->nome)
                    ->update(['empresa_id' => $empresa->id]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sistema_propostas') || ! Schema::hasColumn('sistema_propostas', 'empresa_id')) {
            return;
        }

        Schema::table('sistema_propostas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
        });
    }
};
