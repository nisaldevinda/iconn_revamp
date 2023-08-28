<?php

namespace App\Library;

use Exception;
use Illuminate\Support\Facades\Lang;
use App\Library\Session;
use Log;
use Svg\DefaultStyle;
use App\Traits\JsonModelReader;

class ModelValidator
{
    use JsonModelReader;

    // if isChuckUpdate is true then isRequired validation will skip if true
    // if hasRelationalObj is true then model type fields accepting objects
    public static function validate(
        Model $model,
        $data,
        $isChuckUpdate = false,
        $hasRelationalObj = false
    ) {
        try {
            $store = new Store(app(Session::class));
            $fieldDefinitions = $model->getAttributes(false);

            if (empty($fieldDefinitions)) {
                throw new Exception('Field Definitions not found');
            }

            $relations = $model->getRelations(RelationshipType::BELONGS_TO_MANY);
            $hasForeignKeyCheck = null;

            if (!empty($relations) && !empty($data['id'])) {
                $hasForeignKeyCheck = [
                    "foreignFieldName" => $model->getForeignKey($relations[0]),
                    "value" => $data['id']
                ];
            }

            $errors = [];
            foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
                // skip validation if field is system value
                if (isset($fieldDefinition['isSystemValue']) && $fieldDefinition['isSystemValue']) {
                    continue;
                }

                // if chunk update, skip attribute which not exist in data
                if ($isChuckUpdate && !isset($data[$fieldName])) {
                    continue;
                }

                $fieldName = $fieldDefinition['name'] ?? $fieldName;
                $fieldErrors = ModelValidator::validateField(
                    $fieldName,
                    $fieldDefinition,
                    $data,
                    $isChuckUpdate,
                    $store,
                    $model,
                    $hasForeignKeyCheck
                );

                if (count($fieldErrors) > 0) {
                    $errors[$fieldName] = $fieldErrors;
                }
            }

            return $errors;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private static function validateField($fieldName, $fieldDefinition, $data, $isChuckUpdate, $store, $model, $hasForeignKeyCheck = null)
    {
        $dbFieldName = null;
        $relationType = null;

        if ($fieldDefinition['type'] == 'model') {
            $relationType = $model->getRelationType($fieldName);
        }

        switch ($relationType) {
            case RelationshipType::HAS_ONE:
                $dbFieldName = $fieldName . "Id";
                break;
            case RelationshipType::HAS_MANY_TO_MANY:
                $dbFieldName = $fieldName . "Ids";
                break;
            default:
                $dbFieldName = $fieldName;
                break;
        }

        $errors = [];
        $validations = $fieldDefinition['validations'] ?? null;
        $value = $data[$dbFieldName] ?? null;
        $isDependable = (!empty($fieldDefinition['dependOn'])) ?? false;

        // check isRequired rule
        $isRequired = $validations['isRequired'] ?? false;
        $type = $fieldDefinition['type'] ?? null;
        if (empty($value)) {
            switch ($type) {
                case 'number':
                    if ($isRequired && $value != 0) {
                        $errors[] = Lang::get('ModelValidationMessages.basic.ERR_IS_REQUIRED');
                    }
                    break;
                case 'string':
                    if ($isRequired && $value != '0') {
                        $errors[] = Lang::get('ModelValidationMessages.basic.ERR_IS_REQUIRED');
                    }
                    break;
                default:
                    if ($isRequired) {
                        $errors[] = Lang::get('ModelValidationMessages.basic.ERR_IS_REQUIRED');
                    }
                    break;
            }

            return $errors;
        }

        // check dependent rule
        if ($isDependable) {
            $depedOnData = (sizeof(($fieldDefinition['dependOn'])) > 0) ? (array)$fieldDefinition['dependOn'][0] : [];

            //check has filter key and model key
            if (isset($depedOnData['filterKey']) && isset($depedOnData['modelKey']) && !empty($data[$depedOnData['modelKey']])) {

                $modelName = $fieldDefinition['modelName'];
                $queryBuilder = $store->getFacade();
                $queryBuilder = $queryBuilder::table($modelName);

                $res = $queryBuilder->where('id', $value)->first();
                $res = (array) $res;

                if ($res[$depedOnData['filterKey']] !== $data[$depedOnData['modelKey']]) {
                    $errors[] = Lang::get('ModelValidationMessages.basic.ERR_IS_NOT_MATCH_WITH_DEPENDENT');
                }
                return $errors;
            }
        }

        // handle HAS_MANY relation data
        switch ($relationType) {
            case RelationshipType::HAS_MANY:
                if (!empty($value) && is_array($value)) {
                    $relationModelName = $fieldDefinition['modelName'] ?? null;

                    if (!empty($relationModelName)) {
                        $relationModel = self::getModel($relationModelName, true);

                        foreach ($value as $index => $record) {
                            $relationErrors = self::validate($relationModel, $record, $isChuckUpdate);

                            if (!empty($relationErrors)) {
                                $errors[$index] = $relationErrors;
                            }
                        }
                    }
                }
                break;
        }

        // validate field type
        $isValidDataWithTheValue = ModelValidator::isValidDataWithTheValue($fieldDefinition, $value);

        if (!$isValidDataWithTheValue) {
            $type = $fieldDefinition['type'] ?? null;
            if (!is_null($type) && $type  == 'phone') {
                $errors[] = Lang::get('ModelValidationMessages.basic.ERR_INVALID_PHONE');
            } else {
                $errors[] = Lang::get('ModelValidationMessages.basic.ERR_INVALID_TYPE');
            }
        }


        if (!empty($validations)) {
            // check isUnique rule
            $isUnique = $validations['isUnique'] ?? false;
            if ($isUnique) {
                $modelName = $model->getName();

                $queryBuilder = $store->getFacade();
                $queryBuilder = $queryBuilder::table($modelName);

                $count = $queryBuilder->where($dbFieldName, $value);

                if (!empty($hasForeignKeyCheck)) {
                    $count = $count->where($hasForeignKeyCheck["foreignFieldName"], $hasForeignKeyCheck["value"]);
                }

                if ($isChuckUpdate && !empty($data['id'])) {
                    $count = $count->where('id', '!=', $data['id']);
                }
                if (!empty($data['id'])) {
                    $count = $count->where('id', '!=', $data['id']);
                }
                if ($model->getIsSoftDeleteStatus($modelName)) {
                    $count = $count->where('isDelete', 0);
                }

                $count = $count->count();


                if ($count > 0) {
                    $errors[] = Lang::get('ModelValidationMessages.basic.ERR_IS_UNIQUE');
                }
            }

            if (
                isset($validations["min"])
                && isset($validations["max"])
                && (strlen($value) < $validations["min"])
                && (strlen($value) > $validations["max"])
            ) {
                $errors[] = Lang::get(
                    'ModelValidationMessages.basic.ERR_INVALID_BETWEEN_LENGTH',
                    [
                        'min' => $validations["min"],
                        'max' => $validations["max"]
                    ]
                );
            } elseif (isset($validations["min"]) && (strlen($value) < $validations["min"])) {
                $errors[] = Lang::get(
                    'ModelValidationMessages.basic.ERR_INVALID_MIN_LENGTH',
                    [
                        'min' => $validations["min"]
                    ]
                );
            } elseif (isset($validations["max"]) && (strlen($value) > $validations["max"])) {
                $errors[] = Lang::get(
                    'ModelValidationMessages.basic.ERR_INVALID_MAX_LENGTH',
                    [
                        'max' => $validations["max"]
                    ]
                );
            }

            if (isset($validations["regex"]) && (!preg_match($validations["regex"], $value) === true)) {
                $errors[] = Lang::get('ModelValidationMessages.basic.ERR_INVALID_REGEX');
            }
        }

        return $errors;
    }

    public static function isValidDataWithTheValue($fieldDefinition, $value)
    {
        $type = $fieldDefinition['type'] ?? null;

        switch ($type) {
            case 'number':
                return is_numeric($value);
            case 'int':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'boolean':
                return is_bool($value) || (is_int($value) && ($value == 0 || $value == 1));
            case 'timestamp':
                return strtotime($value) != false;
            case 'enum':
                return (is_string($value) || is_numeric($value))
                    && isset($fieldDefinition["values"])
                    && ModelValidator::in_array_r($value, $fieldDefinition["values"], 'value');
            case 'model':
                return is_int($value)
                    || is_array($value)
                    || is_string($value); // TODO : Need to enhance this
            case 'email':
                return is_string($value)
                    && filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'checkbox':
                return  filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'radio':
                return is_string($value);
            case 'json':
                return is_array($value);
            case 'phone':
                return ModelValidator::checkPhoneNo($value, $fieldDefinition["validations"]["isRequired"] ?? false);
            case 'avatar':
                return is_numeric($value);
            case 'month':
                return is_numeric($value);
            case 'switch':
                return is_bool($value) || (is_int($value) && ($value == 0 || $value == 1));
            default:
                return is_string($value)
                    || is_array($value)
                    || is_int($value);
        }
    }

    public static function in_array_r($needle, $haystack, $field)
    {
        foreach ($haystack as $item) {
            if ($item[$field] == $needle) {
                return true;
            }
        }

        return false;
    }

    public static function checkPhoneNo($value, $required)
    {
        $contactNoArray = explode("-", $value);

        $isEmptyPhone = strlen($value) === 0 && !$required;
        $isCodePhone = count($contactNoArray) === 2;
        $isCodeNo = $isCodePhone && is_numeric($contactNoArray[0]);
        $isPhoneNo = $isCodePhone && is_numeric($contactNoArray[1]) && strlen($contactNoArray[1]) === 10;

        if ($isEmptyPhone) {
            return true;
        } else {
            return $isCodeNo && $isPhoneNo;
        }
    }
}
