<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;

/**
 * Name: NoticePeriodConfigService
 * Purpose: Performs tasks related to the NoticePeriodConfig model.
 * Description: NoticePeriodConfig Service class is called by the NoticePeriodConfigController where the requests related
 * to NoticePeriodConfig Model (basic operations and others). Table that is being modified is noticePeriodConfig.
 * Module Creator: Chalaka
 */
class NoticePeriodConfigService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $noticePeriodConfigModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->noticePeriodConfigModel = $this->getModel('noticePeriodConfig', true);
    }


    /**
     * Following function creates a NoticePeriodConfig.
     *
     * @param $NoticePeriodConfig array containing the NoticePeriodConfig data
     * @return int | String | array
     *
     * Usage:
     * $NoticePeriodConfig => ["name": "Sri Lankan"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "noticePeriodConfig created Successuflly",
     * $data => {"name": "Sri Lankan"}//$data has a similar set of values as the input
     *  */

    public function createNoticePeriodConfig($noticePeriodConfig)
    {
        try {

            $validationResponse = ModelValidator::validate($this->noticePeriodConfigModel, $noticePeriodConfig, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('noticePeriodConfigMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            // check wheather already having a record agains jobCategoryId and employmentStatusId
            $isNotExists = $this->store->getFacade()::table('noticePeriodConfig')
                ->where('jobCategoryId', $noticePeriodConfig['jobCategoryId'])
                ->where('employmentStatusId', $noticePeriodConfig['employmentStatusId'])
                ->where('isDelete', false)
                ->get()
                ->isEmpty();

            if (!$isNotExists) {
                return $this->error(400, Lang::get('noticePeriodConfigMessages.basic.ERR_COFIG_EXISTS'), null);
            }

            $newNoticePeriodConfig = $this->store->insert($this->noticePeriodConfigModel, $noticePeriodConfig, true);

            return $this->success(201, Lang::get('noticePeriodConfigMessages.basic.SUCC_CREATE'), $newNoticePeriodConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all noticePeriodConfigs.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "noticePeriodConfig created Successuflly",
     *      $data => {{"id": 1, name": "Sri Lankan"}, {"id": 1, name": "Sri Lankan"}}
     * ]
     */
    public function getAllNationalities($permittedFields, $options)
    {
        try {
            $searchFields = '';
            $filteredNoticePeriodConFigData=[];
            
            if($options['keyword']) {
                $searchFields = $options['keyword'];
                unset($options['keyword']);
            }
           
            $filteredNoticePeriod = $this->store->getAll(
                $this->noticePeriodConfigModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
           
            if($searchFields != '') {
                $jobCategory = $this->store->getFacade()::table('jobCategory')->where('name', 'like', '%' . $searchFields . '%')->first();
                
                if(!empty($jobCategory)) {
                    $filteredNoticePeriodConFigData = $filteredNoticePeriod['data']->filter(function($item) use ($jobCategory){
                       return $item->jobCategoryId == $jobCategory->id;
                    })->values();
                }
                
                $filteredNoticePeriod['data'] = $filteredNoticePeriodConFigData;
            }

           
            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_ALL_RETRIVE'),  $filteredNoticePeriod);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single NoticePeriodConfig for a provided id.
     *
     * @param $id noticePeriodConfig id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Sri Lankan"}
     * ]
     */
    public function getNoticePeriodConfig($id)
    {
        try {
            $noticePeriodConfig = $this->store->getFacade()::table('noticePeriodConfig')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($noticePeriodConfig)) {
                return $this->error(404, Lang::get('noticePeriodConfigMessages.basic.ERR_NONEXISTENT_NATIONALITY'), $noticePeriodConfig);
            }

            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_SINGLE_RETRIVE'), $noticePeriodConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single noticePeriodConfig for a provided id.
     *
     * @param $id noticePeriodConfig id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "noticePeriodConfig created Successuflly",
     *      $data => {"id": 1, name": "Sri Lankan"}
     * ]
     */
    public function getNoticePeriodConfigByKeyword($keyword)
    {
        try {
            $noticePeriodConfig = $this->store->getFacade()::table('noticePeriodConfig')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_ALL_RETRIVE'), $noticePeriodConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a noticePeriodConfig.
     *
     * @param $id noticePeriodConfig id
     * @param $NoticePeriodConfig array containing NoticePeriodConfig data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticePeriodConfig updated successfully.",
     *      $data => {"id": 1, name": "Sri Lankan"} // has a similar set of data as entered to updating NoticePeriodConfig.
     *
     */
    public function updateNoticePeriodConfig($id, $noticePeriodConfig)
    {
        try {
            $validationResponse = ModelValidator::validate($this->noticePeriodConfigModel, $noticePeriodConfig, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('noticePeriodConfigMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbNoticePeriodConfig = $this->store->getFacade()::table('noticePeriodConfig')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbNoticePeriodConfig)) {
                return $this->error(404, Lang::get('noticePeriodConfigMessages.basic.ERR_NONEXISTENT'), $noticePeriodConfig);
            }

            // check wheather already having a record agains jobCategoryId and employmentStatusId
            $isNotExists = $this->store->getFacade()::table('noticePeriodConfig')
                ->where('jobCategoryId', $noticePeriodConfig['jobCategoryId'])
                ->where('employmentStatusId', $noticePeriodConfig['employmentStatusId'])
                ->where('id', '!=', $id)
                ->where('isDelete', false)
                ->get()
                ->isEmpty();

            if (!$isNotExists) {
                return $this->error(400, Lang::get('noticePeriodConfigMessages.basic.ERR_COFIG_EXISTS'), null);
            }

            $noticePeriodConfig['isDelete'] = $dbNoticePeriodConfig->isDelete;
            $result = $this->store->updateById($this->noticePeriodConfigModel, $id, $noticePeriodConfig);

            if (!$result) {
                return $this->error(502, Lang::get('noticePeriodConfigMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_UPDATE'), $noticePeriodConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id noticePeriodConfig id
     * @param $NoticePeriodConfig array containing NoticePeriodConfig data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticePeriodConfig deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteNoticePeriodConfig($id)
    {
        try {
            $dbNoticePeriodConfig = $this->store->getById($this->noticePeriodConfigModel, $id);
            if (is_null($dbNoticePeriodConfig)) {
                return $this->error(404, Lang::get('noticePeriodConfigMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }
            $recordExist = Util::checkRecordsExist($this->noticePeriodConfigModel, $id);
            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('noticePeriodConfigMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('noticePeriodConfig')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a noticePeriodConfig.
     *
     * @param $id noticePeriodConfig id
     * @param $NoticePeriodConfig array containing NoticePeriodConfig data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticePeriodConfig deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteNoticePeriodConfig($id)
    {
        try {
            $dbNoticePeriodConfig = $this->store->getById($this->noticePeriodConfigModel, $id);
            if (is_null($dbNoticePeriodConfig)) {
                return $this->error(404, Lang::get('noticePeriodConfigMessages.basic.ERR_NONEXISTENT_NATIONALITY'), null);
            }

            $this->store->deleteById($this->noticePeriodConfigModel, $id);

            return $this->success(200, Lang::get('noticePeriodConfigMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticePeriodConfigMessages.basic.ERR_DELETE'), null);
        }
    }
}
