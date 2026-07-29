<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Users Migration
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['user', 'dealer'])->default('user');

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('phone');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('city');
            $table->string('states');
            $table->string('zip');
            $table->string('county')->nullable();
            $table->string('dealer_name')->nullable();
            $table->enum('license_type', ['VI', 'VW', 'VA', 'SD', 'VF'])->nullable();
            $table->string('license_number')->nullable();
            $table->string('region')->nullable();
            $table->string('zones')->nullable();
            $table->text('additional_info')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'username',
                'user_type',
                'address_1',
                'address_2',
                'city',
                'states',
                'zip',
                'phone',
                'county',
                'dealer_name',
                'license_type',
                'license_number',
                'region',
                'zones',
                'verification_token',
            ]);
        });
    }
}
