import React, { useState, useEffect, useRef } from 'react';
import ProTable, { ProColumns, ActionType } from '@ant-design/pro-table';
import { history, useIntl, useAccess, Access } from 'umi';
import { PageContainer } from '@ant-design/pro-layout';
import { Col, Row, Form, message as Message, Button, Select, Tag, DatePicker } from 'antd';
import { getAllScheduledJobLogHistory } from '@/services/scheduledJobLogs';
import PermissionDeniedPage from '../403';
import moment from 'moment';

const ScheduledJobsLog: React.FC = () => {
    const intl = useIntl();
    const [modalForm] = Form.useForm();
    const { Option } = Select;
    const access = useAccess();
    const { hasPermitted } = access;
    const actionRef = useRef<ActionType>();
    const [records, setRecords] = useState([]);

    const searchForm = async (params:any) => {
        console.log(params);
        let paramData = {
            'type' : params.type,
            'status' : (params.status) ? JSON.stringify(params.status) : JSON.stringify([]),
            'date': moment(params.date, "DD-MM-YYYY").isValid() ? moment(params.date, "DD-MM-YYYY").format('YYYY-MM-DD'): null
        }

       const {data} = await getAllScheduledJobLogHistory(paramData)
       setRecords(data);
    }
    const columns: ProColumns<any>[] = [
        {
            title: 'Executed At',
            width: 180,
            dataIndex: 'createdAt',
            render: (_, record) => { 
                return (
                   <div style={{
                      textOverflow: 'ellipsis',
                      whiteSpace: 'nowrap'
                    }}>
                        {moment(record.createdAt).format("DD-MM-YYYY HH:mm:ss")}
                    </div>
                )

            }
        },
        {
            title: 'Status',
            width: 180,
            dataIndex: 'hasFailed',
            render: (_, record) => { 
                let status ='';
                let color ='';
                if (record.hasFailed == 0) {
                    status = 'Success';
                    color = 'success';
                }
                if (record.hasFailed == 1) {
                    status = 'Failed';
                    color='error';
                }
                return (
                    <Tag color={color} style={{width:60 }}>{status}</Tag>
         
                )
            }
        },
        {
            title: 'Exception',
            width: 180,
            dataIndex: 'exception',
        }
    ];

    return (
        <Access accessible={hasPermitted('scheduled-jobs-log')} fallback={<PermissionDeniedPage />}>
            <PageContainer>
                <Row style={{ marginBottom: '32px' }}>
                    <div style={{ background: '#FFFFFF', padding: '32px', width: '100%', borderRadius: 10 }}>
                        <Form
                            form={modalForm}
                            onFinish={searchForm}
                            autoComplete="off"
                            layout="vertical"
                        >
                            <Row>

                                <Col span={4} 
                                   style={{
                                      height: 35,
                                      width: 250,
                                      paddingLeft: 10,
                                      marginBottom:20
                                    }}
                                >
                                    <Form.Item
                                        name="type"
                                        label={intl.formatMessage({
                                            id: 'days',
                                            defaultMessage: 'Select Type',
                                        })}
                                        rules={[
                                            {
                                                required: true,
                                                message: intl.formatMessage({
                                                    id: 'type.required',
                                                    defaultMessage: 'Required.',
                                                })

                                            },
                                        ]}
                                    >
                                        <Select
                                            style={{
                                                width: '100%'
                                            }}
                                            placeholder={intl.formatMessage({
                                                id: 'selectType',
                                                defaultMessage: 'Select Type',
                                            })}
                                        >
                                            <Option value="attendanceLogs">Attendance Logs</Option>
                                            <Option value="leaveAccrualLogs">Leave Acrrual Logs</Option>
                                        </Select>

                                    </Form.Item>
                                </Col>
                                <Col span={4} 
                                   style={{
                                      height: 35,
                                      width: 250,
                                      paddingLeft: 10,
                                      marginBottom:20
                                    }}
                                >
                                    <Form.Item
                                        name="date"
                                        label={intl.formatMessage({
                                            id: 'days',
                                            defaultMessage: 'Date',
                                        })}
                                    >
                                        <DatePicker
                                            name="date"
                                            style={{width: '100%'}}
                                            format={'DD-MM-YYYY'}
                                            onChange={(value) => {
                                            }}


                                        />

                                    </Form.Item>
                                </Col>
                                <Col span={4} 
                                   style={{
                                      height: 35,
                                      width: 250,
                                      paddingLeft: 10,
                                      marginBottom:20
                                    }}
                                >
                                    <Form.Item
                                        name="status"
                                        label={intl.formatMessage({
                                            id: 'status',
                                            defaultMessage: 'Status',
                                        })}
                                    >
                                        <Select
                                            style={{
                                                width: '100%'
                                            }}
                                            mode="multiple"
                                            placeholder={intl.formatMessage({
                                                id: 'selectType',
                                                defaultMessage: 'Select Type',
                                            })}
                                        >
                                            <Option value={0}>{intl.formatMessage({
                                                id: 'success',
                                                defaultMessage: 'Success',
                                            })}</Option>
                                            <Option value={1}>{intl.formatMessage({
                                                id: 'failed',
                                                defaultMessage: 'Failed',
                                            })}</Option>
                                        </Select>

                                    </Form.Item>
                                </Col>
                                
                                <Col span={4} 
                                   style={{
                                       height: 35,
                                       textAlign: 'left',
                                       marginLeft: 34,
                                       marginTop:26
                                    }
                                }>
                                  
                                   {/* // <span style={{ verticalAlign: 'text-top', paddingLeft: '8px' }}> */}
                                        <Button key="submit" htmlType="submit" style={{ verticalAlign: 'text-top', background: '#7DC014', border: '1px solid #86C129', boxSizing: 'border-box', borderRadius: '6px', color: '#FFFFFF' }}>
                                            Search
                                        </Button>
                                    {/* </span> */}
                                </Col>

                            </Row>
                        </Form>
                    </div>
                </Row>
                <Row>
                    <ProTable
                        actionRef={actionRef}
                        rowKey="id"
                        search={false}
                        columns={columns}
                        style={{ width: '100%' }}
                        pagination={{ pageSize: 10, defaultPageSize: 10 }}
                        dataSource={records}
                    />

                </Row>
            </PageContainer>
        </Access>
    );
};

export default ScheduledJobsLog;
