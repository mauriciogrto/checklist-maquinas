<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('operador_id')->nullable()->constrained('operadores')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->string('evento', 80);
            $table->string('risco', 20)->default('baixo');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('method', 12);
            $table->string('path', 500);
            $table->string('route_name', 160)->nullable();
            $table->string('ip', 80)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'risco']);
            $table->index(['empresa_id', 'created_at']);
            $table->index(['evento', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
