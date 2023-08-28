import { getEmployeeSideCardDetails } from '@/services/employee';
import { CalendarOutlined, EnvironmentOutlined, MailOutlined, PhoneOutlined, UserOutlined } from '@ant-design/icons';
import { Card, Col, Divider, List, Popover, Row, Skeleton } from 'antd';
import Avatar from 'antd/lib/avatar/avatar';
import Text from 'antd/lib/typography/Text';
import Title from 'antd/lib/typography/Title';
import _ from 'lodash';
import React, { useEffect, useState } from 'react';
import { FormattedMessage, useAccess } from 'umi';
import UploadIcon from './UploadIcon';
import './index.css';

export type SideBusinessCardProps = {
    employeeId: any;
    loading: boolean;
    scope: string;
};

const SideBusinessCard: React.FC<SideBusinessCardProps> = (props) => {
    const { scope } = props;
    const access = useAccess();
    const { hasPermitted } = access;
    const [employeeDetails, setEmployeeDetails] = useState();
    const [uploadFinish, setUploadFinish] = useState(false);
    const [uploadIconDisplay, setUploadIconDisplay] = useState("none");
    useEffect(() => {
        getEmployeeSideCardDetails(props.employeeId)
            .then((response) => {
                setEmployeeDetails(response.data);
                if (hasPermitted('upload-profile-picture') && scope !== 'MANAGER') {
                    setUploadIconDisplay("");
                }
            });

    }, [props.employeeId, props.loading, uploadFinish]);

    return (
        <Card bordered={false} style={{ width: 260 }} >
            {employeeDetails
                ? <div>
                    <div style={{ textAlign: 'center' }}>
                        <Row justify="center">
                            <Col style={{ display: "flex", alignItems: "center", justifyContent: "center" }}>
                                {employeeDetails.profilePicture
                                    ? <Avatar
                                        className='user-avatar'
                                        size={100}
                                        src={employeeDetails.profilePicture}
                                    />
                                    : <Avatar
                                        className='user-avatar'
                                        size={100}
                                        icon={<UserOutlined />}
                                    />
                                }
                                <div className='avatar-upload-icon' style={{ position: "absolute", top: 0, left: 0, fontSize: 20, display: uploadIconDisplay }}>
                                    <UploadIcon onUploadFinish={setUploadFinish} id={props.employeeId} scope={scope} />
                                </div>
                            </Col>
                        </Row>
                        <Title level={3} style={{ marginTop: 16, marginBottom: 0 }}>{employeeDetails.employeeName}</Title>
                        <Text style={{ color: 'gray' }}>{employeeDetails.currentJobTitle}</Text>
                        <br />
                        <Text><FormattedMessage id="employee_number" defaultMessage="Employee Number" />: {employeeDetails.employeeNumber}</Text>
                    </div>
                    <Divider />
                    <List itemLayout="horizontal" >
                        <List.Item key='email'>
                            <List.Item.Meta
                                className="list-item-side-card"
                                avatar={<Avatar icon={<MailOutlined />} />}
                                title={<FormattedMessage id="email" defaultMessage="Email" />}
                                description={
                                    <Popover content={employeeDetails.workEmail} >
                                        <a href={`mailto:${employeeDetails.workEmail}`} target="_blank">{employeeDetails.workEmail}</a>
                                    </Popover>}
                            />
                        </List.Item>
                        <List.Item key='contactNo'>
                            <List.Item.Meta
                                avatar={<Avatar icon={<PhoneOutlined />} />}
                                title={<FormattedMessage id="contact_no" defaultMessage="Contact No" />}
                                description={<a href={`tel:${employeeDetails.mobilePhone}`}>{employeeDetails.mobilePhone}</a>}
                            />
                        </List.Item>
                        <List.Item key='location'>
                            <List.Item.Meta
                                avatar={<Avatar icon={<EnvironmentOutlined />} />}
                                title={<FormattedMessage id="location" defaultMessage="Location" />}
                                description={employeeDetails.currentLocation}
                            />
                        </List.Item>
                        {
                            employeeDetails.isRelatedToWorkPattern ? (
                                <List.Item key='workPattern'>
                                    <List.Item.Meta
                                        avatar={<Avatar icon={<CalendarOutlined />} />}
                                        title={<FormattedMessage id="work_pattern" defaultMessage="Work Pattern" />}
                                        description={employeeDetails.workPattern}
                                    />
                                </List.Item>
                            ) : (
                                <List.Item key='workShift'>
                                    <List.Item.Meta
                                        avatar={<Avatar icon={<CalendarOutlined />} />}
                                        title={<FormattedMessage id="work_shift" defaultMessage="Shift" />}
                                        description={employeeDetails.workShift}
                                    />
                                </List.Item>
                            )
                        }
                       
                        <List.Item key='reportingPerson'>
                            <List.Item.Meta
                                avatar={<Avatar icon={<UserOutlined />} />}
                                title={<FormattedMessage id="reporting_erson" defaultMessage="Reporting Person" />}
                                description={
                                    <div>
                                        <Text style={{ color: 'GrayText' }}>{employeeDetails.reportingPerson}</Text>
                                        <br />
                                        <Text style={{ color: 'lightgrey' }}>{employeeDetails.reportingPersonJobTitle}</Text>
                                    </div>
                                }
                            />
                        </List.Item>
                    </List>
                </div>
                : <div style={{ textAlign: 'center' }}>
                    <Skeleton.Avatar active size={100} />
                    <br />
                    <Skeleton.Button active size='small' style={{ width: 200, marginTop: 20 }} />
                    <Divider />
                    <Skeleton avatar active paragraph={{ rows: 1 }} />
                    <Skeleton avatar active paragraph={{ rows: 1 }} />
                    <Skeleton avatar active paragraph={{ rows: 1 }} />
                    <Skeleton avatar active paragraph={{ rows: 1 }} />
                    <Skeleton avatar active paragraph={{ rows: 1 }} />
                </div>}
        </Card>
    );
};

export default SideBusinessCard;
