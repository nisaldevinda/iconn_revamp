<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait FormTemplateInstanceHelper
{
    public function createFormTemplateInstance($templateId, $employeeId, $authorizedEmployeeId, $createdBy)
    {
        try {
            $template = DB::table('formTemplate')->where('id', $templateId)->first(['formContent']);

            $data = [
                'employeeId' => $employeeId,
                'formtemplateId' => $templateId,
                'hash' => Str::uuid()->toString(),
                'authorizedEmployeeId' => $authorizedEmployeeId,
                'blueprint' => $template->formContent,
                'createdBy' => $createdBy,
                'updatedBy' => $createdBy,
            ];

            return DB::table('formTemplateInstance')->insertGetId($data);

        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception('An error occurred while creating the form template instance');
        }
    }

    public function updateFormTemplateInstance($instanceId, $updatedBy, $response = null, $status = null)
    {
        try {
            $data = [];
            if (!is_null($response)) {
                $data['response'] = $response;
            }
            if (!is_null($status)) {
                $data['status'] = $status;
            }
            $data['updatedBy'] = $updatedBy;

            DB::table('formTemplateInstance')->where('id', $instanceId)->update($data);
            return true;

        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception('An error occurred while updating the form template instance');
        }
    }

    public function hasPermittedToAccessInstance($hash, $requestedEmployeeId)
    {
        try {
            $count = DB::table('formTemplateInstance')->where('hash', $hash)->where('authorizedEmployeeId', $requestedEmployeeId)->count();
            return $count > 0;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception('An error occurred while checking the form template permission');
        }
    }
}
