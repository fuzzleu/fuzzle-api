<?php

use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('templates', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name', 16);
            $table->unsignedSmallInteger('canvas_width');
            $table->unsignedSmallInteger('canvas_height');
            $table->string('thumbnail')->nullable()->default(null);
            $table->json('data')->default('[]');
            $table->boolean('public')->default(false);
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('templates');
    }
}
