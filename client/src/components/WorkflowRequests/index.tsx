/* eslint-disable guard-for-in */
/* eslint-disable no-restricted-syntax */
import React, { useState, Key, useRef, useEffect } from 'react';

import type { ProColumns, ColumnsState } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { Button, message, Alert, Space, Popconfirm, Spin, Tag, Row, Col, Tabs, Form } from 'antd';
import type { ConfirmModelLoadingTypes } from '../../data';
import { EyeOutlined, ArrowLeftOutlined } from '@ant-design/icons';
import { ModalForm } from '@ant-design/pro-form';
const { TabPane } = Tabs;
// change this accordingly

import {
  queryWorkflowInstances,
  updateInstance,
  queryWorkflowFilterOptions,
  getMyRequests,
  accessibleWorkflowActions,
} from '@/services/workflowServices';
import myInfoService from '@/services/myInfoView';
import { getEmployee, getMultiReocrdData, getDataDiffForProfileUpdate } from '@/services/employee';
import { getEmployee as getTeamMember } from '@/services/myTeams';
import { cancelLeaveDates, cancelShortLeave } from '@/services/leave';
import { checkShortLeaveAccessabilityForCompany } from '@/services/leave';

import { useIntl, history, useAccess } from 'umi';
import _ from 'lodash';
import moment from 'moment-timezone';
import styles from './index.less';
import BusinessCard from '@/components/BusinessCard';
import { getModel, Models } from '@/services/model';
import LeaveRequest from './leaveRequest';
import ShortLeaveRequest from './shortLeaveRequest';
import CancelShortLeaveRequest from './cancelShortLeaveRequest';
import ClaimRequest from './claimRequest';
import PostOtRequest from './postOtRequest';
import TimeChangeRequest from './timeChangeRequest';
import ShiftChangeRequest from './shiftChangeRequest';
import CancelLeaveRequest from './cancelLeaveRequest';
import ResignationRequest from './resignationRequest';
import ProfileChangeRequest from './profileUpdateRequests';
import PermissionDeniedPage from '@/pages/403';
import LeaveComment from '../LeaveRequest/leaveComment';
import request, { APIResponse } from '@/utils/request';

moment.locale('en');

export type WorkflowProps = {
  pageType: pageEnum;
};
type pageEnum = 'allRequests' | 'myRequests';

// change as the page

type TableListItem = {
  id: number;
  workflowId: number;
  actionId: string;
  actionName: string;
  priorState: number;
  priorStateName: string;
  details: string;
  createdAt: string;
  contextName: string;
};

// change the page type

interface TableType {
  reload: (resetPageIndex?: boolean) => void;
  reloadAndRest: () => void;
  reset: () => void;
  clearSelected?: () => void;
  startEditable: (rowKey: Key) => boolean;
  cancelEditable: (rowKey: Key) => boolean;
}

