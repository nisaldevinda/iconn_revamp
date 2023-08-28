<?php

namespace App\Traits;

use App\Library\Model;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;

/**
 * ConfigHelper is trait for access configuration relate to tenant
 */
trait ConfigHelper
{
    /**
     * Get Configuration Data
     * 
     * @param $keyName name of the configuration
     */
    public function getConfigValue($keyName = null)
    {
        try {
            if (is_null($keyName)) {
                throw new Exception("Configuration key is not defined.");
            }

            //get $keyName relate configuration record
            $configRecord =  (array) DB::table('configuration')->where('key', $keyName)->first();

            if (empty($configRecord)) {
                throw new Exception("{$keyName} Configuration is not exist.");
            }

            $value = null;

            switch ($configRecord['type']) {
                case 'numeric':
                    $value = (!empty($configRecord['value'])) ? (float) $configRecord['value'] : null;
                    break;
                case 'string':
                    $value = (!empty($configRecord['value'])) ? json_decode($configRecord['value']) : null;
                    break;
                case 'json':
                    $value = (!empty($configRecord['value'])) ? json_decode($configRecord['value']) : null;
                    break;
                case 'boolean':
                    $value = filter_var($configRecord['value'], FILTER_VALIDATE_BOOLEAN);
                    break;
                default:
                    $value = (!empty($configRecord['value'])) ? $configRecord['value'] : null;
                    break;
            }

            return $value;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    /**
     * Set Configuration Data
     * 
     * @param $keyName name of the configuration
     * @param $value value of the configuration
     */
    public function setConfigValue($keyName = null, $value = null)
    {
        try {
            if (is_null($keyName)) {
                throw new Exception("Configuration key is not defined.");
            }

            //get $keyName relate configuration record
            $configRecord = (array) DB::table('configuration')->where('key', $keyName)->first();

            if (empty($configRecord)) {
                throw new Exception("{$keyName} Configuration is not exist.");
            }

            $_value = null;

            switch ($configRecord['type']) {
                case 'numeric':
                    $_value = (!empty($value)) ? (float) $value : null;
                    break;
                case 'string':
                    $_value = (!empty($value)) ? $value : null;
                    break;
                case 'json':
                    $_value = (!empty($value)) ? json_encode($value) : null;
                    break;
                case 'boolean':
                    $_value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                default:
                    $_value = (!empty($value)) ? $value : null;
                    break;
            }

            DB::table('configuration')->where('key', $keyName)->update(['value' => $_value]);

            return $_value;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
