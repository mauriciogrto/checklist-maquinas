<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'frete_tipo')) {
                $table->string('frete_tipo')->nullable()->after('tempo_contrato');
            }

            if (! Schema::hasColumn('orcamentos', 'imposto_percentual')) {
                $table->decimal('imposto_percentual', 8, 2)->default(0)->after('desconto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            foreach (['imposto_percentual', 'frete_tipo'] as $column) {
                if (Schema::hasColumn('orcamentos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
