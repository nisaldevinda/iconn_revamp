<?php

namespace App\Services;

use App\Library\FileStore;
use Log;
use Exception;
// use App\Library\Facades\Store;
use App\Library\Store;
use App\Library\JsonModel;
use App\Library\Interfaces\ModelReaderInterface;
use App\Library\Session;
use App\Traits\JsonModelReader;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;

class NoticeService extends BaseService
{
    use JsonModelReader;
    private $store;
    private $fileStore;
    private $session;
    private $noticeModel;
    private $employeeJobModel;
    private $userRoleModel;

    public function __construct(Store $store, Session $session, FileStore $fileStore)
    {
        $this->store = $store;
        $this->session = $session;
        $this->fileStore = $fileStore;
        $this->noticeModel =  $this->getModel('notice', true);
        $this->employeeJobModel =  $this->getModel('employeeJob', true);
        $this->userRoleModel =  $this->getModel('userRole', true);
    }

    /**
     * Following function retrieves a single notice for a provided notice id.
     *
     * @param $id notice id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Notice retrieved Successfully!",
     *      $data => {"topic": "HR meeting", ...}
     * ]
     */
    public function getNotice($id)
    {
        try {
            $notice = $this->store->getById(
                $this->noticeModel,
                $id
            );

            if (!empty($notice->attachmentId)) {
                $file = $this->fileStore->getBase64EncodedObject($notice->attachmentId);
                $notice->attachment = $file;
            }

            if (empty($notice)) {
                return $this->error(404, Lang::get('noticeMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_GET'), $notice);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('noticeMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function retrieves all notices.
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "message: "All notices retrieved Successfully.",
     *      $data => data: [
     *  0: {id: 1, topic: "Meeting for new joiners", status: "Draft", description: "Today at 10am", ...}
     *  1: {id: 2, topic: "Meeting for Managers", status: "Archived", description: "Today at 12pm in meeting room", ...}
     *  2: {id: 3, topic: "Meeting for Security", status: "Unpublished", description: "Today at 5pm in security area ", ...}
     *  3: {id: 4, topic: "Meeting for Directors", status: "Published", description: "Directors need to participate at 2pm ...", ...}
     * ]
     */
    public function getAllNotices($permittedFields, $options)
    {
        try {
            if ($this->session->isGlobalAdmin() || $this->session->isSystemAdmin()) {
                $notices = $this->store->getFacade()::table($this->noticeModel->getName());
            } else {
                $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
                $employee = $this->session->getEmployee();
                $employeeId = $employee->id;
                $user = $this->session->getUser();

                $locationIds = [];
                if (!empty($user->adminRoleId)) {
                    $adminRole = $this->store->getById($this->userRoleModel, $user->adminRoleId);

                    $customCriteria = json_decode($adminRole->customCriteria, true);

                    if (!empty($customCriteria) && !empty($customCriteria['location'])) {
                        $locationIds = $customCriteria['location'];
                    }
                }

                $notices = $this->store->getFacade()::table($this->noticeModel->getName())
                    ->where(function ($query) use ($employeeId, $locationIds, $permittedEmployeeIds) {
                        $query->where('audienceMethod', 'ALL');
                        $query->orwhere(function ($innerQuery) use ($employeeId) {
                            $innerQuery->whereIn('audienceMethod', ['ASSIGNED_TO_ME', 'REPORT_TO']);
                            $innerQuery->whereJsonContains('audienceData->reportTo', $employeeId);
                        });
                        $query->orwhere(function ($innerQuery) use ($locationIds) {
                            $innerQuery->where('audienceMethod', 'QUERY');
                            $innerQuery->whereJsonContains('audienceData->locationId', $locationIds);
                        });
                        $query->orwhere(function ($innerQuery) use ($permittedEmployeeIds) {
                            $innerQuery->where('audienceMethod', 'CUSTOM');
                            $innerQuery->whereJsonContains('audienceData->employeeIds', $permittedEmployeeIds);
                        });
                    });
            }

            $filterData = json_decode($options["filterBy"], true);

            if (!empty($filterData) && array_key_exists("topic", $filterData))
                $notices->where('topic', 'LIKE', '%' . $filterData['topic'] . '%');

            if (!empty($filterData) && array_key_exists("status", $filterData))
                $notices->where('status', $filterData['status']);

            if (!empty($filterData) && array_key_exists("createdBy" , $filterData))
                $notices->where('createdBy', $filterData['createdBy']);
            
            $notices = $notices->orderBy('updatedAt', 'desc')->get();
            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_GETALL'), $notices);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('noticeMessages.basic.ERR_GETALL'), null);
        }
    }

    /**
     * Following function creates a notice.
     * id: 11
     * topic: "General Meeting"
     * description: "today at 5pm for all members"
     * status: "Published"
     * createdAt: "2021-08-23 03:58:42"
     * createdBy: 1
     * updatedAt: "2021-08-23 03:58:42"
     * updatedBy: 1
     * message: "Notice created Successfully."
     */
    public function createNotice($notice)
    {
        try {
            if (!empty($notice['audienceData'])) {
                $notice['audienceData'] = json_encode($notice['audienceData']);
            }

            if (!empty($notice['attachment'])) {
                $attachment = $notice['attachment'];
                $file = $this->fileStore->putBase64EncodedObject(
                    $attachment['fileName'],
                    $attachment['fileSize'],
                    $attachment['data']
                );

                unset($notice['attachment']);
                $notice['attachmentId'] = $file->id;
            }

            $newNotice = $this->store->insert($this->noticeModel, $notice, true);

            if (!$newNotice) {
                return $this->error(502, Lang::get('noticeMessages.basic.ERR_CREATE'), $notice);
            }

            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_CREATE'), $newNotice);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function updates a notice.
     *
     * @param $id > notice id
     * @param $notice array containing notice data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Notice updated Successfully",
     *      $data => {"topic": "Daily Standup Meeting", ...} // has a similar set of data as entered to create notice.
     */
    public function updateNotice($id, $notice)
    {
        try {
            $existingNotice = $this->store->getById($this->noticeModel, $id);

            if (empty($existingNotice)) {
                return $this->error(404, Lang::get('noticeMessages.basic.ERR_NOT_EXIST'), null);
            }

            if (!empty($notice['audienceData'])) {
                $notice['audienceData'] = json_encode($notice['audienceData']);
            }

            if (!empty($notice['attachment'])) {
                $attachment = $notice['attachment'];
                $file = $this->fileStore->putBase64EncodedObject(
                    $attachment['fileName'],
                    $attachment['fileSize'],
                    $attachment['data']
                );
                
                unset($notice['attachment']);
                $notice['attachmentId'] = $file->id;
            }

            $result = $this->store->updateById($this->noticeModel, $id, $notice, true);

            if (!$result) {
                return $this->error(502, Lang::get('noticeMessages.basic.ERR_UPDATE'), $result);
            }

            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_UPDATE'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('noticeMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a notice.
     *
     * @param $id notice id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Notice deleted Successfully!",
     *      $data => {"topic": "Daily Standup Meeting", ...}
     */
    public function deleteNotice($id)
    {
        try {
            $existingNotice = $this->store->getById($this->noticeModel, $id);

            if (empty($existingNotice)) {
                return $this->error(404, Lang::get('noticeMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->deleteById($this->noticeModel, $id);

            if (!$result) {
                return $this->error(502, Lang::get('noticeMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_DELETE'), $existingNotice);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('noticeMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function retrieves specific Published notices.
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "message: "All notices retrieved Successfully.",
     *      $data => data: [
     *  0: {id: 1, topic: "Meeting for new joiners", description: "Today at 10am"}
     *  1: {id: 2, topic: "Meeting for Managers", description: "Today at 12pm in meeting room"}
     *  2: {id: 3, topic: "Meeting for Security", description: "Today at 5pm in security area "}
     *  3: {id: 4, topic: "Meeting for Directors", description: "Directors need to participate at 2pm ..."}
     * ]
     */
    public function getRecentlyPublishedNotices($permittedFields, $options)
    {
        try {
            $employee = $this->session->getEmployee();
            $employeeId = $employee->id;
            $jobsId = $employee->currentJobsId;
            $job = $this->store->getById($this->employeeJobModel, $jobsId);
            $locationId = !empty($job->locationId) ? $job->locationId : null;
            $reportTo = !empty($job->reportsToEmployeeId) ? $job->reportsToEmployeeId : null;

            $notices = $this->store->getFacade()::table($this->noticeModel->getName())
                ->where('status', '=', 'Published')
                ->where(function ($query) use ($reportTo, $locationId, $employeeId) {
                    $query->where('audienceMethod', 'ALL');
                    $query->orwhere(function ($innerQuery) use ($reportTo) {
                        $innerQuery->whereIn('audienceMethod', ['ASSIGNED_TO_ME', 'REPORT_TO']);
                        $innerQuery->whereJsonContains('audienceData->reportTo', $reportTo);
                    });
                    $query->orwhere(function ($innerQuery) use ($locationId) {
                        $innerQuery->where('audienceMethod', 'QUERY');
                        $innerQuery->whereJsonContains('audienceData->locationId', $locationId);
                    });
                    $query->orwhere(function ($innerQuery) use ($employeeId) {
                        $innerQuery->where('audienceMethod', 'CUSTOM');
                        $innerQuery->whereJsonContains('audienceData->employeeIds', $employeeId);
                    });
                })
                ->orderBy('updatedAt', 'desc')
                ->get();

            return $this->success(200, Lang::get('noticeMessages.basic.SUCC_GETALL'), $notices);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('noticeMessages.basic.ERR_GETALL'), null);
        }
    }
}
