<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelMetaSeeder extends Seeder
{
    private $allowedModels = [
        'employee'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //get all files inside app/Models directory
        $folderPath = base_path('app/Models');
        $files = scandir($folderPath);

        foreach ($files as $model) {
            // skip whether the file is not exist
            $pathToFile= $folderPath.'/'.$model;
            if (!is_file($pathToFile)) continue;

            // skip whether the file is not json
            [$modelName, $fileExtension] = explode('.', $model);
            if ($fileExtension != 'json') continue;

            // skip whether it is not allowed model
            if (!in_array($modelName, $this->allowedModels)) continue;

            $modelData = $this->getModel($modelName);

            if (!empty($modelData)) {
                $modelTitle = ucwords(preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',' ', $modelName));
                $result = DB::table('dynamicModel')->updateOrInsert(
                    ['modelName' => $modelName],
                    [
                        'modelName' => $modelName,
                        'title' => $modelTitle,
                        'description' => 'This form use for manage ' . $modelTitle . ' data.',
                        'dynamicModel' => true,
                        'dynamicModel' => $modelData
                    ]
                );

                if (empty($result)) {
                    error_log($modelName . ' > Failed to insert or update');
                }

                error_log($modelName . ' > Successfully inserted/updated');
            }
        }
    }

    private function getModel($modelName)
    {
        try {
            if (is_null($modelName)) {
                throw new Exception("{$modelName} Model not defined.");
            }

            $filePath = base_path('app/Models') . "/$modelName.json";

            if (!file_exists($filePath)) {
                throw new Exception("{$modelName} Model not exist.");
            }

            // get file content as string
            $jsonString = file_get_contents($filePath);

            // strip comments
            $jsonString = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $jsonString);

            return $jsonString;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
