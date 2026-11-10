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
            if (! Schema::hasColumn('empresas', 'limite_admins')) {
                $table->unsignedInteger('limite_admins')->default(1)->after('status');
            }

            if (! Schema::hasColumn('empresas', 'limite_operadores')) {
                $table->unsignedInteger('limite_operadores')->default(3)->after('limite_admins');
            }

            if (! Schema::hasColumn('empresas', 'limite_maquinas')) {
                $table->unsignedInteger('limite_maquinas')->default(20)->after('limite_operadores');
            }

            if (! Schema::hasColumn('empresas', 'mensalidade_em_dia')) {
                $table->boolean('mensalidade_em_dia')->default(true)->after('limite_maquinas');
            }
        });

        DB::table('empresas')->whereNull('limite_admins')->update(['limite_admins' => 1]);
        DB::table('empresas')->whereNull('limite_operadores')->update(['limite_operadores' => 3]);
        DB::table('empresas')->whereNull('limite_maquinas')->update(['limite_maquinas' => 20]);
        DB::table('empresas')->whereNull('mensalidade_em_dia')->update(['mensalidade_em_dia' => true]);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            foreach (['mensalidade_em_dia', 'limite_maquinas', 'limite_operadores', 'limite_admins'] as $column) {
                if (Schema::hasColumn('empresas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
