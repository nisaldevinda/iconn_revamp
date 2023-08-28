<?php

namespace App\Library;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Exception;
use Log;

class Util
{
    /**
     * Recieved query builder is modified by adding where clauses according to the filters
     * from request query params
     *
     * @param  Illuminate\Support\Facades\DB $queryBuilder
     * @param  array $permittedFields
     * @param  string $filters
     *
     * Usage 01:
     * $queryBuilder => The query builder object to be changed
     * $permittedFields => ['id', 'email', ...]
     * $filters => {
     *  "field_name": "comparison_operator:comparison_value",
     *  "name": "eq:Test",
     *  "email": "like:test",
     *  "languages": "in:sinhala,english",
     *  "salary": "gt:10000",
     *  "salary": "lte:100000",
     *  ...
     * }
     *
     * Usage 02:
     * $queryBuilder => The query builder object to be changed
     * $permittedFields => ['id', 'email', ...]
     * $filters => {
     *  "languages": ["sinhala", "english"],
     *  ...
     * }
     *
     * Sample output:
     * Illuminate\Support\Facades\DB
     *
     */
    public static function addWhereClausesToQueryBuilder($queryBuilder, $permittedFields, $filters) {
        try {
            $filters = json_decode($filters, true);

            foreach ($filters as $fieldName => $comparison) {
                if (!in_array("*", $permittedFields) && !in_array($fieldName, $permittedFields))
                    continue;

                if (is_array($comparison)) {
                    $queryBuilder = $queryBuilder->whereIn($fieldName, $comparison);
                    continue;
                }

                list($operator, $value) = explode(':', $comparison);
                switch ($operator) {
                    case 'like':
                        $queryBuilder = $queryBuilder->whereRaw('LOWER(`' . $fieldName . '`) LIKE ? ',[trim(strtolower($value)).'%']);
                        break;
                    case 'in':
                        $values = explode(',', $value);
                        $queryBuilder = $queryBuilder->whereIn($fieldName, $values);
                        break;
                    case 'null':
                        $queryBuilder = $queryBuilder->whereNull($fieldName);
                        break;
                    case 'not_null':
                        $queryBuilder = $queryBuilder->whereNotNull($fieldName);
                        break;
                    case 'gt';
                        $queryBuilder = $queryBuilder->where($fieldName, '>', $value);
                        break;
                    case 'gte';
                        $queryBuilder = $queryBuilder->where($fieldName, '>=', $value);
                        break;
                    case 'lt';
                        $queryBuilder = $queryBuilder->where($fieldName, '<', $value);
                        break;
                    case 'lte';
                        $queryBuilder = $queryBuilder->where($fieldName, '<=', $value);
                        break;
                    case 'eq';
                    default:
                        $queryBuilder = $queryBuilder->where($fieldName, $value);
                        break;
                }
            }

            return $queryBuilder;
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return $queryBuilder;
        }
    }

    /**
     * This function takes a user data array and set all the data that are required to allow a user to access the system.
     *
     *
     * @param $userData Array containing user data
     * @return array
     *
     * Usage:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     *
     * Sample output:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     */
    public static function clearUserLocks($userData)
    {
        $userData["blocked"] = false;
        $userData["loginsCount"] = 0;
        $userData["failedLoginsCount"] = 0;
        $userData['verificationToken'] = "";
        $userData['isTokenActive'] = false;
        $userData['verificationTokenTime'] = false;

        return $userData;
    }

    /**
     * This function takes a user data array and set all the data that are required to avoid a user from accessing the system.
     *
     *
     * @param $userData Array containing user data
     * @return array
     *
     * Usage:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     *
     * Sample output:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     */
    public static function setUserLocks($userData)
    {
        $userData["blocked"] = true;
        $userData['isTokenActive'] = true;

        return $userData;
    }

     /**
     * This function takes a user data array and set data that are required to reset the user password by mail .
     *
     *
     * @param $userData Array containing user data
     * @return array
     *
     * Usage:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     *
     * Sample output:
     * $userData => {"username": "John", "email": "John@gmail.com", "password": "John@123"}
     */
    public static function setUserTokenActive($userData)
    {

        $userData['isTokenActive'] = true;

        return $userData;
    }

    public static function passwordGenerate($length)
    {
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz!@#$%&*-_';
        return substr(str_shuffle($data), 0, $length);
    }

    public static function checkRecordsExist($model,$id) {
        $belongsToRelationAttributes = $model->getRelations(RelationshipType::BELONGS_TO);

        $records = [];
        foreach ($belongsToRelationAttributes as $relation) {
            $foreignKey = $model->getName() . 'Id';
            $query = DB::table($relation)
                    ->where($foreignKey,$id)
                    ->first();
            array_push($records, $query);
        }

        $records = array_filter($records);
       return $records;
    }

    public static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function toCamelCase($string, $capitalizeFirstChar = false) {
        $str = str_replace(str_split(' -'), '', ucwords($string, ' -'));

        if (!$capitalizeFirstChar) {
            $str = lcfirst($str);
        }

        return $str;
    }
}
