<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadores', function (Blueprint $table) {
            if (! Schema::hasColumn('operadores', 'locacao_cliente_id')) {
                $table->unsignedBigInteger('locacao_cliente_id')->nullable()->after('empresa_id');
                $table->index('locacao_cliente_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operadores', function (Blueprint $table) {
            if (Schema::hasColumn('operadores', 'locacao_cliente_id')) {
                $table->dropIndex(['locacao_cliente_id']);
                $table->dropColumn('locacao_cliente_id');
            }
        });
    }
};
