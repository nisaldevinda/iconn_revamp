<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Jobs\AzureUserExtractingJob;
use App\Jobs\ImportAzureUserJob;
use App\Library\ActiveDirectory;
use App\Library\AzureUser;
use App\Library\Store;
use App\Library\Session;
use App\Traits\ConfigHelper;
use App\Traits\JsonModelReader;

/**
 * Name: ImportAzureUsersService
 * Purpose: Performs tasks related to the ImportAzureUser model.
 * Description: ImportAzureUser Service class is called by the ImportAzureUsersController where the requests related
 * to ImportAzureUser Model (basic operations and others). Table that is being modified is importAzureUser.
 * Module Creator: Hashan
 */
class ImportAzureUsersService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;

    private $store;
    private $session;
    private $activeDirectory;
    private $azureUser;

    private $employeeImportJobModel;
    private $azureUserModel;

    public function __construct(Store $store, Session $session, ActiveDirectory $activeDirectory, AzureUser $azureUser)
    {
        $this->store = $store;
        $this->session = $session;
        $this->activeDirectory = $activeDirectory;
        $this->azureUser = $azureUser;

        $this->employeeImportJobModel = $this->getModel('employeeImportJob', true);
        $this->azureUserModel = $this->getModel('azureUser', true);
    }

    /**
     * Following function creates a ImportAzureUser.
     *
     * @param $ImportAzureUser array containing the ImportAzureUser data
     * @return int | String | array
     *
     * Usage:
     * $ImportAzureUser => ["name": "Male"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "importAzureUser created Successuflly",
     * $data => {"name": "Male"}//$data has a similar set of values as the input
     *  */
    public function setup($data)
    {
        try {
            $fieldMap = $this->azureUser->getAzureUserFieldMap();
            $employeeImportJobData = [
                'fieldMap' => json_encode($fieldMap),
                'progress' => 0,
                'status' => 'PENDING'
            ];

            $employeeImportJobData = $this->store->insert(
                $this->employeeImportJobModel,
                $employeeImportJobData,
                true
            );

            dispatch(new AzureUserExtractingJob([
                'tenantId' => $this->session->getTenantId(),
                'employeeImportJobId' => $employeeImportJobData['id']
            ]));

            return $this->success(201, Lang::get('azureUserImportMessages.basic.SUCC_SETUP_SYNC'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_SETUP_SYNC'), null);
        }
    }

    /**
     * Following function retrives all importAzureUsers.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "importAzureUser created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getStatus()
    {
        try {
            $recentAzureSyncJob = (array) $this->store->getFacade()::table('employeeImportJob')
                ->orderBy('createdAt', 'desc')
                ->first();

            $azureUsers = $this->store->getFacade()::table('stagingEmployee')
                ->orderBy('updatedAt', 'desc')
                ->get()
                ->toArray();

            $data = $recentAzureSyncJob;
            $data['azureUsers'] = $azureUsers;

            return $this->success(200, Lang::get('azureUserImportMessages.basic.SUCC_SYNCING_STATUS_RETRIVED'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_SYNCING_STATUS_RETRIVE'), null);
        }
    }

    public function getFieldMap()
    {
        try {
            $fieldMap = $this->azureUser->getAzureUserFieldMap();
            return $this->success(200, Lang::get('azureUserImportMessages.basic.SUCC_FIELD_MAP_RETRIVED'), $fieldMap);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_RETRIVING_FIELD_MAP'), null);
        }
    }

    public function getConfig()
    {
        try {
            $response = [];

            $configKeys = ['azure_tenant_id', 'azure_client_id', 'azure_client_secret', 'is_active_azure_user_provisioning', 'azure_domain_name', 'azure_default_password'];
            foreach ($configKeys as $configKey) {
                $response[$configKey] = $this->getConfigValue($configKey);
            }

            return $this->success(200, Lang::get('azureUserImportMessages.basic.SUCC_FIELD_MAP_RETRIVED'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_RETRIVING_FIELD_MAP'), null);
        }
    }

    public function storeAuthConfig($data)
    {
        try {
            $response = $this->activeDirectory->initClient(
                $data['azure_tenant_id'],
                $data['azure_client_id'],
                $data['azure_client_secret']
            );

            $configKeys = ['azure_tenant_id', 'azure_client_id', 'azure_client_secret'];
            foreach ($configKeys as $configKey) {
                $response[$configKey] = $this->setConfigValue($configKey, $data[$configKey]);
            }

            return $this->success(201, Lang::get('azureUserImportMessages.basic.SUCC_SETUP_SYNC'), $response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_SETUP_SYNC'), null);
        }
    }

    public function storeUserProvisioningConfig($data)
    {
        try {
            $response = [];
            $configKeys = $data['is_active_azure_user_provisioning']
                ? ['is_active_azure_user_provisioning', 'azure_domain_name', 'azure_default_password']
                : ['is_active_azure_user_provisioning'];

            foreach ($configKeys as $configKey) {
                $response[$configKey] = $this->setConfigValue($configKey, $data[$configKey]);
            }

            return $this->success(201, Lang::get('azureUserImportMessages.basic.SUCC_SETUP_SYNC'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('azureUserImportMessages.basic.ERR_SETUP_SYNC'), null);
        }
    }
}
