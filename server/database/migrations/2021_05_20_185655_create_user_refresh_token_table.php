<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRefreshTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_refresh_token', function (Blueprint $table) {
            $table->id();
            $table->integer('userId')->nullable();
            $table->string('accessTokenId')->nullable();
            $table->string('refreshToken')->nullable();
            $table->integer('refreshTokenExpireAt')->nullable();
            $table->timestamp('createdBy')->nullable()->default(null);
            $table->timestamp('updatedBy')->nullable()->default(null);
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
        Schema::dropIfExists('user_refresh_token');
    }
}
