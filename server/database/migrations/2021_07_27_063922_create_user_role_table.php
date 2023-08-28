<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userRole', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->enum('type', ['EMPLOYEE', 'MANAGER', 'ADMIN', 'OTHER']);
            $table->boolean('isDirectAccess')->nullable();
            $table->boolean('isInDirectAccess')->nullable();
            $table->json('customCriteria')->nullable()->default(null);
            $table->json('permittedActions');
            $table->json('workflowManagementActions');
            $table->json('readableFields');
            $table->json('editableFields');
            $table->boolean('isEditable');
            $table->boolean('isVisibility');
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
        Schema::dropIfExists('userRole');
    }
}
