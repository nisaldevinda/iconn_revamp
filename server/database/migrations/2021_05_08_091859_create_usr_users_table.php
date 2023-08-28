<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsrUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->boolean('emailVerified')->default(false);
            $table->string('firstName')->nullable();
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('nickname')->nullable();
            $table->string('password')->nullable();
            $table->integer('employeeId')->nullable();
            $table->boolean('inactive')->default(false);
            $table->boolean('blocked')->default(false);
            $table->boolean('expired')->default(false);
            $table->string('lastIp')->nullable();
            $table->timestamp('lastLogin')->nullable()->default(null);
            $table->timestamp('lastFailedLogin')->nullable()->default(null);
            $table->timestamp('lastPasswordReset')->nullable()->default(null);
            $table->integer('loginsCount')->default(0);
            $table->integer('failedLoginsCount')->default(0);
            $table->string('multifactor')->nullable()->default(null);
            $table->string('phoneNumber')->nullable()->default(null);
            $table->string('verificationToken')->nullable()->default(null);
            $table->boolean('isTokenActive')->default(false);
            $table->boolean('phoneVerified')->default(false);
            $table->string('picture')->nullable();
            $table->json('identities')->default('{}');
            $table->json('userMetadata')->default('{}');
            $table->string('createdBy')->nullable();
            $table->string('updatedBy')->nullable();
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
