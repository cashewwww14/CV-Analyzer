<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cv_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->onDelete('cascade');
            $table->foreignId('job_description_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('overall_score');
            $table->json('detailed_scores'); // {technical: 85, experience: 70, education: 90}
            $table->json('strengths');
            $table->json('weaknesses');
            $table->json('recommendations');
            $table->json('matched_jobs')->nullable();
            $table->json('skill_gaps')->nullable();
            $table->json('course_recommendations')->nullable();
            $table->longText('ai_feedback');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cv_analyses');
    }
};