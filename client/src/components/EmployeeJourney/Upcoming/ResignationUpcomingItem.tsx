import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { FormattedMessage } from 'react-intl';
import { useIntl } from 'umi';
import { Button, Col, Row, Typography, Spin, Space, message } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import Modal from 'antd/lib/modal/Modal';
import moment from 'moment';
import { getAttachment, reupdateUpcomingEmployeeJourneyMilestone, rollbackUpcomingEmployeeJourneyMilestone } from '@/services/employeeJourney';
import { ModalForm, ProFormDatePicker, ProFormSelect, ProFormTextArea, ProFormUploadButton } from '@ant-design/pro-form';
import { getEmployee } from '@/services/employee';

interface ResignationUpcomingItemProps {
    data: any,
    record: any,
    employee: any,
    setEmployee: (values: any) => void
}

const ResignationUpcomingItem: React.FC<ResignationUpcomingItemProps> = (props) => {
    const intl = useIntl();

    const [attachment, setAttachment] = useState();
    const [isAttachmentModalVisible, setIsAttachmentModalVisible] = useState(false);
    const [isReupdateModalVisible, setIsReupdateModalVisible] = useState(false);
    const [isRollbackModalVisible, setIsRollbackModalVisible] = useState(false);
    const [fileList, setFileList] = useState([]);

    useEffect(() => {
        fetchAttachment();
    }, [])

    const fetchAttachment = async () => {
        if (props.record.attachmentId) {
            const _attachment = await getAttachment(props.employee.id, props.record.attachmentId);
            setAttachment(_attachment?.data);

            const _fileList = [{
                uid: '1',
                name: _attachment?.data?.name,
                status: "done",

            }];
            setFileList(_fileList);
        }
    }

    const tbody = <>
        <tbody>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.employee_no"
                        defaultMessage="Employee No"
                    />
                </td>
                <td className='property-value'>
                    {props.employee?.employeeNumber}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.job_category"
                        defaultMessage="Job Category"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.jobCategories?.find(option => option.value == props.record?.jobCategoryId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.job_title"
                        defaultMessage="Job Title"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.jobTitles?.find(option => option.value == props.record?.jobTitleId)?.label}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.location"
                        defaultMessage="Location"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.locations?.find(option => option.value == props.record?.locationId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.pay_grade"
                        defaultMessage="Pay Grade"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.payGrades?.find(option => option.value == props.record?.payGradeId)?.label}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.reporting_person"
                        defaultMessage="Reporting Person"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.employees?.find(option => option.value == props.record?.reportsToEmployeeId)?.label}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.functional_reporting_person"
                        defaultMessage="Functional Reporting Person"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.employees?.find(option => option.value == props.record?.functionalReportsToEmployeeId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.hire_date"
                        defaultMessage="Hire Date"
                    />
                </td>
                <td className='property-value'>
                    {moment(props.employee?.hireDate, 'YYYY-MM-DD').format("DD-MM-YYYY")}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.resignation_handed_over"
                        defaultMessage="Resignation Handed over"
                    />
                </td>
                <td className='property-value'>
                    {moment(props.record?.resignationHandoverDate, 'YYYY-MM-DD').format("DD-MM-YYYY")}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.resignation_effective_date"
                        defaultMessage="Resignation Effective Date"
                    />
                </td>
                <td className='property-value'>
                    {moment(props.record?.effectiveDate, 'YYYY-MM-DD').format("DD-MM-YYYY")}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.last_working_date"
                        defaultMessage="Last Working Date"
                    />
                </td>
                <td className='property-value'>
                    { props.record?.lastWorkingDate ? moment(props.record?.lastWorkingDate, 'YYYY-MM-DD').format("DD-MM-YYYY") : '-'}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.notice_period_completion_status"
                        defaultMessage="Notice Period Completion Status"
                    />
                </td>
                <td className='property-value'>
                    <Typography.Text>
                        {props.record?.resignationNoticePeriodRemainingDays
                            || props.record?.resignationNoticePeriodRemainingDays == 0
                            ? props.record?.resignationNoticePeriodRemainingDays > 0
                                ? <FormattedMessage
                                    id="employee_journey_update.notice_period_completion_status.not_completed"
                                    defaultMessage="Not Completed"
                                />
                                : <FormattedMessage
                                    id="employee_journey_update.notice_period_completion_status.completed"
                                    defaultMessage="Completed"
                                />
                            : <FormattedMessage
                                id="employee_journey_update.notice_period_completion_status.no_info"
                                defaultMessage=" "
                            />
                        }
                    </Typography.Text>
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.resignation_type"
                        defaultMessage="Resignation Type"
                    />
                </td>
                <td className='property-value' colSpan={3}>
                    {props.data?.resignationTypes?.find(option => option.value == props.record?.resignationTypeId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.resignation_reason"
                        defaultMessage="Resignation Reason"
                    />
                </td>
                <td className='property-value' colSpan={3}>
                    {props.data?.resignationReasons?.find(option => option.value == props.record?.resignationReasonId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.resignation_remark"
                        defaultMessage="Resignation Remarks"
                    />
                </td>
                <td className='property-value' colSpan={3}>
                    {props.record?.resignationRemarks}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.attachment"
                        defaultMessage="Attachment"
                    />
                </td>
                <td className='property-value' colSpan={3}>
                    <Typography.Text>
                        {props.record.attachmentId && !attachment
                            ? <Spin size='small' />
                            : props.record.attachmentId && attachment?.name
                            && <><Button
                                type='link'
                                onClick={() => setIsAttachmentModalVisible(true)}
                            >
                                {attachment.name}
                            </Button>
                                <Modal
                                    title={attachment.name}
                                    visible={isAttachmentModalVisible}
                                    destroyOnClose={true}
                                    onCancel={() => setIsAttachmentModalVisible(false)}
                                    centered
                                    width="80vw"
                                    footer={[
                                        <Row>
                                            <Col span={12}>
                                                <Button
                                                    style={{ float: 'left' }}
                                                    type="link"
                                                    key="download"
                                                    onClick={() => {
                                                        let a = document.createElement("a");
                                                        a.href = attachment.data;
                                                        a.download = attachment.name;
                                                        a.click();
                                                    }}
                                                >
                                                    <DownloadOutlined style={{ marginRight: 8 }} />
                                                    <FormattedMessage
                                                        id="download"
                                                        defaultMessage="Download"
                                                    />
                                                </Button>
                                            </Col>
                                            <Col span={12}>
                                                <Button style={{ float: 'right' }} key="back" onClick={() => setIsAttachmentModalVisible(false)}>
                                                    <FormattedMessage
                                                        id="cancel"
                                                        defaultMessage="Cancel"
                                                    />
                                                </Button>
                                            </Col>
                                        </Row>
                                    ]}
                                >
                                    {
                                        attachment?.type.includes('image')
                                            ? <img src={attachment.data} style={{ height: '65vh', margin: '0 auto', display: 'block' }} />
                                            : <iframe src={attachment.data} style={{ width: '100%', height: '65vh' }} />
                                    }
                                </Modal>
                            </>}
                    </Typography.Text>
                </td>
            </tr>
        </tbody>
    </>;

    return (<>
        <table className='employee-journey-upcoming-table'>
            {tbody}
        </table>

        <Space style={{ display: 'flow-root' }}>
            <Space style={{ float: 'right' }}>
                <ModalForm
                    title={intl.formatMessage({
                        id: 'employee_journey.rollback_resignation',
                        defaultMessage: 'Rollback Resignation',
                    })}
                    visible={isRollbackModalVisible}
                    onVisibleChange={setIsRollbackModalVisible}
                    trigger={
                        <Button
                            key="rollback"
                            className='rollback-btn'
                        >
                            <FormattedMessage
                                id="rollback"
                                defaultMessage="Rollback"
                            />
                        </Button>
                    }
                    submitter={{
                        searchConfig: {
                            submitText: intl.formatMessage({
                                id: 'rollback',
                                defaultMessage: 'Rollback',
                            }),
                            resetText: intl.formatMessage({
                                id: 'cancel',
                                defaultMessage: 'Cancel',
                            }),
                        },
                    }}
                    modalProps={{
                        destroyOnClose: true,
                    }}
                    onFinish={async (values) => {
                        const key = 'rollbacking';
                        message.loading({
                            content: intl.formatMessage({
                                id: 'rollbacking',
                                defaultMessage: 'Rollbacking...',
                            }),
                            key,
                        });

                        const data = { ...props.record, ...values };
                        rollbackUpcomingEmployeeJourneyMilestone(props?.employee?.id, props?.record?.id, data)
                            .then(async (response) => {
                                const _response = await getEmployee(props.employee.id);
                                if (response.error) location.reload();
                                props.setEmployee(_response.data);

                                message.success({
                                    content:
                                        response.message ??
                                        intl.formatMessage({
                                            id: 'successfullyRollback',
                                            defaultMessage: 'Successfully Rollback',
                                        }),
                                    key,
                                });

                                return true;
                            })
                            .catch(error => {
                                message.error({
                                    content:
                                        error.message ??
                                        intl.formatMessage({
                                            id: 'failedToRollback',
                                            defaultMessage: 'Failed to rollback',
                                        }),
                                    key,
                                });
                            });
                    }}
                >
                    <Typography.Title level={5}>{props.title}</Typography.Title>
                    <table className='employee-journey-upcoming-table  employee-journey-rollback-table'>
                        {tbody}
                    </table>
                    <br />
                    <ProFormTextArea
                        name="rollbackReason"
                        label={intl.formatMessage({
                            id: 'employee_journey.rollback_reason',
                            defaultMessage: 'Rollback Reason',
                        })}
                        placeholder={intl.formatMessage({
                            id: 'employee_journey.type_here',
                            defaultMessage: 'Type here',
                        })}
                        rules={
                            [
                                {
                                    required: true,
                                    message: intl.formatMessage({
                                        id: 'employee_journey.required',
                                        defaultMessage: 'Required',
                                    })
                                },
                                {
                                    max: 250,
                                    message: intl.formatMessage({
                                        id: 'employee_journey.250_max_length',
                                        defaultMessage: 'Maximum length is 250 characters.',
                                    })
                                }
                            ]
                        }
                    />
                </ModalForm>
                <ModalForm
                    title={intl.formatMessage({
                        id: 'employee_journey.reupdate_resignation',
                        defaultMessage: 'Reupdate Resignation',
                    })}
                    visible={isReupdateModalVisible}
                    onVisibleChange={setIsReupdateModalVisible}
                    trigger={
                        <Button
                            key="reupdate"
                            className='reupdate-btn'
                        >
                            <FormattedMessage
                                id="reupdate"
                                defaultMessage="Reupdate"
                            />
                        </Button>
                    }
                    submitter={{
                        searchConfig: {
                            submitText: intl.formatMessage({
                                id: 'reupdate',
                                defaultMessage: 'Reupdate',
                            }),
                            resetText: intl.formatMessage({
                                id: 'cancel',
                                defaultMessage: 'Cancel',
                            }),
                        },
                    }}
                    modalProps={{
                        destroyOnClose: true,
                    }}
                    onFinish={async (values) => {
                        const key = 'reupdating';
                        message.loading({
                            content: intl.formatMessage({
                                id: 'reupdating',
                                defaultMessage: 'Reupdating...',
                            }),
                            key,
                        });

                        const data = { ...props.record, ...values };
                        reupdateUpcomingEmployeeJourneyMilestone(props?.employee?.id, props?.record?.id, data)
                            .then(async (response) => {
                                const _response = await getEmployee(props.employee.id);
                                if (response.error) location.reload();
                                props.setEmployee(_response.data);

                                message.success({
                                    content:
                                        response.message ??
                                        intl.formatMessage({
                                            id: 'successfullyReupdate',
                                            defaultMessage: 'Successfully Reupdate',
                                        }),
                                    key,
                                });

                                setIsReupdateModalVisible(false);
                            })
                            .catch(error => {
                                message.error({
                                    content:
                                        error.message ??
                                        intl.formatMessage({
                                            id: 'failedToReupdate',
                                            defaultMessage: 'Failed to reupdate',
                                        }),
                                    key,
                                });
                            });
                    }}
                    initialValues={props.record}
                >
                    <Typography.Title level={5}>{props.title}</Typography.Title>
                    <Row gutter={12}>
                        <Col span={8}>
                            <ProFormDatePicker
                                width='md'
                                format="DD-MM-YYYY"
                                name="resignationHandoverDate"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_handover_date',
                                    defaultMessage: "Resignation Handover Date",
                                })}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_resignation_handover_date',
                                    defaultMessage: "Select Resignation Handover Date",
                                })}
                            // rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                        <Col span={8}>
                            <ProFormDatePicker
                                width='md'
                                format="DD-MM-YYYY"
                                name="effectiveDate"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_effective_date',
                                    defaultMessage: "Resignation Effective Date",
                                })}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_resignation_effective_date',
                                    defaultMessage: "Select Resignation Effective Date",
                                })}
                                rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                        <Col span={8}>
                            <ProFormDatePicker
                                width="md"
                                format="DD-MM-YYYY"
                                name="lastWorkingDate"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.last_working_date',
                                    defaultMessage: "Last Working Date",
                                })}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_last_working_date',
                                    defaultMessage: "Select Last Working Date",
                                })}
                            // rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                    </Row>
                    <Row gutter={12}>
                        <Col span={8}>
                            <ProFormSelect
                                name="resignationTypeId"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_type',
                                    defaultMessage: "Resignation Type",
                                })}
                                showSearch
                                options={props.data?.resignationTypes}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_resignation_type',
                                    defaultMessage: "Select Resignation Type",
                                })}
                            // rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                        <Col span={8}>
                            <ProFormSelect
                                name="resignationReasonId"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_reason',
                                    defaultMessage: "Resignation Reason",
                                })}
                                showSearch
                                options={props.data?.resignationReasons}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_resignation_reason',
                                    defaultMessage: "Select Resignation Reason",
                                })}
                                rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                    </Row>
                    <Row gutter={12}>
                        <Col span={16}>
                            <ProFormTextArea
                                name="resignationRemarks"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_remark',
                                    defaultMessage: "Resignation Remarks",
                                })}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.type_here',
                                    defaultMessage: "Type here",
                                })}
                                rules={[
                                    {
                                        max: 250,
                                        message: intl.formatMessage({
                                            id: 'employee_journey.250_max_length',
                                            defaultMessage: 'Maximum length is 250 characters.',
                                        })
                                    }
                                ]}
                            />
                        </Col>
                    </Row>
                    <Row gutter={12}>
                        <Col span={8}>
                            <ProFormUploadButton
                                name="attachDocument"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.attach_document',
                                    defaultMessage: "Attach Document (JPG or PDF)",
                                })}
                                title={intl.formatMessage({
                                    id: 'upload_max_3mb',
                                    defaultMessage: "Upload (Max 3MB)",
                                })}
                                max={1}
                                listType='text'
                                fieldProps={{
                                    name: 'attachDocument'
                                }}
                                fileList={fileList}
                                onChange={async (info: any) => {
                                    let status = info?.file?.status;
                                    if (status === 'error') {
                                        const { fileList, file } = info;
                                        const { uid } = file;
                                        const index = fileList.findIndex((file: any) => file.uid == uid);
                                        const newFile = { ...file };
                                        if (index > -1) {
                                            newFile.status = 'done';
                                            newFile.percent = 100;
                                            delete newFile.error;
                                            fileList[index] = newFile;
                                            setFileList(fileList);
                                        }
                                    } else {
                                        setFileList(info.fileList);
                                    }
                                }}
                                rules={[
                                    {
                                        validator: (_, upload) => {
                                            if (upload !== undefined && upload && upload.length !== 0) {
                                                //check file size .It should be less than 3MB
                                                if (upload[0].size > 3145728) {
                                                    return Promise.reject(new Error(
                                                        intl.formatMessage({
                                                            id: 'pages.resignation.filesize',
                                                            defaultMessage: 'File size is too large. Maximum size is 3 MB',
                                                        })
                                                    ));
                                                }
                                                const isValidFormat = [
                                                    'image/jpeg',
                                                    'application/pdf',
                                                ]
                                                //check file format
                                                if (!isValidFormat.includes(upload[0].type)) {
                                                    return Promise.reject(new Error(
                                                        intl.formatMessage({
                                                            id: 'pages.resignation.fileformat',
                                                            defaultMessage: 'File format should be jpg or pdf',
                                                        })
                                                    ));
                                                }
                                            }
                                            return Promise.resolve();
                                        },
                                    },
                                ]}
                            />
                        </Col>
                    </Row>
                    <Row gutter={12}>
                        <Col span={8}>
                            <ProFormDatePicker
                                width="md"
                                format="DD-MM-YYYY"
                                name="effectiveDate"
                                label={intl.formatMessage({
                                    id: 'employee_journey_update.resignation_effective_date',
                                    defaultMessage: "Resignation Effective Date",
                                })}
                                placeholder={intl.formatMessage({
                                    id: 'employee_journey_update.select_resignation_effective_date',
                                    defaultMessage: "Select Resignation Effective Date",
                                })}
                                rules={[{ required: true, message: 'Required' }]}
                            />
                        </Col>
                    </Row>
                </ModalForm>
            </Space>
        </Space>
    </>);
};

export default ResignationUpcomingItem;
