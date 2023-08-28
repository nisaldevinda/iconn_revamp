<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Interfaces\ModelReaderInterface;
use App\Library\JsonModel;
use App\Library\ModelValidator;
use App\Library\Session;
use App\Library\Store;
use App\Library\Util;
use Attribute;
use Illuminate\Support\Facades\Lang;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Traits\JsonModelReader;

/**
 * Name: DashboardService
 * Purpose: Performs tasks related to the Dashboard model.
 * Description: Dashboard Service class is called by the DashboardController where the requests related
 * to Dashboard Model (CRUD operations and others).
 * Module Creator: Manjula
 */
class DashboardService extends BaseService
{
    use JsonModelReader;

    private $dashboardModel;
    private $store;
    protected $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->dashboardModel = $this->getModel('dashboard', true);
        $this->session = $session;
    }

    /**
     * Following function return a dashboard.
     *
     * @param $id dashboard id
     * @param $dashboard array containing dashboard data
     * @return json | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Dashboard retrieved Successfully",
     */
    public function getDashboard($employeeId)
    {
        try {
            $modelName = 'dashboard';
            $filePath = base_path('app/Models') . "/$modelName.json";
            if (!file_exists($filePath)) {
                throw new Exception("{$modelName} Model not exist.");
            }

            $jsonString = file_get_contents($filePath); // get file content as string
            $jsonString = preg_replace('![ \t]*//.*[ \t]*[\r\n]!', '', $jsonString);    // strip comments
            $content = json_decode($jsonString, true);  // get json file content as array

            if (empty($result)) {
                $sessionGot = $this->session;
                $user = $sessionGot->getUser();
                $employeeUser = false;
                $managerUser = false;
                $adminUser = false;

                if ($user->employeeRoleId) {
                    $employeeUser = true;
                }
                if ($user->managerRoleId) {
                    $managerUser = true;
                }
                if ($user->adminRoleId) {
                    $adminUser = true;
                }

                $fields = $content["fields"];
                $layouts = $fields["layout"];
                $attributes = $layouts["attributes"];

                $queryBuilder = $this->store->getFacade();
                $dashboardModelName = $this->dashboardModel->getName();

                $result = $queryBuilder::table($dashboardModelName)
                    ->where('employeeId', $employeeId)
                    ->first();

                $oneWidthWidgets = array();
                $twoWidthWidgets = array();
                $oneWidthStaticWidgets = array();
                $twoWidthStaticWidgets = array();
                $Widgets = array();

                foreach ($attributes as $key => $widget) {
                    $widget = (object)$widget;
                    $widgetData = (object)$widget->data;
                    $widgetPermissions = $widgetData->hasAccess;

                    if (
                        $employeeUser && in_array("employee-widget", $widgetPermissions) ||
                        $managerUser && in_array("manager-widget", $widgetPermissions) ||
                        $adminUser && in_array("admin-widget", $widgetPermissions)
                    ) {
                        if ($widget->w === 4) {
                            if ($widget->static) {
                                array_push($oneWidthStaticWidgets, $widget);
                            } else {
                                array_push($oneWidthWidgets, $widget);
                            }
                        } else {
                            if ($widget->static) {
                                array_push($twoWidthStaticWidgets, $widget);
                            } else {
                                array_push($twoWidthWidgets, $widget);
                            }
                        }
                    }
                }

                $unCompletedRow = 0;
                $completedRow = 0;

                // fill rows with two width static Widgets 
                foreach ($twoWidthStaticWidgets as $key => $widget) {
                    $widget = (object)$widget;
                    $widget->x = 0;
                    $widget->y = $key * 2;
                    array_push($Widgets, $widget);
                    $unCompletedRow = $unCompletedRow + 1;
                }

                // fill rows with one width static Widgets after two width static Widgets 
                $currentColumn = 0;
                $currentRow = 0;
                foreach ($oneWidthStaticWidgets as $key => $widget) {
                    $widget = (object)$widget;
                    if (count($twoWidthStaticWidgets) !== 0 && count($twoWidthStaticWidgets) >= $key) {
                        $widget->x = 8;
                        $widget->y = $key * 2;
                        array_push($Widgets, $widget);
                        $currentRow = ($key * 2) + 2;
                        $completedRow = $completedRow + 1;
                    } else {
                        $widget->x = $currentColumn;
                        $widget->y = $currentRow;
                        array_push($Widgets, $widget);

                        if ($currentColumn === 8) {
                            $currentColumn = 0;
                            $currentRow = $currentRow + 2;
                            $completedRow = $completedRow + 1;
                        } else if ($currentColumn === 4) {
                            $currentColumn = $currentColumn + 4;
                        } else {
                            $currentColumn = $currentColumn + 4;
                            $unCompletedRow = $unCompletedRow + 1;
                        }
                    }
                }

                // fill rows with two width Widgets 
                foreach ($twoWidthWidgets as $key => $widget) {
                    $widget = (object)$widget;
                    if ($currentColumn === 0) {
                        $widget->x = 0;
                        $widget->y = $currentRow;
                        array_push($Widgets, $widget);
                        $currentRow = $currentRow + 2;
                        $unCompletedRow = $unCompletedRow + 1;
                    } else if ($currentColumn === 4) {
                        $widget->x = 4;
                        $widget->y = $currentRow;
                        array_push($Widgets, $widget);
                        $currentColumn = 0;
                        $currentRow = $currentRow + 2;
                        $completedRow = $completedRow + 1;
                    } else {
                        $widget->x = 0;
                        $widget->y = $currentRow + 2;
                        array_push($Widgets, $widget);
                        $currentRow = $currentRow + 2 + 2;
                        $unCompletedRow = $unCompletedRow + 1;
                    }
                }

                $remainingToFill = $unCompletedRow - $completedRow;

                if ($unCompletedRow === $completedRow) {
                    $oneWidthWidgetsColumn = 0;
                    $oneWidthWidgetsRow =  $completedRow;
                    foreach ($oneWidthWidgets as $key => $widget) {
                        $widget = (object)$widget;
                        $widget->x = $oneWidthWidgetsColumn;
                        $widget->y = $oneWidthWidgetsRow;
                        array_push($Widgets, $widget);

                        if ($oneWidthWidgetsColumn === 8) {
                            $oneWidthWidgetsRow = $oneWidthWidgetsRow + 2;
                            $oneWidthWidgetsColumn = 0;
                        } else {
                            $oneWidthWidgetsColumn = $oneWidthWidgetsColumn + 4;
                        }
                    }
                } else {
                    $oneWidthWidgetsColumn = 0;
                    $oneWidthWidgetsRow =  $unCompletedRow;
                    foreach ($oneWidthWidgets as $key => $widget) {
                        $widget = (object)$widget;
                        if ($remainingToFill > $key) {
                            $widget->x = 8;
                            $widget->y = $completedRow + $key;
                            array_push($Widgets, $widget);
                        } else {
                            if ($oneWidthWidgetsColumn === 0) {
                                $widget->x = 0;
                                $widget->y = $oneWidthWidgetsRow;
                                array_push($Widgets, $widget);
                                $oneWidthWidgetsColumn === 4;
                            } else if ($oneWidthWidgetsColumn === 4) {
                                $widget->x = 4;
                                $widget->y = $oneWidthWidgetsRow;
                                array_push($Widgets, $widget);
                                $oneWidthWidgetsColumn === 8;
                            } else {
                                $widget->x = 8;
                                $widget->y = $oneWidthWidgetsRow;
                                array_push($Widgets, $widget);
                                $oneWidthWidgetsColumn === 0;
                                $oneWidthWidgetsRow = $oneWidthWidgetsRow + 2;
                            }
                        }
                    }
                }

                return $this->success(200, Lang::get('dashboardMessages.basic.SUCC_GET'), $Widgets);
            } else {
                $resultLayout = $result->layout;
                $resultLayout = json_decode($resultLayout, true);
                $changedAttributes = [];

                //check json model with data base to check weather position got changed
                foreach ($attributes as $objModel) {
                    foreach ($resultLayout as $objResult) {
                        if ($objResult["i"] == $objModel["i"]) {
                            $objModel["x"] = $objResult["x"];
                            $objModel["y"] = $objResult["y"];

                            array_push($changedAttributes, $objModel);
                        }
                    }
                }

                return $this->success(203, Lang::get('dashboardMessages.basic.SUCC_GET'), $changedAttributes);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(402, Lang::get('dashboardMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function updates a dashboard.
     *
     * @param $id dashboard id
     * @param $dashboard array containing dashboard data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Dashboard updated Successfully",
     */
    public function updateDashboard($employeeId, $dashboard)
    {
        try {
            $queryBuilder = $this->store->getFacade();
            $dashboardModelName = $this->dashboardModel->getName();

            $existingDashboard = $queryBuilder::table($dashboardModelName)
                ->where('employeeId', $employeeId)
                ->first();

            $newDataSet = json_encode($dashboard);
            $newData = (array) [
                'employeeId' => $employeeId,
                'layout' => $newDataSet
            ];

            if (empty($existingDashboard)) {
                $result = $this->store->insert($this->dashboardModel, $newData, true);

                if (!$result) {
                    return $this->error(502, Lang::get('dashboardMessages.basic.ERR_CREATE'), $employeeId);
                } else {
                    return $this->success(200, Lang::get('dashboardMessages.basic.SUCC_CREATE'), $result);
                }
            } else {
                $result = $this->store->updateById($this->dashboardModel, $existingDashboard->id, $newData, true);

                if (!$result) {
                    return $this->error(502, Lang::get('dashboardMessages.basic.ERR_UPDATE'), $employeeId);
                } else {
                    return $this->success(200, Lang::get('dashboardMessages.basic.SUCC_UPDATE'), $result);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(402, Lang::get('dashboardMessages.basic.ERR_SAVE'), null);
        }
    }
}
