<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('xthreegames_moderators', function (Blueprint $table) {
            $table->unsignedInteger('subuser_id')->primary();
            $table->foreign('subuser_id')->references('id')->on('subusers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xthreegames_moderators');
    }
};
