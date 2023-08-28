<?php

namespace App\Http\Controllers;

use App\Services\ModelService;
use Illuminate\Http\Request;

/*
    Name: ModelController
    Purpose: Performs request handling tasks related to the Model model.
    Description: API requests related to the model model are directed to this controller.
    Module Creator: Hashan
*/

class ModelController extends Controller
{
    protected $modelService;

    /**
     * ModelController constructor.
     *
     * @param ModelService $modelService
     */
    public function __construct(ModelService $modelService)
    {
        $this->modelService  = $modelService;
    }

    /*
        Retrives a single model based on model key.
    */
    public function getAllModel()
    {
        $result = $this->modelService->getAllModel();
        return $this->jsonResponse($result);
    }

    /*
        Retrives a single model based on model key.
    */
    public function getModel($modelName, $alternative = null)
    {
        $result = $this->modelService->getModelByName($modelName, $alternative);
        return $this->jsonResponse($result);
    }

    /*
        Retrive template tokens
    */
    public function getTemplateTokens()
    {
        $result = $this->modelService->getTemplateTokens();
        return $this->jsonResponse($result);
    }

    /*
        Retrive template tokens
    */
    public function getWorkflowRelateTemplateTokens(Request $request)
    {
        $result = $this->modelService->getWorkflowRelateTemplateTokens($request->all());
        return $this->jsonResponse($result);
    }
}
