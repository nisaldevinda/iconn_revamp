<?php

namespace App\Library;

use App\Exceptions\ModelException;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\Log;
use App\Library\Interfaces\ModelReaderInterface;

class Model
{
    use JsonModelReader;

    private $content;

    function __construct($content = [])
    {
        $this->content = $content;
    }

    /**
     * Get model name
     *
     * @return string
     */
    public function setContent($content = [])
    {
        return $this->content = $content;
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
        return isset($this->content['name']) ? $this->content['name'] : null;
    }

    /**
     * Get model attribute keys as an array
     *
     * @return array
     */
    public function getAttributeKeys()
    {
        return isset($this->content['fields']) ? array_keys($this->content['fields']) : [];
    }

    /**
     * Get computed attributes as an array
     * 
     * @return array
     */
    public function getComputedAttributeKeys()
    {
        $fields = isset($this->content['fields']) ? $this->content['fields'] : [];
        $computedAttributes = array_filter($fields, function ($value) {
            return (isset($value['isComputedProperty']) && $value['isComputedProperty']);
        });

        return array_keys($computedAttributes);
    }

    /**
     * Get model attributes as an array
     *
     * @return array
     */
    public function getAttributes($onlyKeys = true, $storableAttribute = true)
    {
        $fields = isset($this->content['fields']) ? $this->content['fields'] : [];

        if ($storableAttribute) {
            $fields = array_filter($fields, function ($value, $key) {
                return !(isset($value['isNonRecordableField']) && $value['isNonRecordableField'])
                    && !(isset($value['isComputedProperty']) && $value['isComputedProperty']);
            }, ARRAY_FILTER_USE_BOTH);
        }

        if (!$onlyKeys) {
            return $fields;
        }

        $attributes = [];

        foreach ($fields as $key => $fieldMeta) {
            $relationType = $this->getRelationType($key);

            switch ($relationType) {
                case RelationshipType::HAS_ONE:
                case RelationshipType::BELONGS_TO_MANY:
                    $attributeName = $key . "Id";
                    array_push($attributes, $attributeName);
                    break;
                case RelationshipType::HAS_MANY:
                    break;
                case RelationshipType::HAS_MANY_TO_MANY:
                    $attributeName = $key . "Ids";
                    array_push($attributes, $attributeName);
                    break;
                default:
                    array_push($attributes, $key);
                    break;
            }
        }

        return $attributes;
    }



        /**
     * Get model attributes as an array
     *
     * @return array
     */
    public function getAttributesWithActualColumnsInTable($onlyKeys = true, $storableAttribute = true)
    {
        $fields = isset($this->content['fields']) ? $this->content['fields'] : [];

        if ($storableAttribute) {
            $fields = array_filter($fields, function ($value, $key) {
                return !(isset($value['isNonRecordableField']) && $value['isNonRecordableField'])
                    && !(isset($value['isComputedProperty']) && $value['isComputedProperty']);
            }, ARRAY_FILTER_USE_BOTH);
        }

        if (!$onlyKeys) {
            return $fields;
        }

        $attributes = [];

        foreach ($fields as $key => $fieldMeta) {
            $relationType = $this->getRelationType($key);

            switch ($relationType) {
                case RelationshipType::HAS_ONE:
                case RelationshipType::BELONGS_TO_MANY:
                    $attributeName = $key . "Id";
                    $attributes[$key] = $attributeName;

                    // array_push($attributes, $attributeName);
                    break;
                case RelationshipType::HAS_MANY:
                    break;
                case RelationshipType::HAS_MANY_TO_MANY:
                    $attributeName = $key . "Ids";
                    $attributes[$key] = $attributeName;
                    // array_push($attributes, $attributeName);
                    break;
                default:
                    // array_push($attributes, $key);
                    $attributes[$key] = $key;
                    break;
            }
        }

        return $attributes;
    }

    /**
     * Get model attribute with rules
     *
     * @return array
     */
    public function getAttribute($attribute)
    {
        return isset($this->content['fields'][$attribute]) ? $this->content['fields'][$attribute] : [];
    }

