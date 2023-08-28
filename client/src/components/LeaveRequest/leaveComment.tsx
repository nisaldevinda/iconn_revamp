import React, { useEffect, useRef, useState } from 'react';
import { SearchOutlined } from '@ant-design/icons';
import TextArea from 'antd/lib/input/TextArea';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { ProFormDateRangePicker, ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';

// import { approveTimeChange, approveTimeChangeAdmin, requestTimeChange, accessibleWorkflowActions, updateInstance } from '@/services/attendance';
import request, { APIResponse } from '@/utils/request';
import { getModel, Models } from '@/services/model';
import _, { trim, values } from 'lodash';
import { CommentOutlined, EyeOutlined } from '@ant-design/icons';
import styles from './index.less';
import LeaveRequest from '../WorkflowRequests/leaveRequest';
import { ReactComponent as Edit } from '../../assets/attendance/Edit.svg';
import { ReactComponent as Comment } from '../../assets/attendance/Comment.svg';
import { UseFetchDataAction } from '@ant-design/pro-table/lib/typing';
import { ProCoreActionType } from '@ant-design/pro-utils';
import {
  Button,
  Tag,
  Space,
  Image,
  Row,
  Col,
  Tooltip,
  Spin,
  Modal,
  DatePicker,
  TimePicker,
  Form,
  message,
  List,
  Avatar,
  Switch,
  Typography,
  Popconfirm,
  Statistic,
  Select
} from 'antd';

import {
  getEmployeeRequestAdminData,
  accessibleWorkflowActions,
  updateInstance,
  getEmployeeRequestEmployeeData,
  getEmployeeRequestManagerData,
  getEmployeeData,
  addComment,
  cancelLeave,
} from '@/services/leave';
import { getEmployeeList } from '@/services/dropdown';

const { Text } = Typography;

moment.locale('en');

export type LeaveCommentProps = {
  leaveId: number,
  leaveData: any,
  refreshLeaveList: Function

};



const LeaveComment: React.FC<LeaveCommentProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const [commentContent, setCommentContent] = useState<string | null>(null);
  const [selectedLeaveId, setSelectedLeaveId] = useState<any>(null);
  const actionRef = useRef<ActionType>();
  const [commentList, setCommentList] = useState<any>([]);
  

  const [isCommentModalVisible, setIsCommentModalVisible] = useState(false);
  const [loadingModel, setLoadingModel] = useState(false);
  const [loadingCommentListModel, setLoadingCommentListModel] = useState(false);
  const key = 'saving';
  const [isCommentEnable, setIsCommentEnable] = useState<boolean>(true);

  const [form] = Form.useForm();
  const { RangePicker } = DatePicker;
  const { Option } = Select;

  
  const getRelatedComments = async (id: any) => {
    try {
      setLoadingCommentListModel(true);
      const actions: any = [];
      let path = `/api/leaveRequest/getRelatedComments/` + id;
      const res = await request(path);
      setCommentList(res.data);
      setLoadingCommentListModel(false);
    } catch (err) {
      console.log(err);
    }
  };
  

  const handleCommentModalCancel = () => {
    setIsCommentModalVisible(false);
  };

  const handleCommentSave = async () => {
    try {
      await form.validateFields();
      let trimedComment = commentContent?.trim();

      if (trimedComment != null && trimedComment.length == 0) {
         form.setFields([{
           name: 'comment',
           errors: ['Required'] 
         }]);
         return;
      }
      const params = {
        commentContent: commentContent,
        dateTime: moment().format('YYYY-MM-DD, H:mm:ss'),
      };

      addComment(params, selectedLeaveId)
        .then((response: any) => {
          setCommentContent(null);
          setIsCommentModalVisible(false);
          props.refreshLeaveList();
        //   actionRef.current?.reload();
        })
        .catch((error: APIResponse) => {
          message.error({
            content: intl.formatMessage({
              id: 'saveCommentErr',
              defaultMessage: 'Failed to save comment.',
            }),
            key,
          });
        });
    } catch (err) {
      console.log(err);
    }
  };

  

  return (
    <>

        {props.leaveData.commentCount > 0 ? (
        <>
            <Statistic
            valueStyle={{ fontSize: 14, color: '#86C129' }}
            value={props.leaveData.commentCount}
            prefix={
                <span style={{ position: 'relative', top: 3, cursor: 'pointer' }}>
                <Comment
                    height={15}
                    width={15}
                    onClick={() => {
                    getRelatedComments(props.leaveId);
                    setSelectedLeaveId(props.leaveId);
                    setCommentList([]);
                    form.setFieldsValue({ comment: '' });
                    setIsCommentModalVisible(true);
                    }}
                />
                </span>
            }
            />
        </>
        ) : (
        <span style={{ position: 'relative', top: 3, cursor: 'pointer' }}>
            <Comment
            height={15}
            width={15}
            onClick={() => {
                getRelatedComments(props.leaveId);
                setSelectedLeaveId(props.leaveId);
                setCommentList([]);
                form.setFieldsValue({ comment: '' });
                setIsCommentModalVisible(true);
            }}
            />
        </span>
        )}
      
        <Modal
          title={<FormattedMessage id="Leave_Comments" defaultMessage="Leave Request Comments" />}
          visible={isCommentModalVisible}
          onCancel={handleCommentModalCancel}
          centered
          width={700}
          destroyOnClose={true}
          footer={[
            <>
              <Button key="commentBack" onClick={handleCommentModalCancel}>
                Cancel
              </Button>
              <Button key="saveCommet" onClick={handleCommentSave} type="primary">
                Add
              </Button>
            </>,
          ]}
        >
          {loadingModel ? (
            <Spin size="large" spinning={loadingModel} />
          ) : (
            <>
              <Form form={form} style={{paddingLeft: 20, paddingRight: 20}} layout="vertical" size="large">
                {/* <Row><Col><FormattedMessage id="yourComment" defaultMessage="Your Comment" /></Col></Row> */}
                <Row style={{ marginBottom: 10 }}>
                  <Form.Item
                    name="comment"
                    style={{ width: '100%' }}
                    label={<FormattedMessage id="yourComment" defaultMessage="Your Comment" />}
                    rules={[
                      {
                        required: true,
                        message: 'Required',
                      },
                      { max: 250, message: 'Maximum length is 250 characters.' }
                    ]}
                  >
                    <TextArea
                      rows={4}
                      style={{ width: '100%' }}
                      onChange={(event) => {
                        if (event.target.value == '') {
                          setCommentContent(null);
                          setIsCommentEnable(true);
                        } else {
                          setCommentContent(event.target.value);
                          setIsCommentEnable(false);
                        }
                      }}
                      value={commentContent}
                    />
                  </Form.Item>
                </Row>
              </Form>
              {loadingCommentListModel ? (
                <Spin
                  size="large"
                  style={{ marginLeft: '50%' }}
                  spinning={loadingCommentListModel}
                />
              ) : (
                <Row style={{paddingLeft: 20,paddingRight: 20, marginBottom: 20, width: '100%' }}>
                  {commentList.length == 0 ? (
                    <></>
                  ) : (
                    <List
                      itemLayout="horizontal"
                      dataSource={commentList}
                      style={
                        commentList.length > 3
                          ? { overflowY: 'scroll', height: 150, width: '100%' }
                          : { width: '100%' }
                      }
                      renderItem={(item) => (
                        <List.Item key={item.id}>
                          <List.Item.Meta
                            avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                            title={
                              <Row>
                                <p
                                  key="commentedUserName"
                                  style={{ fontSize: 16, marginBottom: 0, marginRight: 10 }}
                                >
                                  {item.commentedUser}
                                </p>
                                <p
                                  key="commentDateTime"
                                  style={{
                                    fontSize: 14,
                                    marginBottom: 0,
                                    paddingTop: 2,
                                    color: 'grey',
                                  }}
                                >
                                  {moment(item.dateTime).format('Do MMMM YYYY, H:mm:ss A')}
                                </p>
                              </Row>
                            }
                            description={item.commentContent}
                          />
                        </List.Item>
                      )}
                    />
                  )}
                </Row>
              )}
            </>
          )}
        </Modal>
    </>
  );
};

export default LeaveComment;
