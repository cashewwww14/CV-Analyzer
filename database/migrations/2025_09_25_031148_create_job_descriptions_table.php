<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('required_skills');
            $table->json('preferred_skills')->nullable();
            $table->string('experience_level');
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_descriptions');
    }
};