    /**
     * Check whether attribute exisit
     *
     * @param $name of the attibute
     * @return boolean
     */
    public function hasAttribute($name)
    {
        return in_array($name, $this->getAttributeKeys());
    }

    /**
     * Get model relationships as an array
     */
    public function getRelations($relationType = null)
    {
        $relations = isset($this->content['relations']) ? $this->content['relations'] : [];

        switch ($relationType) {
            case RelationshipType::HAS_ONE:
                $relations = array_filter($relations, function ($value, $key) {
                    return $value === RelationshipType::HAS_ONE;
                }, ARRAY_FILTER_USE_BOTH);
                break;
            case RelationshipType::HAS_MANY:
                $relations = array_filter($relations, function ($value, $key) {
                    return $value === RelationshipType::HAS_MANY;
                }, ARRAY_FILTER_USE_BOTH);
                break;
            case RelationshipType::BELONGS_TO:
                $relations = array_filter($relations, function ($value, $key) {
                    return $value === RelationshipType::BELONGS_TO;
                }, ARRAY_FILTER_USE_BOTH);
                break;
            case RelationshipType::BELONGS_TO_MANY:
                $relations = array_filter($relations, function ($value, $key) {
                    return $value === RelationshipType::BELONGS_TO_MANY;
                }, ARRAY_FILTER_USE_BOTH);
                break;
            case RelationshipType::HAS_MANY_TO_MANY:
                $relations = array_filter($relations, function ($value, $key) {
                    return $value === RelationshipType::HAS_MANY_TO_MANY;
                }, ARRAY_FILTER_USE_BOTH);
                break;
            default:
                break;
        }

        return array_keys($relations);
    }

    /**
     * get relations by type
     *
     * @param $relationType type
     * @return array
     */
    public function getRelationsByRelationType($relationType)
    {
        $relations = isset($this->content['relations']) ? $this->content['relations'] : [];

        $result = array_filter($relations, function ($relation) use ($relationType) {
            return $relation == strtoupper($relationType);
        });

        return array_keys($result);
    }

    /**
     * get relational models
     *
     * @param $relations array
     * @return array
     */
    public function getRelationalModels($relations = [])
    {
        $modelRelations = empty($relations) ? $this->getRelations() : $relations;

        return array_map(function ($field) {
            $attribute = $this->getAttribute($field);
            $model = empty($attribute) ? $field : $attribute['modelName'];
            return ['field' => $field, 'model' => $model];
        }, $modelRelations);
    }

    /**
     * 
     */
    public function getEffectiveDateConsiderableModels()
    {
        $model = $this->toArray();

        $fields = isset($model['fields']) ? $model['fields'] : [];

        $filteredModels = [];

        foreach ($fields as $fieldName => $fieldMeta) {
            if (isset($fieldMeta['isEffectiveDateConsiderable']) && $fieldMeta['isEffectiveDateConsiderable']) {
                array_push($filteredModels, $fieldMeta['modelName']);
            }
        }

        return $filteredModels;
    }

    /**
     * Check whether relation exisit
     *
     * @param $relation name of the relation
     * @return boolean
     */
    public function hasRelation($relation)
    {
        return in_array($relation, $this->getRelations());
    }

