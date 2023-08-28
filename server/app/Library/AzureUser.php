<?php

namespace App\Library;

use Log;

class AzureUser
{
    private $fieldMap = [
        ['employeeFieldName' => 'employeeNumber', 'azureKey' => 'employeeId', 'azureFieldTitle' => 'Employee ID'],
        ['employeeFieldName' => 'firstName', 'azureKey' => 'givenName', 'azureFieldTitle' => 'First Name'],
        ['employeeFieldName' => 'lastName', 'azureKey' => 'surname', 'azureFieldTitle' => 'Last Name'],
        ['employeeFieldName' => 'fullName', 'azureKey' => 'displayName', 'azureFieldTitle' => 'Display Name'],
        ['employeeFieldName' => 'workEmail', 'azureKey' => 'userPrincipalName', 'azureFieldTitle' => 'User Principle Name'],
        ['employeeFieldName' => 'hireDate', 'azureKey' => 'employeeHireDate', 'azureFieldTitle' => 'Employee Hire Date'],
        ['employeeFieldName' => 'jobs.0.effectiveDate', 'azureKey' => 'employeeHireDate', 'azureFieldTitle' => 'Employee Hire Date'],
        ['employeeFieldName' => 'jobs.0.jobTitle.name', 'azureKey' => 'jobTitle', 'azureFieldTitle' => 'Job Title'],
        ['employeeFieldName' => 'jobs.0.department.parentDepartment.name', 'azureKey' => 'companyName', 'azureFieldTitle' => 'Company Name'],
        ['employeeFieldName' => 'jobs.0.department.name', 'azureKey' => 'department', 'azureFieldTitle' => 'Department'],
        ['employeeFieldName' => 'jobs.0.location.name', 'azureKey' => 'officeLocation', 'azureFieldTitle' => 'Office Location'],
        // ['employeeFieldName' => 'jobs.0.employmentStatus.name', 'azureKey' => 'officeLocation', 'azureFieldTitle' => 'Office Location'],
        ['employeeFieldName' => 'jobs.0.reportsToEmployee.workEmail', 'azureKey' => 'manager.userPrincipalName', 'azureFieldTitle' => 'Manager'],
        ['employeeFieldName' => 'workPhone', 'azureKey' => 'businessPhones', 'azureFieldTitle' => 'Business Phone'],
        ['employeeFieldName' => 'mobilePhone', 'azureKey' => 'mobilePhone', 'azureFieldTitle' => 'Mobile Phone'],
        ['employeeFieldName' => 'personalEmail', 'azureKey' => 'email', 'azureFieldTitle' => 'Email'],
        ['employeeFieldName' => 'isActive', 'azureKey' => 'accountEnabled', 'azureFieldTitle' => 'Account Enabled'],
        ['employeeFieldName' => 'createdAt', 'azureKey' => 'createdDateTime', 'azureFieldTitle' => 'Created Date'],
    ];

    public function getAzureUserFieldMap()
    {
        return $this->fieldMap;
    }

    public function toEmployee($azureUser)
    {
        try {
            $employee = [];

            foreach ($this->getAzureUserFieldMap() as $fieldMap) {
                $azureKey = explode('.', $fieldMap['azureKey']);
                $employeeFieldName = explode('.', $fieldMap['employeeFieldName']);

                $value = null;
                if (count($azureKey) == 1) {
                    $value = $azureUser[$azureKey[0]] ?? null;
                } else {
                    $value = $azureUser[$azureKey[0]][$azureKey[1]] ?? null;
                }

                if (count($employeeFieldName) == 1) {
                    $employee[$employeeFieldName[0]] = $value;
                } else {
                    switch (count($employeeFieldName)) {
                        case 2:
                            $employee[$employeeFieldName[0]][$employeeFieldName[1]] = $value;
                            break;
                        case 3:
                            $employee[$employeeFieldName[0]][$employeeFieldName[1]][$employeeFieldName[2]] = $value;
                            break;
                        case 4:
                            $employee[$employeeFieldName[0]][$employeeFieldName[1]][$employeeFieldName[2]][$employeeFieldName[3]] = $value;
                            break;
                        case 5:
                            $employee[$employeeFieldName[0]][$employeeFieldName[1]][$employeeFieldName[2]][$employeeFieldName[3]][$employeeFieldName[4]] = $value;
                            break;
                    }
                }
            }

            $employee['jobs'][0]['employeeJourneyType'] = 'JOINED';
            $employee['workPhone'] = is_array($employee['workPhone']) && count($employee['workPhone']) > 0
                ? $employee['workPhone'][0]
                : null;

            Log::info($azureUser, $employee);

            return $employee;
        } catch (Exception $e) {
            error_log(json_encode($e->getMessage()));
        }
    }

    public function parseCreateBody($employeeData, $password)
    {
        [$mailNickname] = explode('@', $employeeData['workEmail']);

        return [
            'accountEnabled' => true,
            'displayName' => $employeeData['fullName'],
            'userPrincipalName' => $employeeData['workEmail'],
            'mailNickname' => $mailNickname,
            'passwordProfile' => [
                'forceChangePasswordNextSignIn' => true,
                'password' => $password
            ]
        ];
    }

    public function parseUpdateBody($employeeData)
    {
        $azureUser = [];

        foreach ($this->getAzureUserFieldMap() as ['employeeFieldName' => $employeeFieldName, 'azureKey' => $azureKey]) {
            $value = $employeeData;

            $valuePath = explode('.', $employeeFieldName);
            foreach ($valuePath as $path) {
                if (isset($value[$path])) {
                    $value = $value[$path];
                } else {
                    $value = null;
                    break;
                }
            }

            if (!is_null($value)) {
                $azureUser[$azureKey] = $value;
            }
        }

        return $azureUser;
    }
}
