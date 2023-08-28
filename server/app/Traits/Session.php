<?php

namespace App\Traits;

use App\Library\Store;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

trait Session
{
    use JsonModelReader;

    public function getUserId()
    {
        return request()->attributes->get("user")->id ?? null;
    }

    public function getUser()
    {
        return request()->attributes->get("user");
    }

    public function getCompany()
    {
        $companyModel = $this->getModel('company', true);
        $company = DB::table($companyModel->getName())->first();
        return (array) $company;
    }

    public function getCompanyTimeZone()
    {
        $company = $this->getCompany();
        $companyTimeZone = $company["timeZone"];
        return $companyTimeZone ?? null;
    }

    public function getCompanyDate()
    {
        $companyTimeZone = $this->getCompanyTimeZone();
        $date = new DateTime("now", new DateTimeZone($companyTimeZone));
        return $date->format('Y-m-d');
    }
}
