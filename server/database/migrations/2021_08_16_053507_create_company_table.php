<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company', function (Blueprint $table) {
            $table->id();

            // General Information
            $table->string('name')->default(null);
            $table->integer('taxId')->default(null);
            $table->integer('registrationNo')->default(null);
            
            // Contact Details       
            $table->string('phone')->default(null);
            $table->string('fax')->default(null);
            $table->string('email')->default(null);
            $table->string('street1')->default(null);
            $table->string('street2')->default(null);
            $table->string('city')->default(null);
            $table->string('stateProvince')->default(null);
            $table->string('zipCode')->default(null);
            $table->string('country')->default(null);
            $table->string('timeZone')->default(null);      
            $table->string('notes')->default(null);
            
            
            // Other Feilds       
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
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
        Schema::dropIfExists('company');
    }
}
