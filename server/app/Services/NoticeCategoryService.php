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

/**
 * Name: NoticeCategoryService
 * Purpose: Performs tasks related to the NoticeCategory model.
 * Description: NoticeCategory Service class is called by the NoticeCategoryController where the requests related
 * to NoticeCategory Model (basic operations and others). Table that is being modified is noticeCategory.
 * Module Creator: Hashan
 */
class NoticeCategoryService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $noticeCategoryModel;
    

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->noticeCategoryModel = $this->getModel('noticeCategory', true);
    }
    

    /**
     * Following function creates a NoticeCategory.
     *
     * @param $NoticeCategory array containing the NoticeCategory data
     * @return int | String | array
     *
     * Usage:
     * $NoticeCategory => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "noticeCategory created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */

    public function createNoticeCategory($noticeCategory)
    {
        try {
            $validationResponse = ModelValidator::validate($this->noticeCategoryModel, $noticeCategory, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('noticeCategoryMessages.basic.ERR_CREATE'), $validationResponse);
            }
             
            $newNoticeCategory = $this->store->insert($this->noticeCategoryModel, $noticeCategory, true);

            return $this->success(201, Lang::get('noticeCategoryMessages.basic.SUCC_CREATE'), $newNoticeCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all noticeCategorys.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "noticeCategory created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getAllNoticeCategorys($permittedFields, $options)
    {
        try {
            $filteredNoticeCategorys = $this->store->getAll(
                $this->noticeCategoryModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_ALL_RETRIVE'), $filteredNoticeCategorys);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single NoticeCategory for a provided id.
     *
     * @param $id noticeCategory id
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
    public function getNoticeCategory($id)
    {
        try {
            $noticeCategory = $this->store->getFacade()::table('noticeCategory')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($noticeCategory)) {
                return $this->error(404, Lang::get('noticeCategoryMessages.basic.ERR_NONEXISTENT_GENDER'), $noticeCategory);
            }

            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_SINGLE_RETRIVE'), $noticeCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single noticeCategory for a provided id.
     *
     * @param $id noticeCategory id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "noticeCategory created Successuflly",
     *      $data => {"id": 1, name": "Male"}
     * ]
     */
    public function getNoticeCategoryByKeyword($keyword)
    {
        try {
            $noticeCategory = $this->store->getFacade()::table('noticeCategory')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_ALL_RETRIVE'), $noticeCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a noticeCategory.
     *
     * @param $id noticeCategory id
     * @param $NoticeCategory array containing NoticeCategory data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticeCategory updated successfully.",
     *      $data => {"id": 1, name": "Male"} // has a similar set of data as entered to updating NoticeCategory.
     *
     */
    public function updateNoticeCategory($id, $noticeCategory)
    {
        try {
            $validationResponse = ModelValidator::validate($this->noticeCategoryModel, $noticeCategory, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('noticeCategoryMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbNoticeCategory = $this->store->getFacade()::table('noticeCategory')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbNoticeCategory)) {
                return $this->error(404, Lang::get('noticeCategoryMessages.basic.ERR_NONEXISTENT_GENDER'), $noticeCategory);
            }

            if (empty($noticeCategory['name'])) {
                return $this->error(400, Lang::get('noticeCategoryMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $noticeCategory['isDelete'] = $dbNoticeCategory->isDelete;
            $result = $this->store->updateById($this->noticeCategoryModel, $id, $noticeCategory);

            if (!$result) {
                return $this->error(502, Lang::get('noticeCategoryMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_UPDATE'), $noticeCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id noticeCategory id
     * @param $NoticeCategory array containing NoticeCategory data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticeCategory deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteNoticeCategory($id)
    {
        try {
            $dbNoticeCategory = $this->store->getById($this->noticeCategoryModel, $id);
            if (is_null($dbNoticeCategory)) {
                return $this->error(404, Lang::get('noticeCategoryMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }

            $recordExist = Util::checkRecordsExist($this->noticeCategoryModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('noticeCategoryMessages.basic.ERR_NOTALLOWED'), null);
            } 
            
            $this->store->getFacade()::table('noticeCategory')->where('id', $id)->update(['isDelete' => true]);
            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a noticeCategory.
     *
     * @param $id noticeCategory id
     * @param $NoticeCategory array containing NoticeCategory data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "noticeCategory deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteNoticeCategory($id)
    {
        try {
            $dbNoticeCategory = $this->store->getById($this->noticeCategoryModel, $id);
            if (is_null($dbNoticeCategory)) {
                return $this->error(404, Lang::get('noticeCategoryMessages.basic.ERR_NONEXISTENT_GENDER'), null);
            }
            
            $this->store->deleteById($this->noticeCategoryModel, $id);

            return $this->success(200, Lang::get('noticeCategoryMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('noticeCategoryMessages.basic.ERR_DELETE'), null);
        }
    }
}
