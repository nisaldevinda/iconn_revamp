<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertStateTableData extends Migration
{
    private $countryTableName;
    private $stateTableName;
    private $data;

    public function __construct()
    {
        $this->countryTableName = 'country';
        $this->stateTableName = 'state';

        $path = storage_path() . "/constants/countriesAndStates.json";
        $this->data = json_decode(file_get_contents($path), true);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $countryTableDataCount = DB::table($this->countryTableName)->count();
            $stateTableDataCount = DB::table($this->stateTableName)->count();

            if ($countryTableDataCount > 0 || $stateTableDataCount > 0) {
                echo ">>> " . $countryTableDataCount . " >>> " . $stateTableDataCount;
                echo "Country table and states table is not empty!";
                return;
            }

            foreach ($this->data as $country) {
                $_country = [
                    "name" => $country["name"],
                    "currency" => $country["currency"],
                    "phoneCode" => $country["phone_code"]
                ];

                $countryId = DB::table($this->countryTableName)->insertGetId($_country);

                if (!empty($country["states"])) {
                    foreach ($country["states"] as $state) {
                        $_state = [
                            "countryId" => $countryId,
                            "name" => $state["name"]
                        ];

                        DB::table($this->stateTableName)->insert($_state);
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            if (!Schema::hasTable($this->countryTableName)) {
                DB::table($this->countryTableName)->truncate();
            }

            if (!Schema::hasTable($this->stateTableName)) {
                DB::table($this->stateTableName)->truncate();
            }
        } catch (\Throwable $th) {
            throw $th;
        }

    }
}
