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
 * Name: RelationshipService
 * Purpose: Performs tasks related to the Relationship model.
 * Description: Relationship Service class is called by the RelationshipController where the requests related
 * to Relationship Model (basic operations and others). Table that is being modified is relationship.
 * Module Creator: Chalaka
 */
class RelationshipService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $relationshipModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->relationshipModel = $this->getModel('relationship', true);
    }
    

    /**
     * Following function creates a Relationship.
     *
     * @param $Relationship array containing the Relationship data
     * @return int | String | array
     *
     * Usage:
     * $Relationship => ["name": "Relative"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "relationship created Successuflly",
     * $data => {"name": "Relative"}//$data has a similar set of values as the input
     *  */

    public function createRelationship($relationship)
    {
        try {
            $validationResponse = ModelValidator::validate($this->relationshipModel, $relationship, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('relationshipMessages.basic.ERR_CREATE'), $validationResponse);
            }
          
            $newRelationship = $this->store->insert($this->relationshipModel, $relationship, true);

            return $this->success(201, Lang::get('relationshipMessages.basic.SUCC_CREATE'), $newRelationship);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all relationships.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "relationship created Successuflly",
     *      $data => {{"id": 1, name": "Relative"}, {"id": 1, name": "Relative"}}
     * ]
     */
    public function getAllRelationships($permittedFields, $options)
    {
        try {
            $filteredRelationships = $this->store->getAll(
                $this->relationshipModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_ALL_RETRIVE'), $filteredRelationships);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Relationship for a provided id.
     *
     * @param $id relationship id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "Relative"}
     * ]
     */
    public function getRelationship($id)
    {
        try {
            $relationship = $this->store->getFacade()::table('relationship')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($relationship)) {
                return $this->error(404, Lang::get('relationshipMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), $relationship);
            }

            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_SINGLE_RETRIVE'), $relationship);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single relationship for a provided id.
     *
     * @param $id relationship id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "relationship created Successuflly",
     *      $data => {"id": 1, name": "Relative"}
     * ]
     */
    public function getRelationshipByKeyword($keyword)
    {
        try {
            $relationship = $this->store->getFacade()::table('relationship')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_ALL_RETRIVE'), $relationship);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    


    /**
     * Following function updates a relationship.
     *
     * @param $id relationship id
     * @param $Relationship array containing Relationship data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "relationship updated successfully.",
     *      $data => {"id": 1, name": "Relative"} // has a similar set of data as entered to updating Relationship.
     *
     */
    public function updateRelationship($id, $relationship)
    {
        try {
            $validationResponse = ModelValidator::validate($this->relationshipModel, $relationship, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('relationshipMessages.basic.ERR_UPDATE'), $validationResponse);
            }
            
            $dbRelationship = $this->store->getFacade()::table('relationship')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbRelationship)) {
                return $this->error(404, Lang::get('relationshipMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), $relationship);
            }

            if (empty($relationship['name'])) {
                return $this->error(400, Lang::get('relationshipMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            
            $relationship['isDelete'] = $dbRelationship->isDelete;
            $result = $this->store->updateById($this->relationshipModel, $id, $relationship);

            if (!$result) {
                return $this->error(502, Lang::get('relationshipMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_UPDATE'), $relationship);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id relationship id
     * @param $Relationship array containing Relationship data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "relationship deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteRelationship($id)
    {
        try {
            $dbRelationship = $this->store->getById($this->relationshipModel, $id);
            if (is_null($dbRelationship)) {
                return $this->error(404, Lang::get('relationshipMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }

            $recordExist = Util::checkRecordsExist($this->relationshipModel,$id);
            if (!empty($recordExist) ) {
                return $this->error(502, Lang::get('relationshipMessages.basic.ERR_NOTALLOWED'),  null);
            } 

            $this->store->getFacade()::table('relationship')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a relationship.
     *
     * @param $id relationship id
     * @param $Relationship array containing Relationship data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "relationship deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteRelationship($id)
    {
        try {
            $dbRelationship = $this->store->getById($this->relationshipModel, $id);
            if (is_null($dbRelationship)) {
                return $this->error(404, Lang::get('relationshipMessages.basic.ERR_NONEXISTENT_RELATIONSHIP'), null);
            }
            
            $this->store->deleteById($this->relationshipModel, $id);

            return $this->success(200, Lang::get('relationshipMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('relationshipMessages.basic.ERR_DELETE'), null);
        }
    }
}
