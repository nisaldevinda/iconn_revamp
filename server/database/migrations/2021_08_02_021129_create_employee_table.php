<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('employeeNumber');
            $table->string('initials')->nullable()->default(null);
            $table->string('firstName');
            $table->string('middleName')->nullable()->default(null);
            $table->string('lastName');
            $table->string('forename')->nullable()->default(null);
            $table->string('maidenName')->nullable()->default(null);
            $table->date('dateOfBirth');
            $table->integer('maritalStatusId')->nullable()->default(null);
            $table->integer('genderId');
            $table->string('bloodGroup')->nullable()->default(null);

            // Identification Information
            $table->string('nicNumber')->nullable()->default(null);
            $table->string('passportNumber')->nullable()->default(null);
            $table->date('passportExpiryDate')->nullable()->default(null);
            $table->string('drivingLicenceNumber')->nullable()->default(null);

            // Ethnic Information
            $table->integer('religionId')->nullable()->default(null);
            $table->integer('nationalityId')->nullable()->default(null);
            $table->integer('raceId')->nullable()->default(null);

            // Residential Address
            $table->string('residentialAddressStreet1')->nullable()->default(null);
            $table->string('residentialAddressStreet2')->nullable()->default(null);
            $table->string('residentialAddressCity')->nullable()->default(null);
            $table->integer('residentialAddressStateId')->nullable()->default(null);
            $table->integer('residentialAddressZip')->nullable()->default(null);
            $table->integer('residentialAddressCountryId')->nullable()->default(null);

            // Permanent Address
            $table->string('permanentAddressStreet1')->nullable()->default(null);
            $table->string('permanentAddressStreet2')->nullable()->default(null);
            $table->string('permanentAddressCity')->nullable()->default(null);
            $table->integer('permanentAddressStateId')->nullable()->default(null);
            $table->integer('permanentAddressZip')->nullable()->default(null);
            $table->integer('permanentAddressCountryId')->nullable()->default(null);

            // Contact
            $table->string('workEmail')->nullable()->default(null);
            $table->string('personalEmail')->nullable()->default(null);
            $table->string('workPhone')->nullable()->default(null);
            $table->string('mobilePhone')->nullable()->default(null);
            $table->string('homePhone')->nullable()->default(null);

            // Social
            $table->string('facebookLink')->nullable()->default(null);
            $table->string('linkedIn')->nullable()->default(null);
            $table->string('twitter')->nullable()->default(null);
            $table->string('instagram')->nullable()->default(null);
            $table->string('pinterest')->nullable()->default(null);

            // Employment Basic
            $table->date('hireDate');
            $table->date('originalHireDate')->nullable()->default(null);

            // Compensation Basic
            $table->timestamp('payGrade')->nullable()->default(null);

            // Spouse details
            $table->date('dateOfRegistration')->nullable()->default(null);
            $table->integer('certificateNumber')->nullable()->default(null);

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
        Schema::dropIfExists('employee');
    }
}
