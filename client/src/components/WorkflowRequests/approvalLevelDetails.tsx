import React, { useEffect, useState } from 'react';
import { Col, Row, List, Tag, Input, Form, Spin } from 'antd';
import { FormattedMessage } from 'react-intl';

import { DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import request, { APIResponse } from '@/utils/request';

type ApprovalLevelProps = {
  scope: any;
  actions: any;
  setApproverComment: any;
  isLoading?: any;
  workflowInstanceId: any;
  isViewOnly ?: boolean
};

const ApprovalLevelDetails: React.FC<ApprovalLevelProps> = (props) => {
  const { TextArea } = Input;
  const [approvalLevelList, setApprovalLevelList] = useState<any>([]);
  const [readOnlyView, setReadOnlyView] = useState<any>(false);

  useEffect(() => {
    setApprovalLevelList({});

    if (props.isViewOnly == undefined) {
      setReadOnlyView(false);
    } else {
      setReadOnlyView(props.isViewOnly);
    }
  }, []);

  useEffect(() => {
    if (props.workflowInstanceId) {
      getLevelWiseStatesOfWorkflow();
    }
  }, [props.workflowInstanceId]);

  const getLevelWiseStatesOfWorkflow = async () => {
    try {
      setApprovalLevelList([]);
      let path: string;
      path = `/api/get-approval-level-wise-state/` + props.workflowInstanceId;
      const result = await request(path);
      if (result['data'] !== null) {
        let levels = [];

        Object.keys(result['data']).some((key) => {
          result['data'][key]['levelName'] = key + ' Approval';
          result['data'][key]['approvers'] =
            result['data'][key]['approvers'].length > 0
              ? String(result['data'][key]['approvers'])
              : '--';
          levels.push(result['data'][key]);
        });

        setApprovalLevelList(levels);
      }
    } catch (error) {
      console.log(error);
    }
  };

  return (
    <>
      {props.isLoading ? (
        <Spin size="large" spinning={true}></Spin>
      ) : (
        <Row style={{ paddingRight: 20, marginBottom: 20, width: '100%' }}>
          {approvalLevelList.length == 0 ? (
            <></>
          ) : (
            <List
              itemLayout="horizontal"
              dataSource={approvalLevelList}
              style={
                approvalLevelList.length > 3
                  ? { overflowY: 'scroll', height: 150, width: '100%' }
                  : { width: '100%' }
              }
              renderItem={(item, index) =>
                props.scope != 'EMPLOYEE' && !readOnlyView ? (
                  props.actions.length > 0 ? (
                    !item.isAfterCurrentLevel ? (
                      <List.Item key={item.id}>
                        <List.Item.Meta
                          // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                          title={
                            <Row>
                              <p
                                key="commentedUserName"
                                style={{
                                  fontSize: 18,
                                  fontWeight: 500,
                                  marginBottom: 0,
                                  marginRight: 10,
                                }}
                              >
                                {item.levelName}
                              </p>
                              <p
                                key="commentDateTime"
                                style={{
                                  fontSize: 16,
                                  marginBottom: 0,
                                  fontWeight: 400,
                                  marginRight: 10,
                                  paddingTop: 2,
                                  color: '#626D6C',
                                }}
                              >
                                {'(' + item.approvers + ')'}
                              </p>
                              <Tag
                                style={{
                                  borderRadius: 20,
                                  fontSize: 14,
                                  paddingRight: 20,
                                  paddingLeft: 20,
                                  paddingTop: 4,
                                  paddingBottom: 2,
                                  border: 0,
                                }}
                                color={item.stateTagColor}
                              >
                                {item.state}
                              </Tag>
                            </Row>
                          }
                          // description={item.comment ? item.comment : 'No Comments'}
                          description={
                            item.canAddComment &&
                            props.actions.length > 0 &&
                            !item.isLevelPerform ? (
                              <Row>
                                <Col span={20} style={{ marginTop: 15 }}>
                                  <Form.Item
                                    name="approverComment"
                                    rules={[
                                      { max: 250, message: 'Maximum length is 250 characters.' },
                                    ]}
                                  >
                                    <Input.TextArea
                                      maxLength={250}
                                      rows={4}
                                      style={{ borderRadius: 6 }}
                                      onChange={(val) => {
                                        props.setApproverComment(val.target.value);
                                      }}
                                    />
                                  </Form.Item>
                                </Col>
                              </Row>
                            ) : item.comment ? (
                              item.comment
                            ) : (
                              'No Comments'
                            )
                          }
                        />
                      </List.Item>
                    ) : (
                      <></>
                    )
                  ) : !item.isAfterSucessActionPerformLevel ? (
                    <List.Item key={item.id}>
                      <List.Item.Meta
                        // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                        title={
                          <Row>
                            <p
                              key="commentedUserName"
                              style={{
                                fontSize: 18,
                                fontWeight: 500,
                                marginBottom: 0,
                                marginRight: 10,
                              }}
                            >
                              {item.levelName}
                            </p>
                            <p
                              key="commentDateTime"
                              style={{
                                fontSize: 16,
                                marginBottom: 0,
                                fontWeight: 400,
                                marginRight: 10,
                                paddingTop: 2,
                                color: '#626D6C',
                              }}
                            >
                              {'(' + item.approvers + ')'}
                            </p>
                            <Tag
                              style={{
                                borderRadius: 20,
                                fontSize: 14,
                                paddingRight: 20,
                                paddingLeft: 20,
                                paddingTop: 4,
                                paddingBottom: 2,
                                border: 0,
                              }}
                              color={item.stateTagColor}
                            >
                              {item.state}
                            </Tag>
                          </Row>
                        }
                        description={item.comment ? item.comment : 'No Comments'}
                      />
                    </List.Item>
                  ) : item.isLevelPerform ? (
                    <List.Item key={item.id}>
                      <List.Item.Meta
                        // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                        title={
                          <Row>
                            <p
                              key="commentedUserName"
                              style={{
                                fontSize: 18,
                                fontWeight: 500,
                                marginBottom: 0,
                                marginRight: 10,
                              }}
                            >
                              {item.levelName}
                            </p>
                            <p
                              key="commentDateTime"
                              style={{
                                fontSize: 16,
                                marginBottom: 0,
                                fontWeight: 400,
                                marginRight: 10,
                                paddingTop: 2,
                                color: '#626D6C',
                              }}
                            >
                              {'(' + item.approvers + ')'}
                            </p>
                            <Tag
                              style={{
                                borderRadius: 20,
                                fontSize: 14,
                                paddingRight: 20,
                                paddingLeft: 20,
                                paddingTop: 4,
                                paddingBottom: 2,
                                border: 0,
                              }}
                              color={item.stateTagColor}
                            >
                              {item.state}
                            </Tag>
                          </Row>
                        }
                        description={item.comment ? item.comment : 'No Comments'}
                      />
                    </List.Item>
                  ) : (
                    <></>
                  )
                ) : !item.isAfterSucessActionPerformLevel ? (
                  <List.Item key={item.id}>
                    <List.Item.Meta
                      // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                      title={
                        <Row style={{ width: '100%' }}>
                          <p
                            key="commentedUserName"
                            style={{
                              fontSize: 18,
                              fontWeight: 500,
                              marginBottom: 0,
                              marginRight: 10,
                            }}
                          >
                            {item.levelName}
                          </p>
                          <p
                            key="commentDateTime"
                            style={{
                              fontSize: 16,
                              marginBottom: 0,
                              fontWeight: 400,
                              marginRight: 10,
                              paddingTop: 2,
                              color: '#626D6C',
                            }}
                          >
                            {'(' + item.approvers + ')'}
                          </p>
                          <Tag
                            style={{
                              borderRadius: 20,
                              fontSize: 14,
                              paddingRight: 20,
                              paddingLeft: 20,
                              paddingTop: 4,
                              paddingBottom: 2,
                              border: 0,
                            }}
                            color={item.stateTagColor}
                          >
                            {item.state}
                          </Tag>
                        </Row>
                      }
                      description={item.comment ? item.comment : 'No Comments'}
                    />
                  </List.Item>
                ) : item.isLevelPerform ? (
                  <List.Item key={item.id}>
                    <List.Item.Meta
                      // avatar={<Avatar size={38} icon={<CommentOutlined />} />}
                      title={
                        <Row style={{ width: '100%' }}>
                          <p
                            key="commentedUserName"
                            style={{
                              fontSize: 18,
                              fontWeight: 500,
                              marginBottom: 0,
                              marginRight: 10,
                            }}
                          >
                            {item.levelName}
                          </p>
                          <p
                            key="commentDateTime"
                            style={{
                              fontSize: 16,
                              marginBottom: 0,
                              fontWeight: 400,
                              marginRight: 10,
                              paddingTop: 2,
                              color: '#626D6C',
                            }}
                          >
                            {'(' + item.approvers + ')'}
                          </p>
                          <Tag
                            style={{
                              borderRadius: 20,
                              fontSize: 14,
                              paddingRight: 20,
                              paddingLeft: 20,
                              paddingTop: 4,
                              paddingBottom: 2,
                              border: 0,
                            }}
                            color={item.stateTagColor}
                          >
                            {item.state}
                          </Tag>
                        </Row>
                      }
                      description={item.comment ? item.comment : 'No Comments'}
                    />
                  </List.Item>
                ) : (
                  <></>
                )
              }
            />
          )}
        </Row>
      )}
    </>
  );
};

export default ApprovalLevelDetails;
