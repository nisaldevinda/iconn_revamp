import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { useIntl } from 'react-intl';
import { Card, Col, message, Row, Typography, Space, Button, Form } from 'antd';
import ProForm, { ProFormDatePicker, ProFormDependency, ProFormSelect, ProFormTextArea, ProFormUploadButton } from '@ant-design/pro-form';
import { contractRenewal } from '@/services/employeeJourney';
import { getEmployee } from '@/services/employee';
import { getBase64 } from "@/utils/fileStore";
import Alert from 'antd/lib/alert';
import OrgSelector from '@/components/OrgSelector';
import { getEntity } from '@/services/department';

interface ContractRenewalProps {
    data: any,
    employee: any,
    setEmployee: (values: any) => void,
    currentJob: any,
    hasUpcomingJobs: boolean
}

const ContractRenewal: React.FC<ContractRenewalProps> = (props) => {
    const intl = useIntl();
    const [formRef] = Form.useForm();

    const [loading, setLoading] = useState(false);
    const [initialValues, setInitialValues] = useState();
    const [fileList, setFileList] = useState([]);
    const [orgStructureEntityId, setOrgStructureEntityId] = useState<number>();

    useEffect(() => {
        attachDocumentButtonLabelChange();
    });

    useEffect(() => {
        const _initialValues = { ...props.currentJob };

        delete _initialValues['confirmationAction'];
        delete _initialValues['employmentStatusId'];
        delete _initialValues['confirmationReasonId'];
        delete _initialValues['confirmationRemark'];
        delete _initialValues['effectiveDate'];

        setOrgStructureEntityId(_initialValues.orgStructureEntityId);
        setInitialValues(_initialValues);
    }, [props.currentJob]);

    const onFinish = async (data: any) => {
        setLoading(true);

        data.orgStructureEntityId = orgStructureEntityId;
        const requestData = data;
        if (requestData.attachDocument && requestData.attachDocument.length > 0) {
            const base64File = await getBase64(data.attachDocument[0].originFileObj);
            requestData.fileName = data.attachDocument[0].name;
            requestData.fileSize = data.attachDocument[0].size;
            requestData.fileType = data.attachDocument[0].type;
            requestData.data = base64File;
        }
        const key = 'saving';
        message.loading({
            content: intl.formatMessage({
                id: 'saving',
                defaultMessage: 'Saving...',
            }),
            key,
        });

        contractRenewal(props.employee.id, requestData)
            .then(async (response) => {
                let _response = await getEmployee(props.employee.id);
                if (response.error) location.reload();
                // props.setEmployee(_response.data);

                const getEntityCallStack = [];
                let entityList = {};

                _response.data.jobs.forEach(job => {
                    if (job.orgStructureEntityId && !entityList[job.orgStructureEntityId]) {
                        getEntityCallStack.push(getEntity(job.orgStructureEntityId).then(data => {
                            entityList[job.orgStructureEntityId] = data.data;
                        }));
                    }
                });

                Promise.all(getEntityCallStack).then(() => {
                    _response.data.jobs = _response.data.jobs.map(job => {
                        return {
                            ...job,
                            orgStructureEntity: entityList[job.orgStructureEntityId]
                        };
                    });
                    props.setEmployee(_response.data);
                });

                message.success({
                    content:
                        response.message ??
                        intl.formatMessage({
                            id: 'successfullySaved',
                            defaultMessage: 'Successfully Saved',
                        }),
                    key,
                });

                setFileList([]);
                formRef.resetFields();
                setLoading(false);
            })
            .catch(error => {
                message.error({
                    content:
                        error?.message ??
                        intl.formatMessage({
                            id: 'failedToSave',
                            defaultMessage: 'Failed to Save',
                        }),
                    key,
                });

                setLoading(false);
            });
    }

    const attachDocumentButtonLabelChange = () => {
        const btnDom = document.querySelectorAll(".attach-document-button span")[1];
        const replacement = document.createElement('span');
        replacement.innerHTML = intl.formatMessage({
            id: 'upload',
            defaultMessage: "Upload",
        });
        btnDom?.parentNode?.replaceChild(replacement, btnDom);
    }

    return (<>
        <Typography.Title level={5} style={{ marginTop: 24 }}>
            {intl.formatMessage({
                id: 'employee_journey_update.confirmation_contract_renewal',
                defaultMessage: "Confirmation/Contract Renewal",
            })}
        </Typography.Title>
        <Card>
            {props.hasUpcomingJobs && <Alert
                type="info"
                className="employee-journey-contract-alert"
                message={intl.formatMessage({
                    id: 'employee_journey_update.confirmation_contract_initial_validation_alert_msg',
                    defaultMessage: "User cannot renew conformation/contract until the upcoming renewal is done",
                })}
            />}
            {initialValues && <ProForm
                form={formRef}
                onFinish={onFinish}
                initialValues={initialValues}
                submitter={{
                    render: (_props, doms) => {
                        return [
                            <Space style={{ float: 'right' }}>
                                <Button
                                    disabled={props.hasUpcomingJobs}
                                    onClick={() => {
                                        setFileList([]);
                                        _props.form?.resetFields();
                                    }}
                                >
                                    {intl.formatMessage({
                                        id: 'reset',
                                        defaultMessage: "Reset",
                                    })}
                                </Button>
                                <Button type="primary"
                                    disabled={props.hasUpcomingJobs}
                                    loading={loading}
                                    onClick={() => _props.form?.submit?.()}
                                >
                                    {intl.formatMessage({
                                        id: 'save',
                                        defaultMessage: "Save",
                                    })}
                                </Button>
                            </Space>
                        ];
                    },
                }}
            >
                <Row gutter={12}>
                    <Col span={12}>
                        <ProFormSelect
                            name="confirmationAction"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.action',
                                defaultMessage: "Action",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.confirmationActions}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_action',
                                defaultMessage: "Select Action",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <ProFormDependency name={['confirmationAction']}>
                        {({ confirmationAction }) =>
                            <Col span={12}>
                                <ProFormSelect
                                    name="employmentStatusId"
                                    label={intl.formatMessage({
                                        id: 'employee_journey_update.new_employment_status',
                                        defaultMessage: "New Employment Status",
                                    })}
                                    disabled={props.hasUpcomingJobs}
                                    showSearch
                                    options={
                                        confirmationAction == 'ABSORB_TO_PERMANENT_CARDER'
                                            ? props.data?.employmentStatus.filter(option => option.record?.category == 'PERMANENT')
                                            : confirmationAction == 'EXTEND_THE_PROBATION'
                                                ? props.data?.employmentStatus.filter(option => option.record?.category == 'PROBATION')
                                                : confirmationAction == 'CONTRACT_RENEWAL'
                                                    ? props.data?.employmentStatus.filter(option => option.record?.category == 'CONTRACT')
                                                    : []
                                    }
                                    placeholder={intl.formatMessage({
                                        id: 'employee_journey_update.select_new_employment_status',
                                        defaultMessage: "Select New Employment Status",
                                    })}
                                // rules={[{ required: true, message: 'Required' }]}
                                />
                            </Col>
                        }
                    </ProFormDependency>
                    <OrgSelector
                        value={orgStructureEntityId}
                        setValue={(value: number) => setOrgStructureEntityId(value)}
                        readOnly={props.hasUpcomingJobs}
                    />
                    <Col span={12}>
                        <ProFormSelect
                            name="locationId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.location',
                                defaultMessage: "Location",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.locations}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_location',
                                defaultMessage: "Select Location",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="reportsToEmployeeId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.reporting_person',
                                defaultMessage: "Reporting Person",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.managers}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_reporting_person',
                                defaultMessage: "Select Reporting Person",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="functionalReportsToEmployeeId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.functional_reporting_person',
                                defaultMessage: "Functional Reporting Person",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.managers}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_functional_reporting_person',
                                defaultMessage: "Select Functional Reporting Person",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="jobCategoryId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.job_category',
                                defaultMessage: "Job Category",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.jobCategories}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_job_category',
                                defaultMessage: "Select Job Category",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="jobTitleId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.job_title',
                                defaultMessage: "Job Title",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.jobTitles}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_job_title',
                                defaultMessage: "Select Job Title",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="payGradeId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.pay_grade',
                                defaultMessage: "Pay Grade",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.payGrades}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_pay_grade',
                                defaultMessage: "Select Pay Grade",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="calendarId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.calendar',
                                defaultMessage: "Calendar",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.calendars}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_calendar',
                                defaultMessage: "Select Calendar",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="confirmationReasonId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.confirmation_reason',
                                defaultMessage: "Confirmation Reason",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.confirmationReasons}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_confirmation_reason',
                                defaultMessage: "Select Confirmation Reason",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={24}>
                        <ProFormTextArea
                            name="confirmationRemark"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.remarks',
                                defaultMessage: "Remarks",
                            })}
                            disabled={props.hasUpcomingJobs}
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
                    <Col span={12}>
                        <ProFormDatePicker
                            width="md"
                            format="DD-MM-YYYY"
                            name="effectiveDate"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.renewal_effective_date',
                                defaultMessage: "Renewal Effective Date",
                            })}
                            disabled={props.hasUpcomingJobs}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_renewal_effective_date',
                                defaultMessage: "Select Renewal Effective Date",
                            })}
                            rules={[
                                {
                                    required: true,
                                    message: intl.formatMessage({
                                        id: 'required',
                                        defaultMessage: "Required",
                                    })
                                }
                            ]}
                        />
                    </Col>
                    <Col span={24}>
                        <Typography.Title level={5}>Completed Evaluation</Typography.Title>
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
                            disabled={props.hasUpcomingJobs}
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
                                                        id: 'pages.contract.filesize',
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
                                                        id: 'pages.contract.fileformat',
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
            </ProForm>}
        </Card>
    </>);
};

export default ContractRenewal;
