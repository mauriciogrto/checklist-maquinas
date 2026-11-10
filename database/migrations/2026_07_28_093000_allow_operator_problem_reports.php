<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registros')) {
            DB::statement("ALTER TABLE registros MODIFY formulario_id BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE registros MODIFY tipo ENUM('checklist', 'manutencao', 'problema') NOT NULL");
        }

        if (Schema::hasTable('alertas')) {
            DB::statement("ALTER TABLE alertas MODIFY formulario_item_id BIGINT UNSIGNED NULL");
        }

        if (Schema::hasTable('registro_respostas')) {
            DB::statement("ALTER TABLE registro_respostas MODIFY formulario_item_id BIGINT UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('registros')) {
            DB::statement("ALTER TABLE registros MODIFY tipo ENUM('checklist', 'manutencao') NOT NULL");
        }
    }
};
