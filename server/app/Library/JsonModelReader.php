<?php


namespace App\Library;

use App\Library\Interfaces\ModelReaderInterface;

class JsonModelReader implements ModelReaderInterface
{
    private $model;

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getModel($model = null)
    {
        if (is_null($model)) {
            throw new \Exception('Model not defined.');
        }

        $filePath = base_path('app/Models') . "/$model.json";

        if (!file_exists($filePath)) {
            throw new \Exception('Model not exist.');
        }

        try {
            // get file content as string
            $jsonString = file_get_contents($filePath);
            // strip comments
            $jsonString = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $jsonString);

            // get json file content as array
            $content = json_decode($jsonString, true);

            $this->model->setContent($content);

            if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(json_last_error_msg() . " in $model.json");
            }

            return $this->model;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
