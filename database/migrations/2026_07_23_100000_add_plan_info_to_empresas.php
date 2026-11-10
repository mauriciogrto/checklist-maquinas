<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (! Schema::hasColumn('empresas', 'plano_periodicidade')) {
                $table->string('plano_periodicidade', 20)->default('mensal')->after('mensalidade_em_dia');
            }

            if (! Schema::hasColumn('empresas', 'plano_vencimento')) {
                $table->date('plano_vencimento')->nullable()->after('plano_periodicidade');
            }
        });

        DB::table('empresas')
            ->whereNull('plano_periodicidade')
            ->update(['plano_periodicidade' => 'mensal']);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            foreach (['plano_vencimento', 'plano_periodicidade'] as $column) {
                if (Schema::hasColumn('empresas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
