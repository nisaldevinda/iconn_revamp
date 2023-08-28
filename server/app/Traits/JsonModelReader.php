<?php

namespace App\Traits;

use App\Library\Model;
use App\Exceptions\Exception;
use Illuminate\Support\Facades\DB;
use Log;

/**
 * JsonModelReader is trait for read json model files
 */
trait JsonModelReader
{
    /**
     * Read json model file
     * 
     * @param $modelName name of the model
     * @param $returnAsModelObject when true, this will be returned as Model object
     * return App\Library\Model | array | Exception
     * 
     * Sample output:
     * $modelName = user
     * $returnAsModelObject = false
     */
    public static function getModel($modelName, $returnAsModelObject = false)
    {
        try {
            if (is_null($modelName)) {
                throw new Exception("{$modelName} Model not defined.");
            }

            $filePath = base_path('app/Models') . "/$modelName.json";

            $dynamicModel = DB::table('dynamicModel')
                ->where('modelName', $modelName)
                ->where('isDelete', false)
                ->where('isUnpublished', false)
                ->first();

            if (!file_exists($filePath) && !$dynamicModel) {
                throw new Exception("{$modelName} Model not exist.");
            }

            // get file content as string
            if ($dynamicModel) {
                $jsonString = $dynamicModel->dynamicModel;
            } else {
                $jsonString = file_get_contents($filePath);
            }
            
            // strip comments
            $jsonString = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $jsonString);

            // get json file content as array
            $content = json_decode($jsonString, true);

            if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(json_last_error_msg() . " in $modelName.json");
            }

            return ($returnAsModelObject) ? new Model($content) : $content;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
