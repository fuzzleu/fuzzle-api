<?php

use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name', 10)->unique();
            $table->string('email', 64)->unique();
            $table->string('password');
            $table->string('image');
            $table->enum('role', ['user', 'admin'])->default('user');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
