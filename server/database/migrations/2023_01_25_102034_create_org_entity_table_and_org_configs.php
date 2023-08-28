<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrgEntityTableAndOrgConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orgEntity', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('parentEntityId')->nullable()->default(null);
            $table->integer('headOfEntityId')->nullable()->default(null);
            $table->string('entityLevel');
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
            $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        $defaultOrgEntity = [
            'id' => 1,
            'name' => 'Default Organization',
            'entityLevel' => 'level1'
        ];

        DB::table('orgEntity')->insert($defaultOrgEntity);

        $configuration = [
            "id" => 11,
            "key" => "organization_hierarchy",
            "description" => "Organization Hierarchy",
            "type" => "json",
            "value" => json_encode(["level1" => "Organization"])
        ];

        $orgHirachy = DB::table('configuration')->where('id', '=', 11)->first();

        if (Schema::hasTable('configuration') && is_null($orgHirachy)) {
            DB::table('configuration')->insert($configuration);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('configuration')->where('id', '=', 11)->delete();

        Schema::dropIfExists('orgEntity');
    }
}
