<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (! Schema::hasColumn('maquinas', 'locacao_cliente_id')) {
                $table->unsignedBigInteger('locacao_cliente_id')->nullable()->after('locacao_status');
                $table->index('locacao_cliente_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (Schema::hasColumn('maquinas', 'locacao_cliente_id')) {
                $table->dropIndex(['locacao_cliente_id']);
                $table->dropColumn('locacao_cliente_id');
            }
        });
    }
};
