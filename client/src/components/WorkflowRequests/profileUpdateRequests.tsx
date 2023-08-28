import React, { useEffect, useState } from 'react';
import {
  Image,
  Col,
  Row,
  List,
  DatePicker,
  TimePicker,
  Space,
  Input,
  Avatar,
  message,
  Table,
} from 'antd';
import { FormattedMessage } from 'react-intl';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from '@/services/model';
import { getEmployeeCurrentDetails } from '@/services/employee';

import { DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import ApprovalLevelDetails from './approvalLevelDetails';
import styles from './index.less';

type ProfileUpdateRequestProps = {
  dataChanges: any;
  employeeFullName: any;
  scope: any;
  employeeId: any;
  actions?: any;
  setApproverComment: any;
  workflowInstanceId: any;
  updatedTimeOld: any;
  updatedTimeNew: any;
  model: any;
  selectedRow: any;
};

const ProfileUpdateRequest: React.FC<ProfileUpdateRequestProps> = (props) => {
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [shiftChangeRequestId, setShiftChangeRequestId] = useState<string | null>(null);
  const [jobTitle, setJobTitle] = useState<string | null>(null);

  useEffect(() => {
    getEmployeeProfileImage();
    getEmployeeRelateDataSet();
  }, []);

  const getEmployeeProfileImage = async () => {
    try {
      const actions: any = [];
      const response = await getModel('employee');
      let path: string;

      if (!_.isEmpty(response.data)) {
        path =
          `/api${response.data.modelDataDefinition.path}/` + props.employeeId + `/profilePicture`;
        const result = await request(path);
        if (result['data'] !== null) {
          setimageUrl(result['data']['data']);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  const getLastUpdatedTime = (field) => {
    if (field === 'currentVal') {
      return (
        <>
          <div>Current Data</div>
          <div className={styles.secondryInfo}>
            Last updated: {props.updatedTimeOld ? props.updatedTimeOld : '  --'}
          </div>
        </>
      );
    }
    return (
      <>
        <div>New Data</div>
        <div className={styles.secondryInfo}>
          Submitted on: {props.updatedTimeNew ? props.updatedTimeNew : '--'}
        </div>
      </>
    );
  };

  let tableSec: string = '';
  let tableSubSec: string = '';
  const modalColumns = [
    {
      title: <div style={{ marginBottom: 38 }}>Section</div>,
      dataIndex: 'fieldName',
      width: '100px',
      render: (fieldName) => {
        const structure = _.get(props.model, 'frontEndDefinition.structure', '');
        let section;
        const details = JSON.parse(props.selectedRow['details']);

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
      title: <div style={{ marginBottom: 38 }}>Sub Section</div>,
      dataIndex: 'fieldSubName',
      width: '180px',
      render: (fieldSubName) => {
        const structure = _.get(props.model, 'frontEndDefinition.structure', '');
        const details = JSON.parse(props.selectedRow['details']);
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
      title: <div style={{ marginBottom: 38 }}>Field Name</div>,
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

  const getEmployeeRelateDataSet = () => {
    try {
      getEmployeeCurrentDetails(props.employeeId).then((res) => {
        if (res.data) {
          let jobTitle = res.data.jobTitle != null ? res.data.jobTitle : '-';
          setJobTitle(jobTitle);
        }
      });
    } catch (error) {
      if (_.isEmpty(error)) {
        const hide = message.loading('Error');
        message.error('Validation errors');
        hide();
      }
    }
  };

  return (
    <>
      <Row style={{ width: '100%' }}>
        {/* {props.scope != 'EMPLOYEE' ? (
          <>
            <Row style={{ marginBottom: 30, marginTop: 30, width: '100%' }}>
              <Col span={16} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>
                    {imageUrl ? (
                      <Avatar style={{ fontSize: 22, border: 1 }} src={imageUrl} size={55} />
                    ) : (
                      <Avatar style={{ backgroundColor: 'blue', fontSize: 18 }} size={55}>
                        {props.employeeFullName != null
                          ? props.employeeFullName
                              .split(' ')
                              .map((x) => x[0])
                              .join('')
                          : ''}
                      </Avatar>
                    )}
                  </Col>
                  <Col style={{ paddingLeft: 10 }}>
                    <Row style={{ fontWeight: 500, fontSize: 20, color: '#394241' }}>
                      {props.employeeFullName}
                    </Row>
                    <Row style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
                      {jobTitle}
                    </Row>
                  </Col>
                </Row>
              </Col>
            </Row>
          </>
        ) : (
          <></>
        )} */}
        <Row style={{ width: '100%', marginBottom: 20 }}>
          <Table
            className={'profileUpdateTable'}
            columns={modalColumns}
            dataSource={props.dataChanges}
            pagination={false}
          />
        </Row>
        {props.workflowInstanceId ? (
          <ApprovalLevelDetails
            workflowInstanceId={props.workflowInstanceId}
            setApproverComment={props.setApproverComment}
            actions={props.actions}
            scope={props.scope}
          ></ApprovalLevelDetails>
        ) : (
          <></>
        )}
      </Row>
    </>
  );
};

export default ProfileUpdateRequest;