    /**
     * Get relational model
     *
     * @param $relation name of the relation
     * @return Model | ModelException
     */
    public function getRelationModal($relation)
    {
        try {
            if (!$this->hasRelation($relation)) {
                throw new ModelException("'$relation' relation is not defined in '" . $this->getName() . "' model");
            }

            if (!$this->hasAttribute($relation)) {
                throw new ModelException("'$relation' attribute is not defined in '" . $this->getName() . "' model");
            }

            $relationalModel = $this->getAttribute($relation);

            if (!isset($relationalModel['type']) && $relationalModel['type'] !== 'model') {
                throw new ModelException("'$relation' attribute is not defined as model in '" . $this->getName() . "' model");
            }

            if (!isset($relationalModel['modelName'])) {
                throw new ModelException("'$relation' invalid model definition in '" . $this->getName() . "' model");
            }

            $relationModal = $this->getModel($relationalModel['modelName'], true);
            return $relationModal;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get relation type
     *
     * @param $relation name of the relation
     * @return string
     */
    public function getRelationType($relation)
    {
        $relations = isset($this->content['relations']) ? $this->content['relations'] : [];

        return in_array($relation, $this->getRelations()) ? $relations[$relation] : null;
    }

    /**
     * Get foreign key according to relationship
     *
     * @param $relation name of the relation
     * @return string
     */
    public function getForeignKey($relation)
    {
        if (!$this->hasRelation($relation)) {
            throw new ModelException("'$relation' not exist for retrive foreign key '" . $this->getName() . "' model");
        }

        $attribute = $this->getAttribute($relation);
        $relationType = $this->getRelationType($relation);

        switch ($relationType) {
            case RelationshipType::HAS_ONE:
            case RelationshipType::BELONGS_TO_MANY:
                if (!isset($attribute['name'])) {
                    throw new ModelException("Name not defined for '$relation' '" . $this->getName() . "' model");
                }
                return $attribute['name'] . "Id";

            case RelationshipType::BELONGS_TO:
                if (!isset($attribute['foreignKeyAttribute'])) {
                    throw new ModelException("Foreign key attribute not defined for '$relation' '" . $this->getName() . "' model");
                }
                return $attribute['foreignKeyAttribute'] . "Id";

            case RelationshipType::HAS_MANY:
                return $this->getName() . "Id";

            case RelationshipType::HAS_MANY_TO_MANY:
                if (!isset($attribute['name'])) {
                    throw new ModelException("Name not defined for '$relation' '" . $this->getName() . "' model");
                }
                return $attribute['name'] . "Ids";
        }
    }

    /**
     * Get foreign keys
     *
     * @param $relations name of the relations
     * @return array
     */
    public function getForeignKeys($relations)
    {
        $foreignKeys = [];

        foreach ($relations as $relation) {
            if (!$this->hasRelation($relation)) {
                throw new ModelException("'$relation' not exist for retrive foreign key '" . $this->getName() . "' model");
            }

            $type = $this->getRelationType($relation);

            if ($type === 'BELONGS_TO') {
                $model = $this->getRelationModal($relation);
                $modelName = $model->getName();
                array_push($foreignKeys, $modelName . "Id");
            }
        }

        return $foreignKeys;
    }

    /**
     * Get model template tokens as an array
     */
    public function getTemplateTokens()
    {
        return isset($this->content['templateTokens']) ? $this->content['templateTokens'] : [];
    }

    /**
     * Get model tokens as an array
     */
    public function getTokens()
    {
        return isset($this->content['templateTokens']) ? array_values($this->content['templateTokens']) : [];
    }

    /**
     * Get model template token fields as an array
     */
    public function getTokenAttributes($tokens = [])
    {
        if (empty($tokens)) {
            return isset($this->content['templateTokens']) ? array_keys($this->content['templateTokens']) : [];
        }

        $tokensWithFields = isset($this->content['templateTokens']) ? $this->content['templateTokens'] : [];
        $fields = [];
        foreach ($tokens as $token) {
            $fields[] = array_search($token, $tokensWithFields);
        }
        return $fields;
    }

    /**
     * Send the overall model soft delete status
     */
    public function getIsSoftDeleteStatus($modelName)
    {
        if (isset($modelName)) {
            $model = $this->getModel($modelName);
            if (isset($model['hasSoftDelete'])) {
                return $model['hasSoftDelete'];
            }
        }
        return null;
    }

     /**
     * Send the overall model sensitive feild data
     */
    public function getSensitiveFeilds($modelName)
    {
        if (isset($modelName)) {
            $model = $this->getModel($modelName);
            if (isset($model['sensitiveFeilds'])) {
                return $model['sensitiveFeilds'];
            }
        }
        return null;
    }

    /**
     * Get effective date considerable attributes
     */
    public function isEffectiveDateConsiderableModel()
    {
        return isset($this->content['hasEffectiveDate']) ? $this->content['hasEffectiveDate'] : false;
    }

    /**
     * Get effective date considerable attributes
     */
    public function getEffectiveDateConsiderableAttributes()
    {
        $attributes = $this->getAttributes(false, false);
        $filteredModels = [];

        foreach ($attributes as $fieldName => $fieldMeta) {
            if (isset($fieldMeta['isEffectiveDateConsiderable']) && $fieldMeta['isEffectiveDateConsiderable']) {
                array_push($filteredModels, $fieldMeta);
            }
        }

        return $filteredModels;
    }

    /**
     * Get effective date considerable attributes
     */
    public function getHasManyAttributesWithEffectiveDate()
    {
        $fields = $this->getRelationsByRelationType("HAS_MANY");

        $data = [];

        foreach ($fields as $field) {
            $fieldMeta = $this->getAttribute($field);
            $modelName = isset($fieldMeta['modelName']) ? $fieldMeta['modelName'] : null;
            $name = isset($fieldMeta['name']) ? $fieldMeta['name'] : null;
            $isEffectiveDateConsiderable = isset($fieldMeta['isEffectiveDateConsiderable']) ? $fieldMeta['isEffectiveDateConsiderable'] : false;
            if (!(is_null($modelName) && is_null($name))) {
                $data[$field] = ['isEffectiveDateConsiderable' => $isEffectiveDateConsiderable, 'modelName' => $modelName, 'name' => $name];
            }
        }
        return $data;
    }

    /**
     * Get required fields
     */
    public function getRequiredFields($ignoreComputed = true, $ignoreDependentFields = true)
    {
        $attributes = isset($this->content['fields']) ? $this->content['fields'] : [];

        $requiredFields = [];

        foreach ($attributes as $key => $attribute) {
            if (!isset($attribute['validations']['isRequired'])) {
                continue;
            }
            if ($ignoreComputed) {
                if (isset($attribute['isComputedProperty']) && $attribute['isComputedProperty']) {
                    continue;
                }
            }

            // this is for skipping fields which shows on depending user inputs
            if ($ignoreDependentFields && isset($attribute['showOn'])) {
                continue;
            }

            $type = isset($attribute['type']) ? $attribute['type'] : null;

            if ($type != 'model') {
                array_push($requiredFields, $key);
            }

            $relationType = $this->getRelationType($key);

            switch ($relationType) {
                case RelationshipType::HAS_ONE:
                case RelationshipType::BELONGS_TO_MANY:
                    $attributeName = $key . "Id";
                    array_push($requiredFields, $attributeName);
                    break;
                case RelationshipType::HAS_MANY:
                    break;
                case RelationshipType::HAS_MANY_TO_MANY:
                    $attributeName = $key . "Ids";
                    array_push($requiredFields, $attributeName);
                    break;
            }
        }
        return $requiredFields;
    }


    public function mapActualFieldNamesWithDefinitions () {
        $attributes = $this->getAttributesWithActualColumnsInTable();
        $fieldDefinitions = (array) $this->getAttributes(false);

        $actualDefinitions = [];

        foreach ($attributes as $key => $value) {
            $actualDefinitions[$value] = $fieldDefinitions[$key];
        }

        return $actualDefinitions;

    }

    //get salary component wise fields from model
    public function getSalaryComponentTypeWiseFields ($componentName) {

        $fieldDefinitions = (array) $this->getAttributes(false);

        $filteredFields = [];

        $componentRelateFields = array_filter($fieldDefinitions, function ($item) use ($componentName, $filteredFields) {
            $item = (array) $item;
            if (isset($item['componentType']) &&  $item['componentType'] === $componentName) {
                return $item;
            }
        });

        $filteredFields = array_values($componentRelateFields);
        if (!empty($filteredFields)  && sizeof($filteredFields) == 1 && $componentName == 'basic') {
            return $filteredFields[0]['name'];
        }

        $componentTypeBasedFields = [];

        foreach ($filteredFields as $key => $fields) {
            $fields = (array) $fields;
            $componentTypeBasedFields[] = $fields['name'];
        }

        return $componentTypeBasedFields;
        
    }
}
