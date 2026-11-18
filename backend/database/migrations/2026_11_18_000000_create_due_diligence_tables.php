<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terceiros', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['pf', 'pj']);
            $table->string('nome', 150);
            $table->string('documento', 14)->unique(); // só dígitos (CPF ou CNPJ)
            $table->boolean('pep')->default(false);
            $table->timestamps();
        });

        Schema::create('apontamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terceiro_id')->constrained('terceiros')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->unsignedSmallInteger('ano');
            $table->timestamps();
        });

        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('terceiros')->cascadeOnDelete();
            $table->foreignId('socio_id')->constrained('terceiros')->cascadeOnDelete();
            $table->decimal('participacao', 5, 2);
            $table->timestamps();
            $table->unique(['empresa_id', 'socio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
        Schema::dropIfExists('apontamentos');
        Schema::dropIfExists('terceiros');
    }
};
