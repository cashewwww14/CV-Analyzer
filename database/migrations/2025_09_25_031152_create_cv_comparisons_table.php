<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cv_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('comparison_name');
            $table->json('cv_ids'); // Array of CV IDs being compared
            $table->json('comparison_results');
            $table->json('ranking');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cv_comparisons');
    }
};