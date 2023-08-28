<?php

namespace App\Library;

/**
 * JsonModel
 *
 * JsonModel for convert json model to php readable format
 */
class JsonModel
{
    private $content;

    private $attributes;

    /**
     * Create a new JsonModel instance.
     *
     * @param $model name of the json file
     * @return void | Exception
     */
    function __construct($model = null)
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

            $this->content = $content;

            if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(json_last_error_msg() . " in $model.json");
            }

            $this->attributes = isset($this->content['fields']) ? array_keys($this->content['fields']) : [];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get model content as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->content;
    }

    /**
     * Get model name
     *
     * @return string
     */
    public function getName()
    {
        return ($this->content['name']) ? $this->content['name'] : null;
    }

    /**
     * Get model attributes as an array
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Check whether attribute exisit
     *
     * @param $model name of the json file
     * @return boolean
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }
}
