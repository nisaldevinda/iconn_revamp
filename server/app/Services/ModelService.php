<?php

namespace App\Services;

use App\Library\Interfaces\ModelReaderInterface;
use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\RelationshipType;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Config;

/**
 * Name: ModelService
 * Purpose: Performs tasks related to the Model model.
 * Description: Model Service class is called by the ModelController where the requests related
 * to Model Model (basic operations and others).
 * Module Creator: Hashan
 */
class ModelService extends BaseService
{
    private $store;

    private $dynamicModelModel;
    private $frontEndDefinitionModel;

    use JsonModelReader;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->dynamicModelModel = $this->getModel('dynamicModel', true);
        $this->frontEndDefinitionModel = $this->getModel('frontEndDefinition', true);
    }

    /**
     * Following function retrives a single model for a provided model_id.
     *
     * @param $modelName String
     * @return int | String | array
     *
     * Usage:
     * $modelName => 'employee
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Models retrieved Successfully!",
     *      $data => {"modelname": "John"}
     * ]
     */
    public function getModelByName($modelName, $alternative = null, $internalRequest = false)
    {
        $queryBuilder = $this->store->getFacade();

        $staticModelData = null;
        $dynamicModelData = null;

        try {
            $staticModelData = $this->getModel($modelName);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        try {
            $modelData = !empty($staticModelData) ? $staticModelData : json_decode($dynamicModelData['dynamicModel'], true);

            $response["modelDataDefinition"] = $modelData;

            $hasFrontEndDefinition = $modelData["hasFrontEndDefinition"] ?? false;

            if ($hasFrontEndDefinition) {
                $frontEndDefinitionModelName = $this->frontEndDefinitionModel->getName();
                $frontEndDefinitionModelQueryBuilder = $queryBuilder::table($frontEndDefinitionModelName);

                if (empty($alternative)) {
                    $frontEndDefinitionData = $frontEndDefinitionModelQueryBuilder
                        ->where('modelName', $modelName)
                        ->orderBy('id', 'DESC')
                        ->first();
                } else {
                    $frontEndDefinitionData = $frontEndDefinitionModelQueryBuilder
                        ->where('modelName', $modelName)
                        ->orderBy('id', 'DESC')
                        ->where('alternative', $alternative)
                        ->first();
                }

                if (!is_null($frontEndDefinitionData)) {
                    $frontEndDefinitionData->structure = json_decode($frontEndDefinitionData->structure, true);
                    $response["frontEndDefinition"] = $frontEndDefinitionData;
                }
            }

            if ($internalRequest) {
                error_log('this is internal request');
                $response = json_encode($response);
            }

            return $this->success(200, Lang::get('modelMessages.basic.SUCC_MODEL_RETRIVE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('modelMessages.basic.ERR_MODEL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives all models.
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Models retrieved Successfully!",
     *      $data => [{"modelname": "John"}, ...]
     * ]
     */
    public function getAllModel()
    {
        try {
            $defaultModels = config('models.default');
            $response = [];

            foreach ($defaultModels as $key => $model) {
                if (is_array($model)) {
                    $alternatives = [];
                    foreach ($model as $alternative) {
                        $alternatives[$alternative] = $this->getModelByName($key, $alternative)['data'];
                    }
                    $response[$key] = $alternatives;
                } else {
                    $response[$model] = $this->getModelByName($model)['data'];
                }
            }

            return $this->success(200, Lang::get('modelMessages.basic.SUCC_ALL_MODELS_RETRIVE'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('modelMessages.basic.ERR_ALL_MODELS_RETRIVE'), null);
        }
    }

    /**
     * Get all template tokens difined in the models.
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Template tokens have been retrieved successfully.",
     *      $data => ["employee_number","employee_initials"]
     * ]
     */
    public function getTemplateTokens()
    {
        try {
            $models = ['employee']; // predifined models
            $tokens = [];

            // get employee model
            $employeeModel = $this->getModel('employee', true);

            // get employee relations
            $oneToOneRelations = $employeeModel->getRelationsByRelationType(RelationshipType::HAS_ONE);

            $oneToOneRelationalModels = array_column($employeeModel->getRelationalModels($oneToOneRelations), 'model');

            // get employee one to many relations
            $onewToMenyRelations = $employeeModel->getRelationsByRelationType(RelationshipType::HAS_MANY);

            $oneToMenyRelationalModels = array_column($employeeModel->getRelationalModels($onewToMenyRelations), 'model');

            $dependantModels = [];

            array_walk($oneToMenyRelationalModels, function ($model) use (&$dependantModels) {
                $modelObj = $this->getModel($model, true);
                $relations = $modelObj->getRelationsByRelationType(RelationshipType::HAS_ONE);
                $dependantModels = array_merge($dependantModels, array_column($modelObj->getRelationalModels($relations), 'model'));
            });

            $tokenModels = array_unique(array_merge($models, $oneToOneRelationalModels, $oneToMenyRelationalModels, $dependantModels));

            foreach ($tokenModels as $modelName) {
                $model = $this->getModel($modelName, true);
                $tokens = array_merge($tokens, $model->getTokens());
            }

            return $this->success(200, Lang::get('modelMessages.basic.SUCC_MODEL_TOKEN_RETRIVE'), $tokens);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('modelMessages.basic.ERR_MODEL_TOKEN_RETRIVE'), null);
        }
    }


    /**
     * Get template tokens according to the forkflow context.
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Template tokens have been retrieved successfully.",
     *      $data => ["employee_number","employee_initials"]
     * ]
     */
    public function getWorkflowRelateTemplateTokens($data)
    {
        try {
            
            $tokens = [];
            $processedTokens = [];
            
            if (!empty($data['workflowContextId'])) {
                switch ($data['workflowContextId']) {
                    case 1:
                        //relate to profile update workflow context
                        $tokens = config('workflowToken.profile-update-request');
                        break;
                    case 2:
                        //relate to profile leave request workflow context
                        $tokens = config('workflowToken.leave-request');
                        break;
                    case 3:
                        //relate to time change request  workflow context
                        $tokens = config('workflowToken.time-change-request');
                        break;
                    case 4:
                        //relate to time change request  workflow context
                        $tokens = config('workflowToken.short_leave-request');
                        break;
                    case 5:
                        //relate to shift change request  workflow context
                        $tokens = config('workflowToken.shift-change-request');
                        break;
                    case 6:
                        //relate to cancel leave request  workflow context
                        $tokens = config('workflowToken.cancel_leave-request');
                        break;
                    case 7:
                        //relate to resignation request  workflow context
                        $tokens = config('workflowToken.resignation-request');
                        break;
                    case 8:
                        //relate to cancel short leave request  workflow context
                        $tokens = config('workflowToken.cancel-short-leave-request');
                        break;
                    case 9:
                        //relate to claim request  workflow context
                        $tokens = config('workflowToken.claim-request');
                        break;
                    case 10:
                        //relate to claim request  workflow context
                        $tokens = config('workflowToken.post-ot-request');
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }

            foreach ($tokens as $key => $value) {
                $temp = [
                    'value' => '{#'.$key.'#}',
                    'text' => $value
                ];

                $processedTokens[] = $temp;
            }

            return $this->success(200, Lang::get('modelMessages.basic.SUCC_MODEL_TOKEN_RETRIVE'), $processedTokens);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('modelMessages.basic.ERR_MODEL_TOKEN_RETRIVE'), null);
        }
    }
}
