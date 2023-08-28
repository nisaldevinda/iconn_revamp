<?php

namespace Database\Seeders;

use App\Services\UserRoleService;
use App\Traits\JsonModelReader;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    use JsonModelReader;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $model = $this->getModel('employee', true);

        $res = $model->getRequiredFields();

        dd($res);

        // $schema = json_decode(env('ATTEN_FILE_CONFIG'), true)['file1'];
        // // dd($schema);

        // $filename = 'flexi-attendance.txt';

        // $handle = fopen( $filename, "r");
        // if ($handle) {
        //     while (($line = fgets($handle)) !== false) {
        //         // dd($line);
        //         dd($this->getAttendanceRecord($schema, $line));
        //     }
        //     fclose($handle);
        // }



        // $rows = [];

        // $file = fopen('Export.txt', 'r');
        // while (($line = fgetcsv($file)) !== FALSE) {
        //     //$line is an array of the csv elements
        //     $data = preg_replace('!\s+!', ' ', $line);

        //     // dd($data);

        //     array_push($rows, $data[0]);

        // }
        // fclose($file);

        // $dataArr = [];

        // foreach ($rows as $row) {
        //     $rowData = explode(" ", $row);

        //     // dd($rowData);

        //     $employeeNumber = trim($rowData[0]);
        //     $date = date("d/m/Y", strtotime(trim($rowData[1])));
        //     $time = trim($rowData[2]);
        //     $fix = "FP";
        //     $device = "flexi03";

        //     $str = str_pad($employeeNumber, 25) . str_pad($employeeNumber, 25) . $date .' '. $time .' '. str_pad($fix, 21) . str_pad($device, 32);

        //     // dd($str);

        //     $dataArr[] = $str;
        //     // dd($str);
        //     // str_pad();
        // }

        // // foreach ($dataArr as $value) {
        // //     // file_put_contents('array.txt', var_export($value, TRUE));
        // //     $fp = fopen('file.txt', 'w');
        // //     fwrite($fp, print_r($value, TRUE));
        // //     fclose($fp);
        // // }
        // file_put_contents('flexi-attendance-001.txt', implode("\n", $dataArr));
    }

    public function getAttendanceRecord($schema, $line)
    {

        $record  = [
            'device_id' => '',
            'emp_code' => '',
            'rec_date' => '',
            'rec_time' => '',
            'rec_date_time' => '',
            'status' => '',
        ];

        if ($schema['seperator'] != '') {

            $splited =  preg_split('/' . $schema['seperator'] . '+/', trim($line));
            $record['emp_code'] = isset($splited[$schema['emp_code']['location']]) ?  $splited[$schema['emp_code']['location']] : '';
            $date_time =  $this->setDateFormat($schema['rec_date']['format'] . " " . $schema['rec_time']['format'],   substr($splited[$schema['rec_date']['location']], $schema['rec_date']['start'], $schema['rec_date']['length']) . ' ' . substr($splited[$schema['rec_time']['location']], $schema['rec_time']['start'], $schema['rec_time']['length']));
            $record['rec_date'] =  $date_time->format('Y-m-d');
            $record['rec_time'] =  $date_time->format('H:i:s');
            $record['rec_date_time'] =  $date_time->format('Y-m-d H:i:s');
            $record['status'] = isset($schema['status']['location']) ?  $splited[$schema['status']['location']] : '';
            $record['device_id'] = trim(isset($schema['device_id']['location']) ? $splited[$schema['device_id']['location']] : '0');
        } else {

            $date_time =  $this->setDateFormat($schema['rec_date']['format'] . " " . $schema['rec_time']['format'],  substr($line, $schema['rec_date']['start'], $schema['rec_date']['length']) . ' ' . substr($line, $schema['rec_time']['start'], $schema['rec_time']['length']));
            $record['emp_code'] =  isset($schema['emp_code']['start']) ?  substr($line, $schema['emp_code']['start'], $schema['emp_code']['length']) : '';
            $record['rec_date'] =  $date_time->format('Y-m-d');
            $record['rec_time'] =   $date_time->format('H:i:s');
            $record['rec_date_time'] =  $date_time->format('Y-m-d H:i:s');
            $record['status'] = isset($schema['status']['start']) ?  substr($line, $schema['status']['start'], $schema['status']['length']) : '';
            $record['device_id'] = trim(isset($schema['device_id']['start']) ? substr($line, $schema['device_id']['start'], $schema['device_id']['length']) : '0');
        }


        return $record;
    }


    public function  setDateFormat($format, $value)
    {
        $value = Carbon::createFromFormat($format, $value);
        return  $value;
    }
}
