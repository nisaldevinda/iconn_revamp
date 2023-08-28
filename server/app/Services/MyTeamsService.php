<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Session;
use App\Library\Store;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;


/**
 * Name: MyTeamsService
 * Purpose: Performs tasks related to the MyTeams model.
 * Description: MyTeams Service class is called by the MyTeamsController where the requests related
 * to MyTeams Model (basic operations and others). Table that is being modified is nationality.
 * Module Creator: Yohan
 */
class MyTeamsService extends BaseService
{
    private $store;
    private $employeeModel;
    private $session;

    use JsonModelReader;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->employeeModel = $this->getModel('employee', true);
        $this->session = $session;
    }

    /**
     * Following function retrives all my teams.
     *
     * @return int 
     * | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "all Teams retrieved Successfully",
     *      $data => {{"id": 1, "firstname": "Jhon","lastname":"Doe"}}
     * ]
     */
    public function getMyTeams($options, $permittedFields)
    {
        try {

            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
            $customWhereClauses = ['whereIn' => ['id' => $permittedEmployeeIds]];

            $myTeamRecords = $this->store->getAll(
                $this->employeeModel,
                $permittedFields,
                $options,
                ['gender', 'currentJobs'],
                $customWhereClauses
            );

            return $this->success(200, Lang::get('myTeamsMessages.basic.SUCC_ALL_RETRIVE'), $myTeamRecords);
        } catch (Exception $e) {

            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('myTeamsMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
}
