<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'data_faturamento')) {
                $table->date('data_faturamento')->nullable()->after('validade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            if (Schema::hasColumn('orcamentos', 'data_faturamento')) {
                $table->dropColumn('data_faturamento');
            }
        });
    }
};