const WorkflowInstance: React.FC<WorkflowProps> = (props) => {
  const { hasPermitted, hasAnyPermission } = useAccess();

  const [columnsStateMap, setColumnsStateMap] = useState<Record<string, ColumnsState>>({
    name: {
      show: false,
    },
    order: 2,
  });

  const [confirmLoading, setConfirmLoading] = useState<ConfirmModelLoadingTypes>({
    add: false,
    edit: false,
  });

  const [addModalVisible, handleAddModalVisible] = useState<boolean>(false);
  const tableRefAll = useRef<TableType>();
  const tableRefLeave = useRef<TableType>();
  const tableRefShortLeave = useRef<TableType>();
  const tableRefShiftChange = useRef<TableType>();
  const tableRefCancelLeave = useRef<TableType>();
  const tableRefResignation = useRef<TableType>();
  const tableRefCancelShortLeave = useRef<TableType>();
  const tableRefClaim = useRef<TableType>();
  const tableRefPostOt = useRef<TableType>();
  const tableRefTime = useRef<TableType>();
  const tableRefProfile = useRef<TableType>();
  const [dataCount, setDataCount] = useState(0);
  const intl = useIntl();
  const [approverCommentForm] = Form.useForm();

  const [selectedRow, setSelectedRow] = useState({});
  const [dataChanges, setDataChanges] = useState([]);
  const [loggedInUser, setLogedInUser] = useState(0);
  const [currentTabKey, setCurrentTabKey] = useState('all');
  const [attendanceSheetData, setAttendanceSheetData] = useState([]);
  const [requestState, setRequestState] = useState(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isShowCancelView, setIsShowCancelView] = useState<boolean>(false);
  const [isLoadAllRequest, setIsLoadAllRequest] = useState<boolean>(false);
  const [actions, setActions] = useState<any>([]);
  const [isApproveActionAvailable, setIsApproveActionAvailable] = useState<any>(false);
  const [model, setModel] = useState<any>();
  const [relatedModel, setRelatedModel] = useState<any>();
  const [updatedTimeOld, setUpdatedTimeOld] = useState();
  const [updatedTimeNew, setUpdatedTimeNew] = useState();
  const [isChangesAreNew, setisChangesAreNew] = useState(false);
  const [showThisIsFailureState, setShowThisIsFailureState] = useState(false);
  const [leaveDataSet, setleaveDataSet] = useState({});
  const [timeChangeDataSet, setTimeChangeDataSet] = useState({});
  const [shiftChangeDataSet, setShiftChangeDataSet] = useState({});
  const [cancelLeaveDataSet, setCancelLeaveDataSet] = useState({});
  const [cancelShortLeaveData, setCancelShortLeaveData] = useState({});
  const [claimRequestData, setClaimRequestData] = useState({});
  const [postOtRequestData, setPostOtRequestData] = useState({});
  const [resignationDataSet, setResignationDataSet] = useState({});
  const [employeeName, setEmployeeName] = useState<string | null>(null);
  const [employeeNumber, setEmployeeNumber] = useState<string | null>(null);
  const [hireDate, setHireDate] = useState<string | null>(null);
  const [workflowInstanceId, setWorkflowInstanceId] = useState<string | null>(null);
  const [approverComment, setApproverComment] = useState<string | null>(null);
  const [resignationUpdatedEffectiveDate, setResignationUpdatedEffectiveDate] = useState<string | null>(null);
  const [relateScope, setRelateScope] = useState<string | null>(null);
  const [contextType, setContextType] = useState<string | null>('all');
  const [isShowShortLeaveTab, setIsShowShortLeaveTab] = useState(false);
  const [hasPermissionForAllRequests, setHasPermissionForAllRequests] = useState<boolean>(true);
  const [hasPermissionForProfileUpdateRequests, setHasPermissionForProfileUpdateRequests] = useState<boolean>(false);
  const [hasPermissionForLeaveRequests, setHasPermissionForLeaveRequests] = useState<boolean>(false);
  const [hasPermissionForShortLeaveRequests, setHasPermissionForShortLeaveRequests] = useState<boolean>(false);
  const [hasPermissionForShiftChangeRequests, setHasPermissionForShiftChangeRequests] = useState<boolean>(false);
  const [hasPermissionForCancelLeaveRequests, setHasPermissionForCancelLeaveRequests] = useState<boolean>(false);
  const [hasPermissionForResignationRequests, setHasPermissionForResignationRequests] = useState<boolean>(false);
  const [hasPermissionForCancelShortLeaveRequests, setHasPermissionForCancelShortLeaveRequests] = useState<boolean>(false);
  const [hasPermissionForClaimRequests, setHasPermissionForClaimRequests] = useState<boolean>(false);
  const [hasPermissionForPostOtRequests, setHasPermissionForPostOtRequests] = useState<boolean>(false);
  const [hasPermissionForTimeChangeRequests, setHasPermissionForTimeChangeRequests] = useState<boolean>(false);
  const [leaveCancelDates, setLeaveCancelDates] = useState([]);
  const [intialData, setIntialData] = useState<any>([]);

  useEffect(() => {
    if (!model) {
      getModel(Models.Employee, 'edit').then((model) => {
        if (model && model.data) {
          setModel(model.data);
        }
      });
      checkShortLeaveAccessability();
    }

  });

  useEffect(() => {
    if (history.location.state) {
      changeTabKey(history.location.state.tabKey);
    }

  }, []);

  const checkShortLeaveAccessability = async () => {
    try {
      const response = await checkShortLeaveAccessabilityForCompany({});
      setIsShowShortLeaveTab(response.data.isMaintainShortLeave);
    } catch (err) {
      console.log(err);
    }
  };

  const getLastUpdatedTime = (field) => {
    if (field === 'currentVal') {
      return (
        <>
          <div>Current Data</div>
          <div className={styles.secondryInfo}>
            Last updated: {(updatedTimeOld) ? updatedTimeOld : '  --'}
          </div>
        </>
      );
    }
    return (
      <>
        <div>New Data</div>
        <div className={styles.secondryInfo}>
          Submitted on: {(updatedTimeNew) ? updatedTimeNew : '--'}
        </div>
      </>
    );
  };

  const refreshLeaveList = () => {
    switch (contextType) {
      case 'all':
        setHasPermissionForAllRequests(true);
        tableRefAll.current?.reload();
        break;
      case '2':
        setHasPermissionForLeaveRequests(true);
        tableRefLeave.current?.reload();
        break;
      case '4':
        setHasPermissionForShortLeaveRequests(true);
        tableRefShortLeave.current?.reload();
        break;
      case '5':
        setHasPermissionForShiftChangeRequests(true);
        tableRefShiftChange.current?.reload();
        break;
      case '6':
        setHasPermissionForCancelLeaveRequests(true);
        tableRefCancelLeave.current?.reload();
        break;
      case '7':
        setHasPermissionForResignationRequests(true);
        tableRefResignation.current?.reload();
        break;
      case '8':
        setHasPermissionForCancelShortLeaveRequests(true);
        tableRefCancelShortLeave.current?.reload();
        break;
      case '9':
        setHasPermissionForClaimRequests(true);
        tableRefClaim.current?.reload();
        break;
      case '10':
        setHasPermissionForPostOtRequests(true);
        tableRefPostOt.current?.reload();
        break;

      default:
        break;
    }
  }


  useEffect(() => {
    if (addModalVisible) {
      setApproverComment(null);
      setAttendanceSheetData([]);
      setIntialData([]);
      approverCommentForm.setFieldsValue({ approverComment: null });
      approverCommentForm.setFieldsValue({ cancelReason: null });
    }
  }, [addModalVisible]);


  const getFilter = async () => {
    const data = await queryWorkflowFilterOptions();

    const o = [
      {
        label: 'All',
        value: 'All',
      },
      {
        label: 'Pending',
        value: 'Pending',
      },
      {
        label: 'Approved',
        value: 'Approved',
      },
      {
        label: 'Rejected',
        value: 'Rejected',
      },
      {
        label: 'Cancelled',
        value: 'Cancelled',
      },
    ];
    // await data.data.forEach((element) => {
    //   o.push({
    //     label: element,
    //     value: element,
    //   });
    // });
    return o;
  };

  const getEmployeeData = (event, relatedModel, relations) => {
    const requestScope = props.pageType === 'allRequests' ? 'MANAGER' : 'EMPLOYEE';
    const { employeeId, workflowId } = event;

    if (event.isCoveringPersonsRelateLeave) {
      myInfoService.getEmployee().then((response) => {
        if (response && response.data) {
          if (event.contextId == 2) {
            let empName = event.firstName + ' ' + event.lastName;
            setEmployeeName(empName);
            setleaveDataSet(JSON.parse(event.details))
          }
        }
      });
      setRelateScope('EMPLOYEE');
      setIsLoading(false);

    } else {
      setWorkflowInstanceId(event.id);
      accessibleWorkflowActions(workflowId, employeeId, { scope: requestScope }, event.id)
        .then((response) => {
          const { actions, scope } = response.data;

          let hasApproveAction = false;
          if (actions.length > 0) {
            actions.map((element) => {
              if (element.id == 2 && !hasApproveAction) {
                hasApproveAction = true;
              }
            })
          }
          setIsApproveActionAvailable(hasApproveAction);
          setActions(actions);
          const { pageType } = props;
          const details = JSON.parse(event.details);
          setRelateScope(scope);

          if (event.contextId == 1 && !event.isInFinalSucessState) {
            let params = {
              'employeeId': event.employeeId,
              'workflowInstanceId': event.id,
              'modalName': relatedModel.modelDataDefinition.name,
              'scope': scope,
              'pageType': pageType
            }

            getDataDiffForProfileUpdate(params).then((res) => {
              setDataChanges(res.data);
            });

          } else {
            setDataChanges([]);
          }

          setIsLoading(false);

          if (event.contextId == 1) {
            if (details['isMultiRecord'] == false) {
              if (pageType === 'allRequests') {
                if (scope === 'MANAGER') {
                  return getTeamMember(event.employeeId);
                } else {
                  return getEmployee(event.employeeId);
                }
              } else { // if my requests
                return myInfoService.getEmployee();
              }
            } else {
              let id = (details['id']) ? details['id'] : 'new'
              return getMultiReocrdData(id, details['tabName']);
            }

          } else {
            if (pageType === 'allRequests') {
              if (scope === 'MANAGER') {
                return getTeamMember(event.employeeId);
              } else {
                return getEmployee(event.employeeId);
              }
            } else { // if my requests
              return myInfoService.getEmployee();
            }
          }

        })
        .then((response) => {
          if (response && response.data) {
            if (event.contextId == 1) {
              const currentTime = _.get(event, 'updatedAt', null);
              const oldTime = _.get(response, 'data.updatedAt', null);
              setUpdatedTimeOld(oldTime);
              setUpdatedTimeNew(currentTime);
              const objectDetails = JSON.parse(event.details);

              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);

              if (!event.isInFinalSucessState) {
                setisChangesAreNew(false);
              } else if (!event.isInFinalFaliureState && event.isInFinalSucessState) {
                setisChangesAreNew(true);
              }

              if (!event.isInFinalFaliureState) {
                setShowThisIsFailureState(false);
              } else if (!event.isInFinalSucessState && event.isInFinalFaliureState) {
                setShowThisIsFailureState(true);

              }
              if (response.data.length == 0) {
                //set object for create object
                Object.keys(objectDetails).map((keyName, keyIndex) => {
                  response.data[keyName] = '-';
                });
              }
              // setDataChanges(changes(response.data, JSON.parse(event.details), relatedModel, event.contextId, relations));
            } else if (event.contextId == 2) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setleaveDataSet(JSON.parse(event.details))
            } else if (event.contextId == 3) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setTimeChangeDataSet(JSON.parse(event.details))
            } else if (event.contextId == 4) {
              let shortLeaveData = JSON.parse(event.details);
              shortLeaveData.leavePeriodType = shortLeaveData.shortLeaveType;
              shortLeaveData.fromDate = shortLeaveData.date;
              shortLeaveData.toDate = shortLeaveData.date;
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setleaveDataSet(shortLeaveData)
            } else if (event.contextId == 5) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setShiftChangeDataSet(JSON.parse(event.details))

            } else if (event.contextId == 6) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setCancelLeaveDataSet(JSON.parse(event.details))

            } else if (event.contextId == 7) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setEmployeeNumber(response.data.employeeNumber);
              setHireDate(response.data.hireDate);
              setResignationDataSet(JSON.parse(event.details))

            } else if (event.contextId == 8) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setCancelShortLeaveData(JSON.parse(event.details))
            } else if (event.contextId == 9) {
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setClaimRequestData(JSON.parse(event.details))
            } else if (event.contextId == 10) {
              let detail = JSON.parse(event.details);
              getClaimRequestReceiptDetails(detail.id);
              let empName = event.firstName + ' ' + event.lastName;
              setEmployeeName(empName);
              setPostOtRequestData(JSON.parse(event.details))
            }
          }
        });

    }




  };

  const getClaimRequestReceiptDetails = async (id) => {
    try {
      if (id != null) {
        let path: string;
        path =
          `/api/attendanceSheet/getAttendanceDetailsByPostOtRequestId/` + id;
        const result = await request(path);
        if (result['data'] !== null) {
          setAttendanceSheetData(result['data']['sheets']);
          setRequestState(result['data']['requestState']);
          let orgData = [];
          result['data']['sheets'].map((sheet) => {
            let tempObj = {
              in: sheet.in.time,
              id: sheet.id,
              out: sheet.out.time,
              outDate: sheet.outDate,
              shiftId: sheet.shiftId,
              shift: sheet.shift,
              approveUserComment: sheet.approveUserComment ? sheet.approveUserComment : null
            };

            for (let index in sheet.otData.approvedOtDetails) {
              let payKey = 'approved' + index;
              tempObj[payKey] = sheet.otData.approvedOtDetails[index];
            }

            orgData.push(tempObj);
          });

          setIntialData([...orgData]);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  const showModal = async (event) => {
    setSelectedRow(event);
    setDataChanges([]);
    setIsLoading(true);
    setisChangesAreNew(false);
    setShowThisIsFailureState(false);
    setIsShowCancelView(false);
    let details = JSON.parse(event.details);

    details['modelName'] = (details['modelName']) ? details['modelName'] : 'employee'

    const response = await getModel(details['modelName'], 'edit');
    setRelatedModel(response.data);
    var relations: object = {};

    getEmployeeData(event, response.data, relations);
    await setConfirmLoading({ ...confirmLoading, add: true });
    await handleAddModalVisible(true);
  };

  const updateWorkflowInstance = (actionId, instanceId, workflowId, contextId) => {

    let updatedEffectiveDate = null;
    let postOtDetails = [];
    if (contextId == 7) {
      updatedEffectiveDate = resignationUpdatedEffectiveDate;
    }

    if (contextId == 10) {
      // postOtDetails = attendanceSheetData;


      attendanceSheetData.map((el, index) => {
        let obj = {
          'id': el.id,
          'otDetails': el.otData,
          'approveUserComment': el.approveUserComment ? el.approveUserComment : null,
          'approveUserCommentList': el.approveUserCommentList
        }

        postOtDetails.push(obj);

      });
    }

    updateInstance({
      actionId,
      instanceId,
      workflowId,
      contextId,
      relateScope,
      approverComment,
      updatedEffectiveDate,
      postOtDetails
    }).then((res) => {
      switch (contextType) {
        case 'all':
          setHasPermissionForAllRequests(true);
          tableRefAll.current?.reload();
          break;
        case '1':
          setHasPermissionForProfileUpdateRequests(true);
          tableRefProfile.current?.reload();
          break;
        case '2':
          setHasPermissionForLeaveRequests(true);
          tableRefLeave.current?.reload();
          break;
        case '3':
          setHasPermissionForTimeChangeRequests(true);
          tableRefTime.current?.reload();
          break;
        case '4':
          setHasPermissionForShortLeaveRequests(true);
          tableRefShortLeave.current?.reload();
          break;
        case '5':
          setHasPermissionForShiftChangeRequests(true);
          tableRefShiftChange.current?.reload();
          break;
        case '6':
          setHasPermissionForCancelLeaveRequests(true);
          tableRefCancelLeave.current?.reload();
          break;
        case '7':
          setHasPermissionForResignationRequests(true);
          tableRefResignation.current?.reload();
          break;
        case '8':
          setHasPermissionForCancelShortLeaveRequests(true);
          tableRefCancelShortLeave.current?.reload();
          break;
        case '9':
          setHasPermissionForClaimRequests(true);
          tableRefClaim.current?.reload();
          break;
        case '10':
          setHasPermissionForPostOtRequests(true);
          tableRefPostOt.current?.reload();
          break;

        default:
          break;
      }

      message.success(res.message);
    }).catch((error: APIResponse) => {
      message.error(error.message);
    });

    handleAddModalVisible(false);

  };
  const removeTimeStamps = (object) => {
    const primaryObject = object;
    for (const key in primaryObject) {
      const currrentKey = key;
      if (primaryObject[key] && Array.isArray(primaryObject[key])) {
        primaryObject[key] = _.map(primaryObject[key], (obj) => {
          return _.omit(obj, 'updatedAt');
        });
      }
    }
    return primaryObject;
  };
  // const changes = (newData, oldData, relatedModel, contextId, relations) => {
  //   let oldValues = removeTimeStamps(oldData);
  //   const currentValues = removeTimeStamps(newData);
  //   oldValues = _.omit(oldData, ['id', 'updatedAt']);
  //   const newValues = _.omit(currentValues, ['isDelete', 'fullName', 'id', 'updatedAt']);
  //   const replacedNewVal = JSON.stringify(newValues);

  //   if (contextId == 1 && oldData['isMultiRecord'] == false) {
  //       const diferences = diff(JSON.parse(replacedNewVal), oldValues);
  //       const returnarr = Object.keys(model.modelDataDefinition.fields).map((keyName, keyIndex) => {
  //         let isMultiRecord = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').isMultiRecord ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').isMultiRecord : false;
  //         let columnName = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').tableColumnName ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').tableColumnName : keyName;
  //         let modelName = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').modelName ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').modelName : null;
  //         var currentValue = (_.get(diferences[columnName], '__old', '-') != null && _.get(diferences[columnName], '__old', '-') != '') ? _.get(diferences[columnName], '__old', '-') : '-';
  //         var newVal = (_.get(diferences[columnName], '__new', '-') != null &&  _.get(diferences[columnName], '__new', '-') != '') ? _.get(diferences[columnName], '__new', '-') : '-';

  //         if (modelName != null && !isMultiRecord) {

  //           if (Object.keys(relations[modelName]).length > 0) {
  //             relations[modelName].forEach(element => {
  //               if (element.id == newVal) {
  //                 let col = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').displayCol;
  //                 newVal =  element[col];
  //               }

  //               if (element.id == currentValue) {
  //                 let col = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').displayCol;
  //                 console.log(element[col]);
  //                 currentValue =  element[col];
  //               }
  //             }); 
  //           }
  //         }
  //         return {
  //           key: keyIndex,
  //           field: (_.get(relatedModel.modelDataDefinition.fields, keyName, false) && currentValue != newVal && (keyName != 'id' && keyName != 'employeeId' && keyName != 'createdAt' && keyName != 'createdBy' && keyName != 'updatedAt' && keyName != 'updatedBy'))
  //           ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').defaultLabel
  //           : 'noData',
  //           currentVal: currentValue,
  //           newVal: newVal,
  //           fieldName: keyName,
  //           fieldSubName: keyName,
  //         };
  //       });
  //       return returnarr.filter(({ field }) => !field.includes('noData'));
  //     } else {
  //         const returnarr = Object.keys(relatedModel.modelDataDefinition.fields).map((keyName, keyIndex) => {
  //             let columnName = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').tableColumnName ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').tableColumnName : keyName;

  //             let modelName = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').modelName ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').modelName : null;

  //             if (modelName == null) {
  //               var newFieldVal = (newData[columnName]) ? newData[columnName] : '-'
  //               var oldFieldVal = (oldData[columnName]) ? oldData[columnName] : '-'
  //             } else {        
  //                 if (Object.keys(relations[modelName]).length > 0) {
  //                   newFieldVal = '-';
  //                   oldFieldVal = '-';
  //                   relations[modelName].forEach(element => {
  //                     if (element.id == newData[columnName]) {
  //                       let col = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').displayCol;
  //                       newFieldVal =  element[col];
  //                     }

  //                     if (element.id == oldData[columnName]) {
  //                       let col = _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').displayCol;
  //                       oldFieldVal =  element[col];
  //                     }
  //                   });

  //                 } else {
  //                   var newFieldVal = (newData[columnName]) ? newData[columnName] : '-'
  //                   var oldFieldVal = (oldData[columnName]) ? oldData[columnName] : '-'
  //                 }
  //             }

  //             return {
  //               key: keyIndex,
  //               field: (_.get(relatedModel.modelDataDefinition.fields, keyName, false) && (keyName != 'id' && keyName != 'employeeId' && keyName != 'createdAt' && keyName != 'createdBy' && keyName != 'updatedAt' && keyName != 'updatedBy'))
  //                 ? _.get(relatedModel.modelDataDefinition.fields, keyName, 'false').defaultLabel
  //                 : 'noData',
  //               currentVal: newFieldVal,
  //               newVal: oldFieldVal,
  //               fieldName: keyName,
  //               fieldSubName: keyName,
  //             };
  //         });
  //       return returnarr.filter(({ field }) => !field.includes('noData'));
  //   }

  // };

  useEffect(() => {
    const loggedinUser = JSON.parse(localStorage.getItem('user_session'));
    setLogedInUser(_.get(loggedinUser, 'userId', false));
  }, []);

  const requestData = async (params, sorter) => {
    params.contextType = contextType;
    if (props.pageType === 'allRequests') {
      return queryWorkflowInstances(loggedInUser, { ...params, sorter }).then((res) => {
        let response = {
          'data': res.data.sheets
        }
        setDataCount(res.data.count);
        return response;
        return res;
      }).catch((e) => {
        if (e.status == '403') {
          switch (contextType) {
            case 'all':
              setHasPermissionForAllRequests(false);
              break;
            case '1':
              setHasPermissionForProfileUpdateRequests(false);
              break;
            case '2':
              setHasPermissionForLeaveRequests(false);
              break;
            case '3':
              setHasPermissionForTimeChangeRequests(false);
              break;
            case '4':
              setHasPermissionForShortLeaveRequests(false);
              break;
            case '5':
              setHasPermissionForShiftChangeRequests(false);
              break;
            case '6':
              setHasPermissionForCancelLeaveRequests(false);
              break;
            case '7':
              setHasPermissionForResignationRequests(false);
              break;
            case '8':
              setHasPermissionForCancelShortLeaveRequests(false);
              break;
            case '9':
              setHasPermissionForClaimRequests(false);
              break;
            case '10':
              setHasPermissionForPostOtRequests(false);
              break;

            default:
              break;
          }
        }
      });
    }

    let result = await getMyRequests({ ...params, sorter });
    let resData = {
      'data': result.data.sheets
    }
    setDataCount(result.data.count);
    return resData;
  };

  const requestAllData = async (params, sorter) => {

    params.contextType = contextType;
    if (props.pageType === 'allRequests') {
      return queryWorkflowInstances(loggedInUser, { ...params, sorter }).then((res) => {
        setIsLoadAllRequest(true);
        let response = {
          'data': res.data.sheets
        }
        setDataCount(res.data.count);
        return response;
      }).catch((e) => {
        if (e.status == '403') {
          switch (contextType) {
            case 'all':
              setHasPermissionForAllRequests(false);
              break;
            case '1':
              setHasPermissionForProfileUpdateRequests(false);
              break;
            case '2':
              setHasPermissionForLeaveRequests(false);
              break;
            case '3':
              setHasPermissionForTimeChangeRequests(false);
              break;
            case '4':
              setHasPermissionForShortLeaveRequests(false);
              break;
            case '5':
              setHasPermissionForShiftChangeRequests(false);
              break;
            case '6':
              setHasPermissionForCancelLeaveRequests(false);
              break;
            case '7':
              setHasPermissionForResignationRequests(false);
              break;
            case '8':
              setHasPermissionForCancelShortLeaveRequests(false);
              break;
            case '9':
              setHasPermissionForClaimRequests(false);
              break;
            case '10':
              setHasPermissionForPostOtRequests(false);
              break;

            default:
              break;
          }
        }
      });
    }

    let result = await getMyRequests({ ...params, sorter });
    setIsLoadAllRequest(true);
    let resData = {
      'data': result.data.sheets
    }
    setDataCount(result.data.count);
    return resData;

    // return res;
  };

  const getNameByDetails = (details: any) => {
    // const data = JSON.parse(details);
    return _.get(details, 'firstName', null);
  };
  const getDateTime = (date: any) => {
    const utcCutoff = moment.utc(date, 'YYYYMMDD HH:mm:ss');
    const displayCutoff = utcCutoff.clone().tz('Asia/Colombo');

    return displayCutoff.format('hh:mm');
  };

  const changeTabKey = (value: any) => {
    setContextType(value);
    setCurrentTabKey(value);
    switch (value) {
      case 'all':
        setHasPermissionForAllRequests(true);
        tableRefAll.current?.reload();
        break;
      case '1':
        setHasPermissionForProfileUpdateRequests(true);
        tableRefProfile.current?.reload();
        break;
      case '2':
        setHasPermissionForLeaveRequests(true);
        tableRefLeave.current?.reload();
        break;
      case '3':
        setHasPermissionForTimeChangeRequests(true);
        tableRefTime.current?.reload();
        break;
      case '4':
        setHasPermissionForShortLeaveRequests(true);
        tableRefShortLeave.current?.reload();
        break;
      case '5':
        setHasPermissionForShiftChangeRequests(true);
        tableRefShiftChange.current?.reload();
        break;
      case '6':
        setHasPermissionForCancelLeaveRequests(true);
        tableRefCancelLeave.current?.reload();
        break;
      case '7':
        setHasPermissionForResignationRequests(true);
        tableRefResignation.current?.reload();
        break;
      case '8':
        setHasPermissionForCancelShortLeaveRequests(true);
        tableRefCancelShortLeave.current?.reload();
        break;
      case '9':
        setHasPermissionForClaimRequests(true);
        tableRefClaim.current?.reload();
        break;
      case '10':
        setHasPermissionForPostOtRequests(true);
        tableRefPostOt.current?.reload();
        break;

      default:
        break;
    }
  };

  const columns: ProColumns<TableListItem>[] = [
    {
      dataIndex: 'createdAt',
      valueType: 'index',
      width: '100px',
      filters: false,
      onFilter: false,
      render: (entity, dom) => [
        <div className={styles.tableDate}>
          <div className={styles.dateTime}>
            <div className={styles.headerDiv}></div>
            <div className={styles.date}>{moment(dom.createdAt).format('DD')}</div>
            <div className={styles.month}>{moment(dom.createdAt).format('MMM YYYY')}</div>
          </div>
        </div>,
      ],
    },
    {
      dataIndex: 'details',
      valueType: 'index',
      width: '600px',
      filters: false,
      render: (entity, dom) => [
        <>
          <div className={styles.profileDetails}>
            {props.pageType === 'allRequests' ? (
              <>
                {' '}
                <Row style={{ marginLeft: 10 }}>
                  <Col>
                    <Row>
                      <Col style={{ color: 'grey' }}>{dom.contextName}&nbsp;Request&nbsp;By&nbsp;</Col>
                      <Col className={styles.profileName} onClick={() => { }}><BusinessCard
                        employeeData={dom}
                        text={getNameByDetails(dom)}
                      /></Col>
                    </Row>
                    <Row>
                      <Col style={{ fontSize: 12, marginBottom: 2 }}>{dom.displayHeading1}</Col>
                    </Row>
                    <Row>
                      <Col style={{ fontSize: 12 }}>{dom.displayHeading2}</Col>
                    </Row>
                  </Col>

                </Row>
              </>
            ) : (
              <Row style={{ marginLeft: 10 }}>
                <Col>
                  <Row>
                    <Col style={{ color: 'grey' }}>{dom.contextName}&nbsp;Request</Col>
                  </Row>
                  <Row>
                    <Col style={{ fontSize: 12, marginBottom: 2 }}>{dom.displayHeading1}</Col>
                  </Row>
                  <Row>
                    <Col style={{ fontSize: 12 }}>{dom.displayHeading2}</Col>
                  </Row>
                </Col>
              </Row>
            )}
          </div>
        </>,
      ],
    },
    {
      dataIndex: 'priorStateName',
      valueType: 'select',
      initialValue: 'All',
      request: async () => getFilter(),
      render: (_, record) => (
        <Space>
          <Tag style={{ borderRadius: 20, fontSize: 17, paddingRight: 20, paddingLeft: 20, paddingTop: 4, paddingBottom: 4, border: 0 }} color={record.stateTagColor}>{record.priorStateName}</Tag>
        </Space>
      ),
    },
    {
      dataIndex: 'requestedOn',
      width: '350px',
      render: (_, record) => (
        <Space style={{ color: '#909A99', fontSize: 16 }}>
          {'Requested On ' + record.requestedOn}
        </Space>
      ),
    },
    {
      dataIndex: 'action',
      valueType: 'index',
      width: '150px',
      render: (entity, dom) => [
        <>
          <div
            className={styles.view}
            style={{ display: 'flex' }}
          >
            <EyeOutlined style={{ paddingTop: 2 }} onClick={() => {
              showModal(dom);
            }} /> {' '}


            {
              (dom.contextId == 2) ? (

                <span style={{ paddingLeft: 10 }}>
                  <LeaveComment leaveData={dom} leaveId={dom.leaveRequestId} refreshLeaveList={refreshLeaveList} ></LeaveComment>
                </span>

              ) : (
                <></>
              )
            }
          </div>

        </>

      ],
    },
  ];

  let tableSec: string = '';
  let tableSubSec: string = '';
  const modalColumns = [
    {
      title: (<div style={{ marginBottom: 38 }} >Section</div>),
      dataIndex: 'fieldName',
      width: '100px',
      render: (fieldName) => {
        const structure = _.get(model, 'frontEndDefinition.structure', '');
        let section;
        const details = JSON.parse(selectedRow['details']);

        if (details['isMultiRecord'] == true) {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(details['tabName'])) {
                section = element.defaultLabel;
              }
            });
          });
        } else {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(fieldName)) {
                section = element.defaultLabel;
              }
            });
          });
        }

        if (tableSec !== section) {
          tableSec = section;
          return <>{section}</>;
        }
        tableSec = section;
        return <></>;
      },
    },
    {
      title: (<div style={{ marginBottom: 38 }} >Sub Section</div>),
      dataIndex: 'fieldSubName',
      width: '180px',
      render: (fieldSubName) => {
        const structure = _.get(model, 'frontEndDefinition.structure', '');
        const details = JSON.parse(selectedRow['details']);
        let subSection;

        if (details['isMultiRecord'] == true) {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(details['tabName'])) {
                subSection = el.defaultLabel;
              }
            });
          });
        } else {
          structure.forEach((element) => {
            element.content.forEach((el) => {
              if (el.content.includes(fieldSubName)) {
                subSection = el.defaultLabel;
              }
            });
          });
        }
        if (tableSubSec !== subSection) {
          tableSubSec = subSection;
          return <>{subSection}</>;
        }
        tableSubSec = subSection;
        return <></>;
      },
    },
    {
      title: (<div style={{ marginBottom: 38 }} >Field Name</div>),
      dataIndex: 'field',
      width: '150px',
    },
    {
      title: getLastUpdatedTime('currentVal'),
      dataIndex: 'currentVal',
    },
    {
      title: getLastUpdatedTime('newVal'),
      dataIndex: 'newVal',
    },
  ];
  return (
    <>
      <div className="card-container">
        <Tabs activeKey={currentTabKey} type="card" onChange={(value) => changeTabKey(value)}>
          {((props.pageType === 'myRequests' && hasAnyPermission([
            'my-info-request',
            'my-leave-request',
            'my-leaves',
            'my-attendance',
            'my-info-request',
            'my-resignation-request',
            'my-claim-request',
            'my-post-ot-request',
            'my-shift-change-request'
          ])) || (props.pageType === 'allRequests' && hasAnyPermission([
            'workflow-2',
            'workflow-3',
            'workflow-1',
            'workflow-4',
            'workflow-5',
            'workflow-6',
            'workflow-7',
            'workflow-8',
            'workflow-9',
            'workflow-10',
          ]))) &&
            <TabPane tab="All Requests" key="all">
              {
                hasPermissionForAllRequests ? (

                  <ProTable<TableListItem>
                    columns={columns}
                    request={(params, sorter) => requestAllData(params, sorter)}
                    actionRef={tableRefAll}
                    rowKey="id"
                    key={'allTable'}
                    columnsStateMap={columnsStateMap}
                    onColumnsStateChange={(map) => setColumnsStateMap(map)}
                    span={1}
                    // pagination={{
                    //   showSizeChanger: true,
                    // }}
                    pagination={{
                      pageSize: 10,
                      current: 1,
                      total: dataCount,
                      // hideOnSinglePage: true,
                    }}
                    dateFormatter="string"
                    search={{
                      filterType: 'light',
                      searchText: 'sdasdasd',
                    }}
                    showHeader={false}
                    options={{ fullScreen: false, reload: true, setting: false }}
                  />
                ) : (
                  <PermissionDeniedPage />
                )
              }

            </TabPane>
          }
          {
            isLoadAllRequest ? (
              <>
                {((props.pageType === 'myRequests' && (hasPermitted('my-leave-request') || hasPermitted('my-leaves'))) || (props.pageType === 'allRequests' && (hasPermitted('workflow-2')))) &&
                  <TabPane forceRender={true} tab="Leave Requests" key="2">
                    {
                      hasPermissionForLeaveRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          key={'leaveTable'}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefLeave}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-attendance')) || (props.pageType === 'allRequests' && hasPermitted('workflow-3'))) &&
                  <TabPane forceRender={true} tab="Time Change Requests" key="3">
                    {
                      hasPermissionForTimeChangeRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          key={'timeChangeTable'}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefTime}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          dateFormatter="string"
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-info-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-1'))) &&
                  <TabPane forceRender={true} tab="Profile Update Requests" key="1">
                    {
                      hasPermissionForProfileUpdateRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          key={'profileUpdateTable'}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefProfile}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          dateFormatter="string"
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {isShowShortLeaveTab && ((props.pageType === 'myRequests') || (props.pageType === 'allRequests' && hasPermitted('workflow-4'))) &&
                  <TabPane forceRender={true} tab="Short Leave Requests" key="4">

                    {
                      hasPermissionForShortLeaveRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefShortLeave}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-shift-change-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-5'))) &&
                  <TabPane forceRender={true} tab="Shift Change Requests" key="5">

                    {
                      hasPermissionForShiftChangeRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefShiftChange}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-leave-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-6'))) &&
                  <TabPane forceRender={true} tab="Cancel Leave Requests" key="6">

                    {
                      hasPermissionForCancelLeaveRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefCancelLeave}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-resignation-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-7'))) &&
                  <TabPane forceRender={true} tab="Resignation Requests" key="7">
                    {
                      hasPermissionForResignationRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefResignation}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {isShowShortLeaveTab && ((props.pageType === 'myRequests') || (props.pageType === 'allRequests' && hasPermitted('workflow-8'))) &&
                  <TabPane forceRender={true} tab="Cancel Short Leave Requests" key="8">
                    {
                      hasPermissionForCancelShortLeaveRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefCancelShortLeave}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-claim-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-9'))) &&
                  <TabPane forceRender={true} tab="Claim Requests" key="9">
                    {
                      hasPermissionForClaimRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefClaim}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
                {((props.pageType === 'myRequests' && hasPermitted('my-post-ot-request')) || (props.pageType === 'allRequests' && hasPermitted('workflow-10'))) &&
                  <TabPane forceRender={true} tab="Post OT Requests" key="10">

                    {
                      hasPermissionForPostOtRequests ? (
                        <ProTable<TableListItem>
                          columns={columns}
                          request={(params, sorter) => requestData(params, sorter)}
                          actionRef={tableRefPostOt}
                          rowKey="id"
                          columnsStateMap={columnsStateMap}
                          onColumnsStateChange={(map) => setColumnsStateMap(map)}
                          span={1}
                          // pagination={{
                          //   showSizeChanger: true,
                          // }}
                          pagination={{
                            pageSize: 10,
                            current: 1,
                            total: dataCount,
                            // hideOnSinglePage: true,
                          }}
                          dateFormatter="string"
                          search={{
                            filterType: 'light',
                            searchText: 'sdasdasd',
                          }}
                          showHeader={false}
                          options={{ fullScreen: false, reload: true, setting: false }}
                        />
                      ) : (
                        <PermissionDeniedPage />
                      )
                    }
                  </TabPane>
                }
              </>
            ) : (
              <></>
            )
          }
        </Tabs>
      </div>
      <ModalForm
        className='workflowDataViewModal'
        width={(selectedRow.contextId == 2) ? 880 : (selectedRow.contextId == 4 || selectedRow.contextId == 8) ? 700 : (selectedRow.contextId == 3) ? 650 : (selectedRow.contextId == 7) ? 900 : (selectedRow.contextId == 5) ? 800 : (selectedRow.contextId == 6) ? 800 : (selectedRow.contextId == 10) ? 1800 : 1000}
        title={selectedRow.contextId == 2 ?
          (<>
            {
              isShowCancelView ? (
                <>
                  <span style={{ cursor: 'pointer' }} onClick={() => {
                    setIsShowCancelView(false);
                  }}><ArrowLeftOutlined /></span>&nbsp; &nbsp;
                  {
                    intl.formatMessage({
                      id: 'leaveRequestTitle',
                      defaultMessage: 'Cancel Leave Request',
                    })
                  }
                </>
              ) : (
                <Row>
                  <Col>
                    <Space style={{ paddingTop: 4 }}>
                      {intl.formatMessage({
                        id: 'pages.Workflows.addNewWorkflow',
                        defaultMessage: 'Leave Request',
                      })}
                    </Space>
                  </Col>
                  <Col style={{ marginLeft: 20 }}>
                    <Space>
                      <Tag
                        style={{
                          borderRadius: 20,
                          fontSize: 17,
                          paddingRight: 20,
                          paddingLeft: 20,
                          paddingTop: 4,
                          paddingBottom: 4,
                          border: 0,
                        }}
                        color={selectedRow.stateTagColor}
                      >
                        {selectedRow.priorStateName}
                      </Tag>
                    </Space>
                  </Col>
                </Row>
              )
            }
          </>) : selectedRow.contextId == 4 ?
            (<>
              {
                isShowCancelView ? (
                  <>
                    <span style={{ cursor: 'pointer' }} onClick={() => {
                      setIsShowCancelView(false);
                    }}><ArrowLeftOutlined /></span>&nbsp; &nbsp;
                    {
                      intl.formatMessage({
                        id: 'leaveRequestTitle',
                        defaultMessage: 'Cancel Short Leave Request',
                      })
                    }
                  </>
                ) : (
                  <Row>
                    <Col>
                      <Space style={{ paddingTop: 4 }}>
                        {intl.formatMessage({
                          id: 'pages.Workflows.addNewWorkflow',
                          defaultMessage: 'Short Leave Request',
                        })}
                      </Space>
                    </Col>
                    <Col style={{ marginLeft: 20 }}>
                      <Space>
                        <Tag
                          style={{
                            borderRadius: 20,
                            fontSize: 17,
                            paddingRight: 20,
                            paddingLeft: 20,
                            paddingTop: 4,
                            paddingBottom: 4,
                            border: 0,
                          }}
                          color={selectedRow.stateTagColor}
                        >
                          {selectedRow.priorStateName}
                        </Tag>
                      </Space>
                    </Col>
                  </Row>
                )
              }
            </>) : (
              <Row>
                <Col>
                  <Space style={{ paddingTop: 4 }}>
                    {
                      intl.formatMessage({
                        id: selectedRow.contextName + 'RequestTitle',
                        defaultMessage: selectedRow.contextId == 6 ? 'Leave Cancellation Request' : (selectedRow.contextName) + ' ' + 'Request',
                      })
                    }
                  </Space>
                </Col>
                <Col style={{ marginLeft: 20 }}>
                  <Space>
                    <Tag
                      style={{
                        borderRadius: 20,
                        fontSize: 17,
                        paddingRight: 20,
                        paddingLeft: 20,
                        paddingTop: 4,
                        paddingBottom: 4,
                        border: 0,
                      }}
                      color={selectedRow.stateTagColor}
                    >
                      {selectedRow.priorStateName}
                    </Tag>
                  </Space>
                </Col>
              </Row>
            )

        }
        style={{ marginTop: 5 }}
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values: any) => { }}
        visible={addModalVisible}
        onVisibleChange={handleAddModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={{
          render: () => {
            return [
              <>
                {
                  selectedRow.contextId == 2 && isShowCancelView ? (
                    <>
                      <Button
                        style={{ borderRadius: 6 }}
                        key={'cancelBtn'}
                        onClick={() => {
                          setIsShowCancelView(false);
                        }}
                        type={'default'}
                      >
                        {'Cancel'}
                      </Button>

                      <Button
                        style={{ borderRadius: 6 }}
                        key={'cancelRequestBtn'}
                        onClick={() => {
                          let nochangeCount = 0;
                          leaveCancelDates.forEach((element) => {
                            if (!element.isCheckedFirstHalf && !element.isCheckedSecondHalf) {
                              nochangeCount++;
                            }
                          });

                          if (nochangeCount == leaveCancelDates.length) {
                            message.error('There is no any leave dates to cancel');
                            return;
                          }

                          let leaveRequestId = selectedRow.leaveRequestId;
                          let isInInitialState = leaveDataSet.isInInitialState;
                          let leaveCancelReason = (leaveDataSet.isInInitialState) ? null : approverCommentForm.getFieldValue('cancelReason')
                          cancelLeaveDates({
                            leaveRequestId,
                            leaveCancelDates,
                            isInInitialState,
                            leaveCancelReason
                          }).then((res) => {

                            switch (contextType) {
                              case 'all':
                                setHasPermissionForAllRequests(true);
                                tableRefAll.current?.reload();
                                break;
                              case '2':
                                setHasPermissionForLeaveRequests(true);
                                tableRefLeave.current?.reload();
                                break;
                              default:
                                break;
                            }

                            message.success(res.message);
                            handleAddModalVisible(false);
                          }).catch((error: APIResponse) => {
                            message.error(error.message);
                          });
                        }}
                        type={'primary'}
                      >
                        {
                          leaveDataSet.isInInitialState ? (
                            'Cancel Request'
                          ) : (
                            'Send Cancel Leave Request'
                          )
                        }
                      </Button>
                    </>
                  ) : selectedRow.contextId == 4 && isShowCancelView ? (
                    <>
                      <Button
                        style={{ borderRadius: 6 }}
                        key={'cancelBtn'}
                        onClick={() => {
                          setIsShowCancelView(false);
                        }}
                        type={'default'}
                      >
                        {'Cancel'}
                      </Button>

                      <Button
                        style={{ borderRadius: 6 }}
                        key={'cancelRequestBtn'}
                        onClick={() => {
                          // let nochangeCount = 0;
                          // leaveCancelDates.forEach((element) => {
                          //   if (!element.isCheckedFirstHalf && !element.isCheckedSecondHalf) {
                          //     nochangeCount ++;
                          //   }
                          // });

                          // if (nochangeCount == leaveCancelDates.length) {
                          //   message.error('There is no any leave dates to cancel');
                          //   return;
                          // }

                          let shortLeaveRequestId = selectedRow.shortLeaveRequestId;
                          let isInInitialState = leaveDataSet.isInInitialState;
                          let leaveCancelReason = (leaveDataSet.isInInitialState) ? null : approverCommentForm.getFieldValue('cancelReason')
                          cancelShortLeave({
                            shortLeaveRequestId,
                            isInInitialState,
                            leaveCancelReason
                          }).then((res) => {

                            switch (contextType) {
                              case 'all':
                                setHasPermissionForAllRequests(true);
                                tableRefAll.current?.reload();
                                break;
                              case '4':
                                setHasPermissionForLeaveRequests(true);
                                tableRefShortLeave.current?.reload();
                                break;
                              default:
                                break;
                            }

                            message.success(res.message);
                          }).catch((error: APIResponse) => {
                            message.error(error.message);
                          });
                          handleAddModalVisible(false);

                        }}
                        type={'primary'}
                      >
                        {
                          leaveDataSet.isInInitialState ? (
                            'Cancel Short Leave Request'
                          ) : (
                            'Send Cancel Short Leave Request'
                          )
                        }
                      </Button>
                    </>
                  ) : (

                    actions.map((element) => {
                      if (_.get(element, 'actionName', false)) {
                        return (

                          <Popconfirm title='Are you sure you want perform this action?' placement="top" onConfirm={() => {
                            updateWorkflowInstance(
                              element.id,
                              selectedRow.id,
                              selectedRow.workflowId,
                              selectedRow.contextId,
                            );
                          }} okText="Yes" cancelText="No">

                            <Button
                              style={{ borderRadius: 6 }}
                              key={element.id}
                              // onClick={}
                              type={element.isPrimary ? 'primary' : 'default'}
                            >
                              {element.label}
                            </Button>
                          </Popconfirm>
                        );
                      }
                    })
                  )
                }
              </>,
            ];
          },
        }}
      >
        {isChangesAreNew ? (
          <>
            {/* contextId = 1 mean its indicate this workflow related to profileUpdate workflow context  */}
            {(selectedRow.contextId == 1 && !isLoading) ? (
              <Alert
                message="Requested Changes are already approved and there is no any differneces between current and requested changes from this request"
                type="warning"
                style={{ marginBottom: '8px' }}
              />
            ) : <></>
            }
          </>
        ) : showThisIsFailureState ? (
          <>
            {(selectedRow.contextId == 1 && !isLoading) ? (
              <Alert
                message="Requested Changes are already Rejected so below displayed changes are no more valid"
                type="warning"
                style={{ marginBottom: '8px' }}
              />
            ) : <></>
            }
          </>
        ) : (
          <></>
        )}

        {
          (isLoading) ? (<><Spin style={{ marginLeft: '50%' }} size={'large'} /></>) :

            (
              (selectedRow.contextId == 1) ?
                (
                  // <Table className={'profileUpdateTable'} columns={modalColumns} dataSource={dataChanges} pagination={false} />
                  <Form form={approverCommentForm}>
                    <ProfileChangeRequest
                      selectedRow={selectedRow}
                      model={model}
                      updatedTimeOld={updatedTimeOld}
                      updatedTimeNew={updatedTimeNew}
                      dataChanges={dataChanges}
                      setApproverComment={setApproverComment}
                      actions={actions}
                      scope={relateScope}
                      employeeId={selectedRow.employeeId}
                      employeeFullName={employeeName}
                      workflowInstanceId={workflowInstanceId} >
                    </ProfileChangeRequest>
                  </Form>

                ) : selectedRow.contextId == 2 ? (
                  // contextId = 2 mean its indicate this workflow related to Apply Leave Workflow context
                  <Form form={approverCommentForm}>
                    <LeaveRequest setLeaveCancelDates={setLeaveCancelDates} isShowCancelView={isShowCancelView} setIsShowCancelView={setIsShowCancelView} setApproverComment={setApproverComment} actions={actions} fromLeaveRquestList={false} scope={relateScope} employeeId={selectedRow.employeeId} leaveData={leaveDataSet} setLeaveDataSet={setleaveDataSet} employeeFullName={employeeName}></LeaveRequest>
                  </Form>
                ) : selectedRow.contextId == 3 ? (
                  // contextId = 3 mean its indicate this workflow related to Attendence Time Change Workflow context
                  <Form form={approverCommentForm}>
                    <TimeChangeRequest setApproverComment={setApproverComment} actions={actions} scope={relateScope} employeeId={selectedRow.employeeId} employeeFullName={employeeName} timeChangeRequestData={timeChangeDataSet} ></TimeChangeRequest>
                  </Form>
                ) : selectedRow.contextId == 4 ?
                  <Form form={approverCommentForm}>
                    <ShortLeaveRequest setLeaveCancelDates={setLeaveCancelDates} isShowCancelView={isShowCancelView} setIsShowCancelView={setIsShowCancelView} setApproverComment={setApproverComment} actions={actions} fromLeaveRquestList={false} scope={relateScope} employeeId={selectedRow.employeeId} leaveData={leaveDataSet} setLeaveDataSet={setleaveDataSet} employeeFullName={employeeName}></ShortLeaveRequest>
                  </Form>
                  : selectedRow.contextId == 5 ? (
                    // contextId = 5 mean its indicate this workflow related to shift change Workflow context
                    <Form form={approverCommentForm}>
                      <ShiftChangeRequest setApproverComment={setApproverComment} actions={actions} scope={relateScope} employeeId={selectedRow.employeeId} employeeFullName={employeeName} shiftChangeRequestData={shiftChangeDataSet} ></ShiftChangeRequest>
                    </Form>
                  ) : selectedRow.contextId == 6 ? (
                    // contextId = 6 mean its indicate this workflow related to cancel leave Workflow context
                    <Form form={approverCommentForm}>
                      <CancelLeaveRequest setApproverComment={setApproverComment} actions={actions} scope={relateScope} employeeId={selectedRow.employeeId} employeeFullName={employeeName} cancelLeaveRequestData={cancelLeaveDataSet} ></CancelLeaveRequest>
                    </Form>
                  ) : selectedRow.contextId == 7 ? (
                    // contextId = 7 mean its indicate this workflow related to resignation Workflow context
                    <Form form={approverCommentForm}>
                      <ResignationRequest setResignationUpdatedEffectiveDate={setResignationUpdatedEffectiveDate} workflowInstanceId={workflowInstanceId} form={approverCommentForm} hireDate={hireDate} employeeNumber={employeeNumber} setApproverComment={setApproverComment} actions={actions} scope={relateScope} employeeId={selectedRow.employeeId} employeeFullName={employeeName} resignationRequestData={resignationDataSet} ></ResignationRequest>
                    </Form>
                  ) : selectedRow.contextId == 8 ? (
                    // contextId = 7 mean its indicate this workflow related to resignation Workflow context
                    <Form form={approverCommentForm}>
                      <CancelShortLeaveRequest setLeaveCancelDates={setLeaveCancelDates} isShowCancelView={isShowCancelView} setIsShowCancelView={setIsShowCancelView} setApproverComment={setApproverComment} actions={actions} fromLeaveRquestList={false} scope={relateScope} employeeId={selectedRow.employeeId} cancelShortLeaveData={cancelShortLeaveData} setLeaveDataSet={setCancelShortLeaveData} employeeFullName={employeeName}></CancelShortLeaveRequest>
                    </Form>
                  ) : selectedRow.contextId == 9 ? (
                    // contextId = 7 mean its indicate this workflow related to resignation Workflow context
                    <Form form={approverCommentForm}>
                      <ClaimRequest setClaimRequestData={setClaimRequestData} isShowCancelView={isShowCancelView} setIsShowCancelView={setIsShowCancelView} setApproverComment={setApproverComment} actions={actions} fromLeaveRquestList={false} scope={relateScope} employeeId={selectedRow.employeeId} claimRequestData={claimRequestData} setLeaveDataSet={setCancelShortLeaveData} employeeFullName={employeeName}></ClaimRequest>
                    </Form>
                  ) : selectedRow.contextId == 10 ? (
                    // contextId = 10 mean its indicate this workflow related to post ot request context
                    <Form form={approverCommentForm}>
                      <PostOtRequest isApproveActionAvailable={isApproveActionAvailable} intialData={intialData} requestState={requestState} attendanceSheetData={attendanceSheetData} setAttendanceSheetData={setAttendanceSheetData} setPostOtRequestData={setPostOtRequestData} isShowCancelView={isShowCancelView} setIsShowCancelView={setIsShowCancelView} setApproverComment={setApproverComment} actions={actions} fromLeaveRquestList={false} scope={relateScope} employeeId={selectedRow.employeeId} postOtRequestData={postOtRequestData} setLeaveDataSet={setCancelShortLeaveData} employeeFullName={employeeName}></PostOtRequest>
                    </Form>
                  ) : (
                    <></>
                  )
            )
        }

      </ModalForm>
    </>
  );
};

export default WorkflowInstance;
