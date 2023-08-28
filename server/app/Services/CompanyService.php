<?php

namespace App\Services;

use App\Library\FileStore;
use Log;
use Exception;
use App\Library\ModelValidator;
use App\Library\Store;
use Illuminate\Support\Facades\Lang;
use App\Traits\JsonModelReader;
use App\Traits\ConfigHelper;
use Illuminate\Support\Facades\Log as FacadesLog;

/**
 * Name: CompanyService
 * Purpose: Performs tasks related to the User Role model.
 * Description: User Role Service class is called by the CompanyController where the requests related
 * to User Role Model (CRUD operations and others).
 * Module Creator: Yohan
 */
class CompanyService extends BaseService
{
    use JsonModelReader;
    use ConfigHelper;

    private $store;
    private $companyModel;
    private $fileStorage;


    public function __construct(Store $store, FileStore $fileStorage)
    {
        $this->store = $store;
        $this->companyModel = $this->getModel('company', true);
        $this->fileStorage = $fileStorage;
    }

    /**
     * Following function retrives a single company for a provided company_id.
     *
     * @param $id user company id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Company retrieved Successfully!",
     *      $data => {"title": "LK HR", ...}
     * ]
     */
    public function getCompany()
    {
        try {
            $company = $this->store->getFacade()::table($this->companyModel->getName())->first();
            if (empty($company)) {
                return $this->error(404, Lang::get('companyMessages.basic.ERR_NOT_EXIST'), null);
            }

            $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');
            $company->levels = $orgHierarchyConfig;

            if (!is_null($company->iconFileObjectId)) {
                $company->iconImage = $this->fileStorage->getBase64EncodedObject($company->iconFileObjectId);
            }
            if (!is_null($company->coverFileObjectId)) {
                $company->coverImage = $this->fileStorage->getBase64EncodedObject($company->coverFileObjectId);
            }

            return $this->success(200, Lang::get('companyMessages.basic.SUCC_GET'), $company);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('companyMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function updates a company.
     *
     * @param $id user company id
     * @param $company array containing company data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Company updated Successfully",
     *      $data => {"title": "LK HR", ...} // has a similar set of data as entered to updating user.
     *
     */
    public function updateCompany($id, $company)
    {
        try {
            $validationResponse = ModelValidator::validate($this->companyModel, $company, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('companyMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingCompany = $this->store->getById($this->companyModel, $id);
            if (empty($existingCompany)) {
                return $this->error(404, Lang::get('companyMessages.basic.ERR_NOT_EXIST'), null);
            }

            $DB = $this->store->getFacade();

            $DB::beginTransaction();

            $result = $this->store->updateById($this->companyModel, $id, $company);

            if( isset($company['levels']) ) {
                $this->setConfigValue('organization_hierarchy', $company['levels']);
            }

            if (!$result) {
                return $this->error(500, Lang::get('companyMessages.basic.ERR_UPDATE'), $id);
            }

            $DB::commit();

            return $this->success(200, Lang::get('companyMessages.basic.SUCC_UPDATE'), $company);
        } catch (Exception $e) {
            $DB::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('companyMessages.basic.ERR_UPDATE'), $company['levels']);
        }
    }

    public function getImages($imageType = null)
    {
        try {
            Log::error($imageType);
            $company = $this->store->getFacade()::table('company')->first(['id', 'iconFileObjectId', 'coverFileObjectId']);

            if (empty($company)) {
                return $this->error(404, Lang::get('companyMessages.basic.ERR_NOT_EXIST'), null);
            }

            $iconImage = null;
            $coverImage = null;

            if (is_null($imageType)) {
                $iconImage = is_null($company->iconFileObjectId) ? null : $this->fileStorage->getBase64EncodedObject($company->iconFileObjectId);
                $coverImage = is_null($company->coverFileObjectId) ? null : $this->fileStorage->getBase64EncodedObject($company->coverFileObjectId);
            } else if ($imageType == 'icon') {
                $iconImage = is_null($company->iconFileObjectId) ? null : $this->fileStorage->getBase64EncodedObject($company->iconFileObjectId);
            } else {
                $coverImage = is_null($company->coverFileObjectId) ? null : $this->fileStorage->getBase64EncodedObject($company->coverFileObjectId);
            }

            return $this->success(200, Lang::get('companyMessages.basic.SUCC_GET'), ['icon' => $iconImage, 'cover' => $coverImage]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('companyMessages.basic.ERR_GET'), null);
        }
    }

    public function storeImages($imageType, $data)
    {
        try {

            if (!in_array($imageType, ['icon', 'cover'])) {
                return $this->error(500, Lang::get('companyMessages.basic.ERR_UPDATE'), null);
            }

            $file = $this->fileStorage->putBase64EncodedObject(
                $data['fileName'],
                $data['fileSize'],
                $data["data"]
            );

            $updatedArr = ($imageType === 'icon') ? ['iconFileObjectId' => $file->id] : ['coverFileObjectId' => $file->id];
            $this->store->getFacade()::table('company')->update($updatedArr);
            return $this->success(200, Lang::get('companyMessages.basic.SUCC_UPDATE'), ['fileName' => $data['fileName']]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('companyMessages.basic.ERR_UPDATE'), null);
        }
    }


}
