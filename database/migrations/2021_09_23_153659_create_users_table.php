<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class CreateUsersTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('email', 64)->unique();
            $table->string('password')->default(Hash::make('0'));
            $table->string('image')->nullable()->default(null);
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
