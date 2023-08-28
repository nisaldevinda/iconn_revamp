<?php

namespace App\Library;

use App\Exceptions\StoreException;
use App\Exceptions\Exception;
use Log;
use App\Library\Util;
use App\Exceptions\ModelException;
use App\Jobs\EffectiveDatedFieldsUpdatingJob;
use App\Library\AuditLogger;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Library\Session;
use Illuminate\Support\Facades\Queue;
use App\Traits\Crypter;

/**
 * Store
 *
 * Store is class for persist application data
 */
class Store
{

    use Crypter;
    private $session;
    private $aduitLog;

    private const INSERT_MODE = 'insert';

    private const UPDATE_MODE = 'update';

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->aduitLog = new AuditLogger($session);
    }

    /**
     * Inserting record into the database table
     *
     * @param  $model object of App\Library\Model
     * @param  $data model data
     * @param  $fetch boolean for return inserted row
     * @return object | array | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $data => [
     *      'code' => 'INV001',
     *      'name' => 'Smith',
     *      'age' => 26
     * ]
     * $fetch => true
     *
     * Sample output:
     * {
     *      'id' => '1',
     *      'code' => 'INV001',
     *      'name' => 'Smith',
     *      'age' => 26,
     *      'createdAt' => 1477919230,
     *      'updatedAt' => 1477919230,
     *      'createdBy' => 1,
     *      'updatedBy' => 1,
     * }
     *
     */
    public function insert(Model $model, $data, $fetch = false)
    {
        DB::beginTransaction();

        try {
            $response = [];
            $modelName = $model->getName();

            if (is_null($modelName)) {
                throw new ModelException('Invalid model definition (model name not defined)');
            }

            $modelAttributes = $model->getAttributes();
            $tableColumns = Schema::getColumnListing($modelName);

            if ($this->session->permission->hasEnabledFieldFilter()) {
                $writeableFields = $this->session->permission->writeableFields($modelName, $modelAttributes);
                if (empty($writeableFields)) {
                    // if writable field is empty
                    throw new ModelException("Access to the requested resource is forbidden ($modelName Model)", 403);
                }

                $requiredFields = $model->getRequiredFields();
                $missingRequired = array_diff($requiredFields, $writeableFields);
                if (!empty($missingRequired)) {
                    // if required fields are missing
                    throw new ModelException("Access to reqired fields of the requested resource is forbidden ($modelName Model)", 403);
                }

                $writeableTableColumns = array_intersect($tableColumns, $writeableFields);
                $storableAttributeKeys = array_intersect($writeableTableColumns, $modelAttributes);
            } else {
                $storableAttributeKeys = array_intersect($tableColumns, $modelAttributes);
            }

            $belongsToRelationAttributes = $model->getRelations(RelationshipType::BELONGS_TO);
            $hasManyRelationAttributes = $model->getRelations(RelationshipType::HAS_MANY);

            $data = $this->setDefalutAttributes($model, $data, self::INSERT_MODE);
            $primaryTableData = [];
            $foreignTableData = [];

            $actualAttributeDefinition = (array) $model->mapActualFieldNamesWithDefinitions();
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                if (in_array($key, $storableAttributeKeys)) {
                    $primaryTableData[$key] = $value;
                    continue;
                }

                if (in_array($key, $belongsToRelationAttributes)) {
                    $foreignTableData[$key] = $value;
                    continue;
                }

                if (in_array($key, $hasManyRelationAttributes)) {
                    $foreignTableData[$key] = $value;
                    continue;
                }
            }

            $primaryTableData = $this->setEncriptValuesToFields($primaryTableData,  $actualAttributeDefinition);

            // store primary table data
            $id = DB::table($modelName)->insertGetId($primaryTableData);
            if ($fetch) {
                $_response = DB::table($modelName)->where('id', $id)->first();
                $_response = (array) $_response;
                $response = (array) $this->decryptValuesOfEncriptionFields($_response, $actualAttributeDefinition);
            }

            // insert audit log data
            $this->aduitLog->logData($model, $response, "CREATE");

            // store relational data
            foreach ($foreignTableData as $fieldName => $relationalData) {
                $relationModal = $model->getRelationModal($fieldName);
                $relationType = $model->getRelationType($fieldName);
                $foreignKeyName = $model->getForeignKey($fieldName);

                switch ($relationType) {
                    case RelationshipType::HAS_MANY:
                        foreach ($relationalData as $record) {
                            $record[$foreignKeyName] = $id;
                            $response[$fieldName][] = $this->insert($relationModal, $record, $fetch);
                        }
                        break;
                    case RelationshipType::BELONGS_TO:
                        $relationalData[$foreignKeyName] = $id;
                        $response[$fieldName] = $this->insert($relationModal, $relationalData, $fetch);
                        break;
                }
            }

            // process current records reference of effective date considerable multi records fields
            $response = $this->handleEffectiveDateConsiderableValues($model, $id, $response);

            DB::commit();
            return $fetch ? $response : true;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    private function setEncriptValuesToFields($primaryTableData,  $actualAttributeDefinition)
    {
        $dataSet = [];

        foreach ($primaryTableData as $key => $value) {
            $definition = (isset($actualAttributeDefinition[$key])) ?  (array) $actualAttributeDefinition[$key] : [];
            if (!empty($definition) && isset($definition['isEncripted']) && $definition['isEncripted']) {
                $primaryTableData[$key] = $this->encrypt($value);
            }
        }

        return $primaryTableData;
    }

    /**
     * Update record by record id
     *
     * @param  $model object of App\Library\Model
     * @param  $id record id
     * @param  $data model data for update
     * @param  $fetch boolean for return inserted row
     * @return object | boolean | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $id => 1
     * $data => [
     *      'age' => 29
     * ]
     * $fetch => true
     *
     * Sample output:
     * {
     *      'id' => '1',
     *      'code' => 'INV001',
     *      'name' => 'Smith',
     *      'age' => 29,
     *      'createdAt' => 1477919240,
     *      'updatedAt' => 1477919240,
     *      'createdBy' => 1,
     *      'updatedBy' => 1,
     * }
     *
     */
    public function updateById($model, $id, $data, $fetch = false)
    {
        DB::beginTransaction();

        try {
            $response = [];
            $modelName = $model->getName();

            if (is_null($modelName)) {
                throw new ModelException('Exception occurred while updating: Invalid model definition (model name not defined)');
            }

            $data = $this->setDefalutAttributes($model, $data, self::UPDATE_MODE);
            $data = (array) $data;

            if (empty($id)) {
                throw new StoreException('Exception occurred while updating: Document id is empty');
            }

            $record = DB::table($modelName)->where('id', $id)->first();

            if (empty($record)) {
                throw new StoreException('Exception occurred while updating: Document not exist', 404);
            }

            $modelAttributes = $model->getAttributes();
            $tableColumns = Schema::getColumnListing($modelName);
            $actualAttributeDefinition = (array) $model->mapActualFieldNamesWithDefinitions();

            if ($this->session->permission->hasEnabledFieldFilter()) {
                $writeableFields = $this->session->permission->writeableFields($modelName, $modelAttributes);
                if (empty($writeableFields)) { // if writable field is empty
                    throw new ModelException("Access to the requested resource is forbidden {($modelName)}", 403);
                }

                $writeableTableColumns = array_intersect($tableColumns, $writeableFields);
                $storableAttributeKeys = array_intersect($writeableTableColumns, $modelAttributes);
            } else {
                $storableAttributeKeys = array_intersect($tableColumns, $modelAttributes);
            }

            $belongsToRelationAttributes = $model->getRelations(RelationshipType::BELONGS_TO);
            $hasManyRelationAttributes = $model->getRelations(RelationshipType::HAS_MANY);

            $primaryTableData = [];
            $foreignTableData = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $storableAttributeKeys) && !is_array($value)) {
                    $primaryTableData[$key] = $value;
                    continue;
                }

                if (in_array($key, $belongsToRelationAttributes)) {
                    $foreignTableData[$key] = $value;
                    continue;
                }

                if (in_array($key, $hasManyRelationAttributes)) {
                    $foreignTableData[$key] = $value;
                    continue;
                }
            }

            $data['id'] = $id;

            //set encripted values for data set
            $primaryTableData = $this->setEncriptValuesToFields($primaryTableData,  $actualAttributeDefinition);

            // insert audit log data
            $this->aduitLog->logData($model, $data, "UPDATE");

            // update primary table data
            $affectedRows = DB::table($modelName)->where('id', $id)->update($primaryTableData);

            if ($fetch) {
                $_response = DB::table($modelName)->where('id', $id)->first();
                $_response = (array) $_response;
                $response = (array) $this->decryptValuesOfEncriptionFields($_response, $actualAttributeDefinition);
            }

            // update relational data
            foreach ($foreignTableData as $fieldName => $relationalData) {
                $relationModal = $model->getRelationModal($fieldName);
                $relationType = $model->getRelationType($fieldName);
                $foreignKeyName = $model->getForeignKey($fieldName);

                switch ($relationType) {
                    case RelationshipType::HAS_MANY:
                        $existingDataSet = DB::table($relationModal->getName())
                            ->where($foreignKeyName, $id)
                            ->get();
                        $existingDataSet = json_decode($existingDataSet, true);

                        // inserting new records and update existing records
                        foreach ($relationalData as $record) {
                            $record[$foreignKeyName] = $id;
                            $isNewRecord = !isset($record['id']) || $record['id'] == '' || $record['id'] == 'new';

                            if ($isNewRecord) {
                                $response[$fieldName][] = $this->insert($relationModal, $record, $fetch);
                            } else {
                                $existingDataSet = array_filter($existingDataSet, function ($value, $index) use ($record) {
                                    return $value['id'] != $record['id'];
                                }, ARRAY_FILTER_USE_BOTH);

                                $response[$fieldName][] = $this->updateById($relationModal, $record['id'], $record, $fetch);
                            }
                        }

                        // delete not existing records
                        foreach ($existingDataSet as $record) {
                            $this->deleteById($relationModal, $record['id']);
                        }
                        break;
                    case RelationshipType::BELONGS_TO:
                        $relationalData[$foreignKeyName] = $id;

                        $existingDataSet = DB::table($relationModal->getName())
                            ->where($foreignKeyName, $id)
                            ->first();

                        if (!empty($existingDataSet)) {
                            $response[$fieldName] = $this->updateById($relationModal, $relationalData['id'], $relationalData, $fetch);
                        } else {
                            $response[$fieldName] = $this->insert($relationModal, $relationalData, $fetch);
                        }
                        break;
                }
            }
            // process current records reference of effective date considerable multi records fields
            $response = $this->handleEffectiveDateConsiderableValues($model, $id, $response);

            DB::commit();
            return $fetch ? $response : true;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    /**
     * Delete record by record id
     *
     * @param  $model object of App\Library\Model
     * @param  $id record id
     * @param  $softDelete boolean for soft delete
     * @return boolean | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $id => 1
     *
     * Sample output:
     * true
     *
     */
    public function deleteById($model, $id, $softDelete = false)
    {
        try {

            $modelName = $model->getName();

            if (is_null($modelName)) {
                throw new ModelException('Exception occurred while deleting: Invalid model definition (model name not defined)');
            }

            if (empty($id)) {
                throw new StoreException('Exception occurred while deleting: Document id is empty');
            }

            $record = DB::table($modelName)->where('id', $id)->first();

            if (empty($record)) {
                throw new StoreException('Exception occurred while deleting: Document not exist', 404);
            }

            if (!$softDelete) {
                $affectedRows = DB::table($modelName)->where('id', $id)->delete();
                return ($affectedRows) ? true : false;
            }

            // check whether model compatible for soft delete
            if (!isset($record->isDelete)) {
                throw new StoreException("'$modelName' model does not support soft delete");
            }

            $data['id'] = $id;

            $affectedRows = DB::table($modelName)->where('id', $id)->update(['isDelete' => true]);

            // insert audit log data
            $this->aduitLog->logData($model, $data, "DELETE", $record);

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get record by record id
     *
     * @param  $model object of App\Library\Model
     * @param  $id record id
     * @param  $columns array $columns
     * @param  $with relations as array
     * @return object | null | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $id => 1
     * $columns => ['id', 'email']
     *
     * Sample output:
     * object | null
     *
     */
    public function getById(Model $model, $id, $columns = [], $with = [], $bypassFieldLevelAccess = false)
    {
        try {
            $modelName = $model->getName();
            $actualModelDefinition = (array) $model->mapActualFieldNamesWithDefinitions();

            if (!$bypassFieldLevelAccess && $this->session->permission->hasEnabledFieldFilter()) {
                $columns = empty($columns) ? $this->session->permission->readableFields($modelName) : $this->session->permission->selectedReadableFields($modelName, $columns);
            } else {
                $columns = empty($columns) ? ['*'] : array_merge($columns, ['id']);
            }

            // bind computed fields
            $columnSet = $this->bindDeriveFields($model, $columns);

            if (is_null($modelName)) {
                throw new ModelException('Invalid model definition (model name not defined)');
            }

            $result = DB::table($modelName)->where('id', $id)->first($columnSet);
            $result = (object) $this->decryptValuesOfEncriptionFields((array) $result, $actualModelDefinition);
            if (empty($with) || is_null($result)) {
                return $result;
            }

            foreach ($with as $relation) {
                $result->$relation = $this->getRelationalDataForRecord($model, $relation, $result, ["*"], $bypassFieldLevelAccess);
            }

            return $result;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get all records
     *
     * @param  $model object of App\Library\Model
     * @param  $columns columns as array
     * @param  $options options as array
     * @param  $with relations as array
     * @return \Illuminate\Support\Collection | Exception
     *
     * Usage:
     *
     * $model => Model $userModel
     * $columns => ['id', 'email']
     * $options => ['order' => ['id', 'DESC'], 'offset' => 10, 'limit' => 10]
     * $customWhereClauses => ['where' => [['genderId', '=', 1]], 'whereIn' => ['id' => [1, 2, 3]]]
     * $with => ['employee']
     *
     * Sample output:
     * \Illuminate\Support\Collection
     *
     */
    public function getAll($model, $columns = [], $options = [], $with = [], $customWhereClauses = null)
    {
        try {
            $modelName = $model->getName();

            // get foreign keys
            $foreignKeys = $model->getForeignKeys($with);

            // set default as all attributes
            $columns = empty($columns) ? ['*'] : array_merge($columns, ['id'], $foreignKeys);

            if ($this->session->permission->hasEnabledFieldFilter()) {
                $columns = $this->session->permission->selectedReadableFields($modelName, $columns);
            }

            // bind computed fields
            $columnSet = $columns; //$this->bindDeriveFields($model, $columns);

            if (is_null($modelName)) {
                throw new ModelException('Invalid model definition (model name not defined)');
            }

            $where = [];
            $whereIn = [];

            $where = isset($customWhereClauses['where']) ? $customWhereClauses['where'] : [];
            $whereIn = isset($customWhereClauses['whereIn']) ? $customWhereClauses['whereIn'] : [];

            if (!isset($customWhereClauses['where']) && !isset($customWhereClauses['whereIn'])) {
                $where = $customWhereClauses;
            }

            if (empty($options) && empty($customWhereClauses)) {
                $results = DB::table($modelName)->get($columnSet);
            } else if (empty($options)) {
                $results = DB::table($modelName);
                if (!empty($where)) {
                    $results = $results->where($where);
                }
                foreach ($whereIn as $key => $value) {
                    $results = $results->whereIn($key, $value);
                }
                $results = $results->get($columnSet);
            } else { // with paginated & ordered data
                $results = $this->getResultsWithOptions($model, $columns, $options, ['where' => $where, 'whereIn' => $whereIn], $with);
                return $results;
            }

            foreach ($with as $relation) {
                $this->getRelationalData($model, $relation, $results);
            }

            return $results;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Get paginated & ordered data
     *
     * @param  $model object of App\Library\Model
     * @param  array $columns
     * @param  array $options
     * @param  array $customWhereClauses
     * @return \Illuminate\Support\Collection | Exception
     */
    private function getResultsWithOptions($model, $columns, $options, $customWhereClauses = [], $with = [])
    {

        try {
            $modelName = $model->getName();
            $queryBuilder = DB::table($modelName);
            if (!empty(json_decode($options["sorter"], true))) {
                $sortKey = array_keys(json_decode($options["sorter"], true))[0];
                $sortType = array_values(json_decode($options["sorter"], true))[0];

                if ($sortType == "descend") {
                    $queryBuilder = $queryBuilder->orderByDesc($sortKey);
                } else {
                    $queryBuilder = $queryBuilder->orderBy($sortKey);
                }
            }

            if (!empty($customWhereClauses['where']) && sizeof($customWhereClauses['where'])) {
                $queryBuilder = $queryBuilder->where($customWhereClauses['where']);
            }
            foreach ($customWhereClauses['whereIn'] as $key => $value) {
                $queryBuilder = $queryBuilder->whereIn($key, $value);
            }

            if (!empty($options["filter"])) {
                $queryBuilder = Util::addWhereClausesToQueryBuilder($queryBuilder, $columns, $options["filter"]);
            }

            if (!empty($options["keyword"]) && is_array($options["searchFields"])) {
                $queryBuilder = $queryBuilder->where(function ($query) use ($options, $model) {
                    foreach ($options["searchFields"] as $fieldName) {
                        $attribute = $model->getAttribute($fieldName);

                        if (isset($attribute["isComputedProperty"]) && $attribute["isComputedProperty"]) {
                            $fieldName = $this->deriveFieldToSQL($attribute, false, $model);
                        }

                        $value = '%' . trim(strtolower($options["keyword"])) . '%';
                        $query = $query->orWhereRaw('LOWER(' . $fieldName . ') LIKE ? ', [$value]);
                    }
                });
            }

            $total = null;
            if (!empty($options["pageSize"]) && !is_null($options["current"])) {
                $page = (int) ($options["current"] > 0 ? (int) $options["current"] - 1 : 0) * $options["pageSize"];
                $total = $queryBuilder->count();
                $queryBuilder = $queryBuilder->limit($options["pageSize"])->offset($page);
            }

            $columnSet = $this->bindDeriveFields($model, $columns);
            $results = $queryBuilder->get($columnSet);

            foreach ($with as $relation) {
                $this->getRelationalData($model, $relation, $results);
            }

            if (!empty($total)) {
                return [
                    "current" => $options["current"],
                    "pageSize" => $options["pageSize"],
                    "total" => $total,
                    "data" => $results
                ];
            }

            return $results;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new ModelException("Error occured while getting relational data ($e->getMessage())");
        }
    }

    /**
     * Get relational data for single record
     *
     * @param $model object of App\Library\Model
     * @param $relation string $relation relation to parent model
     * @param $result object parent result
     * @param $columns selected columns
     * @return collection | object | null | Exception
     */
    private function getRelationalDataForRecord($model, $relation, $result, $columns = ['*'], $bypassFieldLevelAccess = false)
    {
        try {
            $relationalModel = $model->getRelationModal($relation);
            $relationalModelName = $relationalModel->getName();
            $relationType = $model->getRelationType($relation);

            $actualRelationModelDefinition = (array) $relationalModel->mapActualFieldNamesWithDefinitions();
            if (!$bypassFieldLevelAccess) {
                $tableColumns = Schema::getColumnListing($relationalModelName);
                $permittedReadableFields = $this->session->permission->selectedReadableFields($relationalModelName, $columns);
                $columns = array_intersect($tableColumns, $permittedReadableFields);

                if (in_array("*", $permittedReadableFields)) {
                    $columns = [...$columns, "*"];
                }
            } else {
                $columns = ["*"];
            }

            switch ($relationType) {
                case RelationshipType::HAS_ONE:
                    $foreignKey = $model->getForeignKey($relation);
                    if (isset($result->$foreignKey)) {
                        $res = DB::table($relationalModelName)->where('id', $result->$foreignKey)->first($columns);
                        $resultData = (object) $this->decryptValuesOfEncriptionFields($res, $actualRelationModelDefinition);
                        return $resultData;
                    } else {
                        return null;
                    }
                    break;
                case RelationshipType::BELONGS_TO:
                    $foreignKey = $model->getName() . 'Id';
                    $res = DB::table($relationalModelName)->where($foreignKey, $result->id)->first($columns);
                    $resultData = (object) $this->decryptValuesOfEncriptionFields($res, $actualRelationModelDefinition);
                    return $resultData;
                    break;

                case RelationshipType::HAS_MANY:
                    $foreignKey = $model->getName() . 'Id';
                    $relationalDataQuery = DB::table($relationalModelName)->where($foreignKey, $result->id);

                    if ($relationalModel->isEffectiveDateConsiderableModel()) {
                        $relationalDataQuery->orderBy('effectiveDate', 'DESC');
                    }

                    $results = $relationalDataQuery->orderBy('createdAt', 'DESC')->get($columns);
                    foreach ($results as $key => $value) {
                        $value = (array) $value;
                        $results[$key] = (object) $this->decryptValuesOfEncriptionFields($value, $actualRelationModelDefinition);
                    }
                    return $results;
                    break;

                default:
                    return null;
                    break;
            }
        } catch (\Throwable $th) {
            Log::error("getRelationalDataForRecord > " . $th->getMessage());
            throw new ModelException("Error occured while getting relational data (Relation: $relation)");
        }
    }

    private function decryptValuesOfEncriptionFields($data, $actualRelationModelDefinition)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $definition = (isset($actualRelationModelDefinition[$key])) ?  (array) $actualRelationModelDefinition[$key] : [];

                if (!empty($definition) && isset($definition['isEncripted']) && $definition['isEncripted']) {

                    $data[$key] = $this->decrypt($value);
                    if ($definition['type'] == 'number') {

                        if (isset($definition['validations']['isDecimal']) &&  $definition['validations']['isDecimal'] && isset($definition['validations']['precision']) && !empty($definition['validations']['precision'])) {
                            $data[$key] = number_format((float)$data[$key], $definition['validations']['precision'], '.', '');
                        } else {
                            $data[$key] = (float)$data[$key];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get relational data for multiple records
     *
     * @param $model object of App\Library\Model
     * @param $relation string $relation relation to parent model
     * @param $results object parent result
     * @param $columns selected columns
     * @return collection | object | null | Exception
     */
    private function getRelationalData($model, $relation, $results, $columns = ['*'])
    {
        try {
            $relationalModelName = $model->getRelationModal($relation)->getName();
            $relationType = $model->getRelationType($relation);
            $columnSet = $this->bindDeriveFields($model, $columns);
            $relationalModel = $model->getRelationModal($relation);
            $actualRelationModelDefinition = (array) $relationalModel->mapActualFieldNamesWithDefinitions();

            switch ($relationType) {
                case RelationshipType::BELONGS_TO:
                    $foreignKey = $model->getForeignKey($relation);
                    $values = [];
                    foreach ($results as $record) {
                        $foreignKeyValue = $record->$foreignKey;
                        if (!empty($foreignKeyValue)) {
                            array_push($values, $foreignKeyValue);
                        }
                    }
                    $relationalResult = DB::table($relationalModelName)->whereIn('id', $values)->get($columnSet);
                    foreach ($results as $result) {
                        $relateRecord = (array) $relationalResult->where('id', $result->$foreignKey)->first();
                        $decryptData = (object) $this->decryptValuesOfEncriptionFields($relateRecord, $actualRelationModelDefinition);
                        $result->$relation = $decryptData;
                    }
                    //return $result;
                    break;

                case RelationshipType::HAS_ONE:
                    $foreignKey = $relation . 'Id';
                    $values = [];
                    foreach ($results as $record) {
                        if (isset($record->$foreignKey)) {
                            array_push($values, $record->$foreignKey);
                        }
                    }
                    $relationalResult = DB::table($relationalModelName)->whereIn('id', array_values($values))->get();
                    foreach ($results as $result) {
                        if (isset($result->$foreignKey)) {
                            $res = (array) $relationalResult->where('id', $result->$foreignKey)->first();
                            $decryptData = (object) $this->decryptValuesOfEncriptionFields($res, $actualRelationModelDefinition);
                            $result->$relation = $decryptData;
                        } else {
                            $result->$relation = null;
                        }
                    }
                    // return $result;
                    break;

                case RelationshipType::HAS_MANY:
                    $foreignKey = $model->getName() . 'Id';
                    $values = [];
                    foreach ($results as $record) {
                        array_push($values, $record->id);
                    }
                    $relationalResult = DB::table($relationalModelName)->whereIn($foreignKey, array_values($values))->get();
                    foreach ($results as $result) {
                        $resData = $relationalResult->where($foreignKey, $result->id);
                        if (sizeof($resData) > 0) {
                            foreach ($resData as $val) {
                                $val = (array) $val;
                                $decryptData = (object) $this->decryptValuesOfEncriptionFields($val, $actualRelationModelDefinition);
                                $result->$relation[] = $decryptData;
                            }
                        } else {
                            $result->$relation = $resData;
                        }
                    }
                    // return $result;
                    break;

                default:
                    return null;
                    break;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new ModelException("Error occured while getting relational data (Relation: $relation)");
        }
    }

    /**
     * return Illuminate\Support\Facades\DB
     */
    public function getFacade()
    {
        return DB::class;
    }

    /**
     * Set system default attributes to user input
     *
     * @param  $model object of App\Library\Model
     * @param  $data model data for update
     * @param  $mode string insert | update
     * @return array
     *
     * Usage:
     *
     * $model => Model $userModel
     * $data => [
     *      'code' => 'INV001',
     *      'name' => 'Smith',
     *      'age' => 26
     * ]
     * $mode => self::INSERT_MODE | self::UPDATE_MODE
     *
     * Sample output:
     * [
     *      'id' => '1',
     *      'code' => 'INV001',
     *      'name' => 'Smith',
     *      'age' => 26,
     *      'createdAt' => 1477919230,
     *      'updatedAt' => 1477919230,
     *      'createdBy' => 1,
     *      'updatedBy' => 1,
     * ]
     *
     */
    private function setDefalutAttributes($model, $data, $mode)
    {
        $loggedInUser = (array) $this->session->getUser();
        $userId = 0;
        if (array_key_exists('id', $loggedInUser)) {
            $userId = $loggedInUser['id'];
        }

        switch ($mode) {
            case self::INSERT_MODE:
                $model->hasAttribute('createdAt') ? $data['createdAt'] = Carbon::now()->toDateTimeString() : null;
                $model->hasAttribute('updatedAt') ? $data['updatedAt'] = Carbon::now()->toDateTimeString() : null;
                $model->hasAttribute('updatedBy') ? $data['updatedBy'] = $userId : null;
                $model->hasAttribute('createdBy') ? $data['createdBy'] = $userId : null;

                return $data;
                break;

            case self::UPDATE_MODE:
                $model->hasAttribute('updatedAt') ? $data['updatedAt'] = Carbon::now()->toDateTimeString() : null;
                $model->hasAttribute('updatedBy') ? $data['updatedBy'] = $userId : null;
                return $data;
                break;

            default:
                return $data;
        }
    }

    private function bindDeriveFields(Model $model, $columns)
    {
        $deriveFields = [];

        // if (in_array("*", $columns)) {
        $fields = array_values($model->toArray()["fields"]);
        $computedFields = array_filter($fields, function ($field) {
            return isset($field["isComputedProperty"]) && $field["isComputedProperty"];
        });

        foreach ($computedFields as $computedField) {
            $sql = $this->deriveFieldToSQL($computedField, true, $model);
            $columns = array_diff($columns, [$computedField['name']]);

            if (!empty($sql)) {
                $deriveFields[] = $sql;
            }
        }
        // }

        return array_merge($columns, $deriveFields);
    }

    private function deriveFieldToSQL($computedField, $withAliases = true, $model = null)
    {
        $select = null;
        $fieldName = $computedField['name'];

        if (
            isset($computedField["name"])
            && isset($computedField["concatFields"])
            && is_array($computedField["concatFields"])
            && !empty($computedField["concatFields"])
        ) {
            $concatFields = implode(', ', $computedField["concatFields"]);
            $select = "CONCAT_WS(' ', $concatFields)";
        } else if (
            !empty($computedField["name"])
            && !empty($computedField["condition"])
            && !empty($computedField["duration"])
        ) {
            $parentFieldName = explode('.', $computedField["condition"]["field"])[0];
            $conditionChildFieldName = explode('.', $computedField["condition"]["field"])[1];
            $durationChildFieldName = explode('.', $computedField["duration"]["expr1Field"])[1];
            $parentAttribute = $model->getAttribute($parentFieldName);
            $select = '(SELECT IF(COUNT(*) > 0 AND ' . $conditionChildFieldName
                . ' != 1 AND ' . $durationChildFieldName
                . ' < NOW(), TIMESTAMPDIFF(' . $computedField["duration"]["unit"] . ', ' . $durationChildFieldName
                . ', NOW()), 0) FROM ' . $parentAttribute['modelName'] . ' WHERE id = ' . $parentFieldName . 'Id)';
        } else if (
            !empty($computedField["name"]) && !empty($computedField["sqlFuntion"])
        ) {
            $select = $computedField["sqlFuntion"];
        }

        // TODO: need to develop derive fields here

        return $withAliases ? DB::raw("$select AS $fieldName") : DB::raw($select);
    }

    public function handleEffectiveDateConsiderableValues($model, $parentId, $response = null, $selectAttribute = [])
    {
        $effectiveDateConsiderableAttributes = $model->getEffectiveDateConsiderableAttributes();

        if (!empty($selectAttribute)) {
            $effectiveDateConsiderableAttributes = array_filter(
                $effectiveDateConsiderableAttributes,
                function ($attribute) use ($selectAttribute) {
                    return in_array($attribute['name'], $selectAttribute);
                }
            );
        }

        if (!empty($effectiveDateConsiderableAttributes)) {
            $company = (array) $this->session->getCompany();
            $companyTimeZone = $company["timeZone"] ?? null;
            $companyDateObject = new DateTime("now", new DateTimeZone($companyTimeZone));
            $companyDate = $companyDateObject->format('Y-m-d');

            $currentRecordUpdateData = [];
            foreach ($effectiveDateConsiderableAttributes as $attribute) {
                $foreignKeyName = $model->getForeignKey($attribute["name"]);
                $currentFieldName = "current" . ucfirst($attribute["name"]) . "Id";

                $records = collect(DB::table($attribute["modelName"])
                    ->where($foreignKeyName, $parentId)
                    ->whereNotNull('effectiveDate')
                    ->orderBy('effectiveDate', 'desc')
                    ->orderBy('createdAt', 'desc')
                    ->get())->toArray();
                $existingDelayedQueueJobs = collect(DB::table('delayed_queue_job')
                    ->where('parentModel', $model->getName())
                    ->where('parentRecordId', $parentId)
                    ->where('childModel', $attribute["modelName"])
                    ->where('isCancelled', false)
                    ->get())->toArray();

                $currentRecord = null;

                if (!empty($records)) {
                    foreach ($records as $record) {
                        $record = (array) $record;

                        if (!empty($currentRecord)) {
                            $targetTime = new DateTime($currentRecord["effectiveDate"], new DateTimeZone($companyTimeZone));
                            $targetTimestamp = $targetTime->getTimestamp();
                            $executeAt = Carbon::createFromTimestamp($targetTimestamp);

                            $payload = [
                                'parentModel' => $model->getName(),
                                'parentRecordId' => (int) $parentId,
                                'childModel' => $attribute["modelName"],
                                'childRecordId' => $currentRecord["id"],
                                'effectiveDate' => $currentRecord["effectiveDate"],
                                'updatedData' => [
                                    $currentFieldName => $record["id"]
                                ],
                                'tenantId' => $this->session->getTenantId()
                            ];

                            $oldJobRecord = null;
                            if (!empty($existingDelayedQueueJobs)) {
                                $index = array_search($currentRecord["id"], array_column($existingDelayedQueueJobs, 'childRecordId'));
                                if ($index > -1) {
                                    $oldJobRecord = (array) $existingDelayedQueueJobs[$index];
                                }
                            }

                            // TODO: employee active status not handle for feature dated jobs
                            if (!empty($oldJobRecord) && $oldJobRecord['executeAt'] != $targetTimestamp) {
                                DB::table('delayed_queue_job')
                                    ->where('parentModel', $model->getName())
                                    ->where('parentRecordId', $parentId)
                                    ->where('childModel', $attribute["modelName"])
                                    ->where('childRecordId', $currentRecord["id"])
                                    ->where('isCancelled', false)
                                    ->update(['isCancelled' => true]);
                            }

                            if (empty($oldJobRecord) || $oldJobRecord['executeAt'] != $targetTimestamp) {
                                $job = new EffectiveDatedFieldsUpdatingJob($payload);
                                $queueJobId = Queue::later($executeAt, $job);

                                unset($payload['effectiveDate']);
                                unset($payload['updatedData']);
                                unset($payload['tenantId']);
                                $payload['queueJobId'] = $queueJobId;
                                $payload['executeAt'] = $targetTimestamp;

                                DB::table('delayed_queue_job')->insert($payload);
                            }
                        }

                        $currentRecord = $record;

                        if (strtotime($record["effectiveDate"]) <= strtotime($companyDate)) {
                            break;
                        }
                    }
                }

                // cancel not existing jobs
                $cancelingJobIds = array_values(
                    array_map(
                        function ($job) {
                            return $job->id;
                        },
                        array_filter(
                            $existingDelayedQueueJobs,
                            function ($job) use ($records) {
                                return !in_array($job->childRecordId, array_column($records, 'id'));
                            }
                        )
                    )
                );

                DB::table('delayed_queue_job')
                    ->where('id', $cancelingJobIds)
                    ->update(['isCancelled' => true]);

                $currentRecordUpdateData[$currentFieldName] = $currentRecord["id"] ?? null;

                if (!empty($response)) {
                    $response[$currentFieldName] = $currentRecordUpdateData[$currentFieldName];
                }
            }

            DB::table($model->getName())
                ->where('id', $parentId)
                ->update($currentRecordUpdateData);
        }

        return !empty($response) ? $response : true;
    }

    public function getRelationallyDependentRecords(Model $model, $id)
    {
        $belongsToRelationAttributes = $model->getRelations(RelationshipType::BELONGS_TO);

        $records = [];
        foreach ($belongsToRelationAttributes as $relation) {
            $attributeData = $model->getAttribute($relation);

            if (empty($attributeData) && empty($attributeData['modelName']) && empty($attributeData['foreignKeyAttribute'])) continue;

            $foreignKey =$attributeData['foreignKeyAttribute'] . 'Id';
            $query = DB::table($attributeData['modelName'])
                ->where($foreignKey, $id)
                ->first();
            array_push($records, $query);
        }

        $records = array_filter($records);
        return $records;
    }
}
