<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Name: DynamicFormService
 * Purpose: Performs tasks related to the DynamicForm model.
 * Description: DynamicForm Service class is called by the DynamicFormController where the requests related
 * to DynamicForm Model (basic operations and others). Table that is being modified is dynamicForm.
 * Module Creator: Chalaka
 */
class DynamicFormService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $dynamicFormModel;
    private $frontEndDefinitionModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->dynamicFormModel = $this->getModel('dynamicModel', true);
        $this->frontEndDefinitionModel = $this->getModel('frontEndDefinition', true);
    }

    /**
     * Following function creates a DynamicForm.
     *
     * @param $DynamicForm array containing the DynamicForm data
     * @return int | String | array
     *
     * Usage:
     * $DynamicForm => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "dynamicForm created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */
    public function createDynamicForm($dynamicForm)
    {
        try {
            $modelName = time() . '_' . Util::toCamelCase($dynamicForm['formName']);
            $dynamicModel = [
                'modelName' => $modelName,
                'title' => $dynamicForm['formName'],
                'description' => $dynamicForm['description'],
                'isDynamicMasterDataModel' => true,
                'dynamicModel' => json_encode([
                    'name' => $modelName,
                    'path' => '/dynamic/' . $modelName,
                    'hasSoftDelete' => true,
                    'hasFrontEndDefinition' => true,
                    'fields' => [
                        'id' => [
                            'name' => 'id',
                            'defaultLabel' => 'ID',
                            'labelKey' => 'ID',
                            'type' => 'number',
                            'isEditable' => false,
                            'isSystemValue' => true
                        ],
                        'isDelete' => [
                            'name' => 'isDelete',
                            'defaultLabel' => 'Is Deleted',
                            'labelKey' => 'IS_DELETE',
                            'type' => 'boolean',
                            'isSystemValue' => true
                        ],
                        'createdBy' => [
                            'name' => 'createdBy',
                            'defaultLabel' => 'Created By',
                            'labelKey' => 'CREATED_BY',
                            'type' => 'string',
                            'isSystemValue' => true
                        ],
                        'updatedBy' => [
                            'name' => 'updatedBy',
                            'defaultLabel' => 'Updated By',
                            'labelKey' => 'UPDATED_BY',
                            'type' => 'string',
                            'isSystemValue' => true
                        ],
                        'createdAt' => [
                            'name' => 'createdAt',
                            'defaultLabel' => 'Created At',
                            'labelKey' => 'CREATED_AT',
                            'type' => 'string',
                            'isSystemValue' => true
                        ],
                        'updatedAt' => [
                            'name' => 'updatedAt',
                            'defaultLabel' => 'Updated At',
                            'labelKey' => 'UPDATED_AT',
                            'type' => 'string',
                            'isSystemValue' => true
                        ]
                    ],
                    'relations' => []
                ])
            ];

            $validationResponse = ModelValidator::validate($this->dynamicFormModel, $dynamicModel, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('dynamicFormMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $frontEndDefinition = [
                'modelName' => $modelName,
                'topLevelComponent' => $dynamicForm['numberOfTabs'] == 'single' ? 'section' : 'tab',
                'structure' => json_encode([])
            ];

            $validationResponse = ModelValidator::validate($this->frontEndDefinitionModel, $frontEndDefinition, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('dynamicFormMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $this->store->insert($this->dynamicFormModel, $dynamicModel, true);
            $this->store->insert($this->frontEndDefinitionModel, $frontEndDefinition, true);

            return $this->success(201, Lang::get('dynamicFormMessages.basic.SUCC_CREATE'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives all dynamicForms.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "dynamicForm created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getAllDynamicForms($permittedFields, $options)
    {
        try {
            // $filteredDynamicForms = $this->store->getAll(
            //     $this->dynamicFormModel,
            //     $permittedFields,
            //     $options,
            //     [],
            //     [['isDelete', '=', false]]
            // );
            // return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_ALL_RETRIVE'), $filteredDynamicForms);

            $dynamicForms = $this->store->getFacade()::table('dynamicModel')
                ->select(
                    'dynamicModel.modelName',
                    'dynamicModel.description',
                    DB::raw("CONCAT_WS('/', dynamicModel.modelName, frontEndDefinition.alternative) AS id"),
                    DB::raw("CONCAT_WS(' - ', dynamicModel.title, frontEndDefinition.alternative) AS title"),
                )
                ->join('frontEndDefinition', 'frontEndDefinition.modelName', '=', 'dynamicModel.modelName')
                ->where('isDelete', false);

            $response = isset($options['pageSize']) && !empty($options['pageSize'])
                ? [
                    "total" => $dynamicForms->count(),
                    "data" => $dynamicForms->get()
                ]
                : $dynamicForms->get();

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_ALL_RETRIVE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single DynamicForm for a provided id.
     *
     * @param $id dynamicForm id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getDynamicForm($id)
    {
        try {
            $dynamicForm = $this->store->getFacade()::table($this->dynamicFormModel->getName())->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dynamicForm)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_GENDER'), $dynamicForm);
            }

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_SINGLE_RETRIVE'), $dynamicForm);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single dynamicForm for a provided id.
     *
     * @param $id dynamicForm id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "dynamicForm created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getDynamicFormByKeyword($keyword)
    {
        try {
            $dynamicForm = $this->store->getFacade()::table($this->dynamicFormModel->getName())->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_ALL_RETRIVE'), $dynamicForm);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function updates a dynamicForm.
     *
     * @param $id dynamicForm id
     * @param $DynamicForm array containing DynamicForm data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "dynamicForm updated successfully.",
     *      $data => {"id": 1, name": "Male"} // has a similar set of data as entered to updating DynamicForm.
     *
     */
    public function updateDynamicForm($modelName, $alternative, $dynamicForm)
    {
        try {
            // update dynamic model (dynamicForm) table
            $existingModelRecord = collect($this->store->getFacade()::table('dynamicModel')
                ->where('modelName', $modelName)
                ->first())
                ->toArray();

            if (empty($existingModelRecord)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_DYNAMIC_MODEL'), $existingModelRecord);
            }

            $existingModel = json_decode($existingModelRecord['dynamicModel'], true) ?? [];
            if (empty($modelName) || empty($existingModel)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_DYNAMIC_MODEL'), $existingModelRecord);
            }

            $newModel = $dynamicForm['model'] ?? [];
            $newModelRecord = $existingModelRecord;
            $newModelRecord['dynamicModel'] = json_encode($newModel);
            $updatedModelRecord = $this->store->updateById($this->dynamicFormModel, $newModelRecord['id'], $newModelRecord, true);

            // update layout (frontEndDefinition) table
            $existingLayoutRecord = collect($this->store->getFacade()::table('frontEndDefinition')
                ->where('modelName', $modelName)
                ->where('alternative', $alternative)
                ->first())
                ->toArray();

            if (empty($existingLayoutRecord)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_MODEL_LAYOUT'), $existingLayoutRecord);
            }

            $existingLayout = $existingLayoutRecord['structure'] ?? [];
            if (empty($existingLayout)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_MODEL_LAYOUT'), $existingLayoutRecord);
            }

            $newLayout = $dynamicForm['layout'] ?? [];
            $newLayoutRecord = $existingLayoutRecord;
            $newLayoutRecord['structure'] = json_encode($newLayout);
            $updatedLayoutRecord = $this->store->updateById($this->frontEndDefinitionModel, $newLayoutRecord['id'], $newLayoutRecord, true);

            // handle db table schema modifications
            $queryLog = $this->handleTableSchemaChanges($newModel);

            DB::table('formBuilderLog')->insert([
                'modelName' => $modelName,
                'dynamicModelId' => $existingModelRecord['id'],
                'frontEndDefinitionId' => $existingLayoutRecord['id'],
                'queryLog' => json_encode($queryLog)
            ]);

            $response = [
                'model' => $updatedModelRecord,
                'layout' => $updatedLayoutRecord
            ];

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_UPDATE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id dynamicForm id
     * @param $DynamicForm array containing DynamicForm data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "dynamicForm deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteDynamicForm($modelName)
    {
        try {
            $dbDynamicForm = $this->store->getFacade()::table('dynamicModel')
                ->where('modelName', $modelName)
                ->where('isDelete', false)
                ->first();

            if (empty($dbDynamicForm)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT'), null);
            }

            $existingRelations = $this->store->getFacade()::table('dynamicModel')
                ->whereRaw('JSON_EXTRACT(dynamicModel, "$.fields.*.modelName") LIKE "%' . $modelName . '%"')
                ->where('isDelete', false)
                ->get()
                ->toArray();

            if (!empty($existingRelations)) {
                return $this->error(404, Lang::get('dynamicModelMessages.basic.ERR_CANT_DELETE'), null);
            }

            $this->store->getFacade()::table('dynamicModel')
                ->where('modelName', $modelName)
                ->update(['isDelete' => true]);

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a dynamicForm.
     *
     * @param $id dynamicForm id
     * @param $DynamicForm array containing DynamicForm data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "dynamicForm deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteDynamicForm($id)
    {
        try {
            $dbDynamicForm = $this->store->getById($this->dynamicFormModel, $id);
            if (is_null($dbDynamicForm)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }

            $this->store->deleteById($this->dynamicFormModel, $id);

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_DELETE'), null);
        }
    }

    private function handleTableSchemaChanges($newModel)
    {
        $queryLog = null;
        $modelName = $newModel['name'];
        $newFieldSet = $newModel['fields'];
        $updatedRelations = $newModel['relations'];

        if (!Schema::hasTable($modelName)) {
            DB::enableQueryLog();

            Schema::create($modelName, function (Blueprint $table) use ($newFieldSet, $updatedRelations) {
                $table->id();

                $ignoreFields = ['id', 'isDelete', 'createdBy', 'updatedBy', 'createdAt', 'updatedAt'];
                foreach ($newFieldSet as $fieldName => $fieldProperties) {
                    if (in_array($fieldName, $ignoreFields)) continue;
                    $this->generateColumnQueryChunk($table, $fieldName, $fieldProperties, $updatedRelations);
                }

                $table->boolean('isDelete')->default(false);
                $table->integer('createdBy')->nullable()->default(null);
                $table->integer('updatedBy')->nullable()->default(null);
                $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updatedAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            });

            $queryLog = DB::getQueryLog();
        } else {
            $existingColumnList = array_values(Schema::getColumnListing($modelName));
            $newColumnList = [];

            foreach ($newFieldSet as $_ => $field) {
                if (isset($field['isNonRecordableField']) && $field['isNonRecordableField']) continue;

                if ($field['type'] == 'model') {
                    if ($updatedRelations[$field['name']] == 'HAS_ONE') {
                        $newColumnList[] = $field['name'] . 'Id';
                    }
                    continue;
                }

                $newColumnList[] = $field['name'];
            };

            $addedFieldSet = array_values(array_diff($newColumnList, $existingColumnList));
            $untrackedFieldSet = array_values(array_intersect($existingColumnList, $newColumnList));
            $removedFieldSet = array_values(array_diff($existingColumnList, $newColumnList));

            Log::info('existingColumnList', $existingColumnList);
            Log::info('newColumnList', $newColumnList);
            Log::info('addedFieldSet', $addedFieldSet);
            Log::info('untrackedFieldSet', $untrackedFieldSet);
            Log::info('removedFieldSet', $removedFieldSet);

            DB::enableQueryLog();

            Schema::table($modelName, function (Blueprint $table) use ($newFieldSet, $addedFieldSet, $untrackedFieldSet, $removedFieldSet, $updatedRelations) {
                foreach ($addedFieldSet as $fieldName) {
                    $fieldProperties = $newFieldSet[$fieldName] ?? $newFieldSet[substr($fieldName, 0, -2)];
                    $this->generateColumnQueryChunk($table, $fieldProperties['name'], $fieldProperties, $updatedRelations);
                }

                // foreach ($untrackedFieldSet as $fieldName) {
                //     $fieldProperties = $newFieldSet[$fieldName];
                //     $this->generateColumnQueryChunk($table, $fieldName, $fieldProperties, $updatedRelations, true);
                // }

                foreach ($removedFieldSet as $fieldName) {
                    $table->dropColumn($fieldName);
                }
            });

            $queryLog = DB::getQueryLog();
        }

        return $queryLog;
    }

    private function generateColumnQueryChunk(&$table, $fieldName, $fieldProperties, $relations, $change = false)
    {
        switch ($fieldProperties['type']) {
            case 'timestamp':
                $change
                    ? $table->date($fieldName)->nullable()->change()
                    : $table->date($fieldName)->nullable();
                break;
            case 'model':
                if ($relations[$fieldProperties['name']] == "HAS_ONE") {
                    $change
                        ? $table->integer($fieldName . 'Id')->nullable()->change()
                        : $table->integer($fieldName . 'Id')->nullable();
                }
                break;
            case 'boolean':
            case 'switch':
                $change
                    ? $table->boolean($fieldName)->nullable()->change()
                    : $table->boolean($fieldName)->nullable();
                break;
            case 'number':
                $change
                    ? $table->float($fieldName, 5, 2)->nullable()->change()
                    : $table->float($fieldName, 5, 2)->nullable();
                break;
            case 'string':
            case 'enum':
            case 'textArea':
            case 'timeZone':
            case 'radio':
            case 'checkbox':
            case 'currency':
            case 'tag':
            case 'phone':
            case 'month':
            default:
                $change
                    ? $table->string($fieldName)->nullable()->change()
                    : $table->string($fieldName)->nullable();
                break;
        }
    }

    /**
     * Following function update Alternative Layout.
     *
     * @param $modelName string
     * @param $alternative string
     * @param $structure array
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Alternative Layout updated successfully.",
     *      $data => {...}
     */
    public function updateAlternativeLayout($modelName, $alternative, $newLayout)
    {
        try {
            // update dynamic model (dynamicForm) table
            $existingModelRecord = collect($this->store->getFacade()::table('dynamicModel')
                ->where('modelName', $modelName)
                ->first())
                ->toArray();

            if (empty($existingModelRecord)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_DYNAMIC_MODEL'), $existingModelRecord);
            }

            // update layout (frontEndDefinition) table
            $existingLayoutRecord = collect($this->store->getFacade()::table('frontEndDefinition')
                ->where('modelName', $modelName)
                ->where('alternative', $alternative)
                ->first())
                ->toArray();

            if (empty($existingLayoutRecord)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_MODEL_LAYOUT'), $existingLayoutRecord);
            }

            $existingLayout = $existingLayoutRecord['structure'] ?? [];
            if (empty($existingLayout)) {
                return $this->error(404, Lang::get('dynamicFormMessages.basic.ERR_NONEXISTENT_MODEL_LAYOUT'), $existingLayoutRecord);
            }

            $newLayoutRecord = $existingLayoutRecord;
            $newLayoutRecord['structure'] = json_encode($newLayout);
            $updatedLayoutRecord = $this->store->updateById($this->frontEndDefinitionModel, $newLayoutRecord['id'], $newLayoutRecord, true);

            return $this->success(200, Lang::get('dynamicFormMessages.basic.SUCC_UPDATE'), $updatedLayoutRecord);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicFormMessages.basic.ERR_UPDATE'), null);
        }
    }
}
