<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rastreador_dispositivos', function (Blueprint $table) {
            $table->string('ultimo_endereco_resumido')->nullable()->after('ultima_longitude');
            $table->timestamp('ultimo_endereco_atualizado_em')->nullable()->after('ultimo_endereco_resumido');
        });

        Schema::table('rastreador_posicoes', function (Blueprint $table) {
            $table->string('endereco_resumido')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('rastreador_posicoes', function (Blueprint $table) {
            $table->dropColumn('endereco_resumido');
        });

        Schema::table('rastreador_dispositivos', function (Blueprint $table) {
            $table->dropColumn(['ultimo_endereco_resumido', 'ultimo_endereco_atualizado_em']);
        });
    }
};
