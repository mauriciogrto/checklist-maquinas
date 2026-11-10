<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (! Schema::hasColumn('maquinas', 'horimetro_inicial_horas')) {
                $table->decimal('horimetro_inicial_horas', 10, 2)->default(0)->after('codigo_interno');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (Schema::hasColumn('maquinas', 'horimetro_inicial_horas')) {
                $table->dropColumn('horimetro_inicial_horas');
            }
        });
    }
};
