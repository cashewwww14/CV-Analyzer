<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->enum('file_type', ['pdf', 'docx']);
            $table->longText('extracted_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cvs');
    }
};