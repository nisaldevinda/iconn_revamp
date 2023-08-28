import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { useIntl } from 'react-intl';
import { Card, Col, message, Row, Typography, Space, Button, Form, Divider } from 'antd';
import ProForm, { ProFormDatePicker, ProFormSelect, ProFormTextArea, ProFormUploadButton } from '@ant-design/pro-form';
import { createNewPromotion } from '@/services/employeeJourney';
import { getEmployee } from '@/services/employee';
import { getBase64 } from "@/utils/fileStore";
import Alert from 'antd/lib/alert';
import OrgSelector from '@/components/OrgSelector';
import { getEntity } from '@/services/department';

interface CreateNewPromotionProps {
    data: any,
    employee: any,
    setEmployee: (values: any) => void,
    setCurrentJob: (values: any) => void,
    hasUpcomingJobs: boolean,
    currentJob: any
}

const CreateNewPromotion: React.FC<CreateNewPromotionProps> = (props) => {
    const intl = useIntl();
    const [formRef] = Form.useForm();

    const [loading, setLoading] = useState(false);
    const [fileList, setFileList] = useState([]);
    const [orgEntityId, setOrgEntityId] = useState(1);

    useEffect(() => {
        // setOrgEntityId(props.currentJob?.orgStructureEntityId);
        attachDocumentButtonLabelChange();
    });

    useEffect(() => {
        setOrgEntityId(props.currentJob?.orgStructureEntityId);
    }, [props.employee]);

    const onFinish = async (data: any) => {
        setLoading(true);

        const requestData = data;
        if (requestData.attachDocument && requestData.attachDocument.length > 0) {
            const base64File = await getBase64(data.attachDocument[0].originFileObj);
            requestData.fileName = data.attachDocument[0].name;
            requestData.fileSize = data.attachDocument[0].size;
            requestData.fileType = data.attachDocument[0].type;
            requestData.data = base64File;
        }
        const key = 'saving';
        requestData.orgStructureEntityId = orgEntityId;
        message.loading({
            content: intl.formatMessage({
                id: 'saving',
                defaultMessage: 'Saving...',
            }),
            key,
        });

        createNewPromotion(props.employee.id, requestData)
            .then(async (response) => {
                let _response = await getEmployee(props.employee.id);
                if (response.error) location.reload();

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
                id: 'employee_journey_update.create_new_promotion',
                defaultMessage: "Create New Promotion",
            })}
        </Typography.Title>
        <Card>
        {props.hasUpcomingJobs && <Alert
                type="info"
                className="employee-journey-promotion-alert"
                message={intl.formatMessage({
                    id: 'employee_journey_update.promotion_initial_validation_alert_msg',
                    defaultMessage: "User cannot create new promotion until the upcoming promotion is done",
                })}
            />}
            <ProForm
                form={formRef}
                onFinish={onFinish}
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
                                <Button
                                    disabled={props.hasUpcomingJobs}
                                    type="primary"
                                    loading={loading} onClick={() => _props.form?.submit?.()}
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
                    <Col span={24}>
                        {/* <Form.Item name="orgEntityId" rules={[{ required: true }]}> */}
                            <Row gutter={24}>
                            <OrgSelector
                                value={orgEntityId}
                                setValue={(orgEntityId: number) => {
                                    console.log(orgEntityId);
                                    setOrgEntityId(orgEntityId);
                                    // const currentValues = { ...props.values };
                                    // currentValues['orgEntityId'] = orgEntityId;
                                    // props.setValues(currentValues);
                                // const formData = props.form.getFieldsValue();
                                // props.form.setFieldsValue({ ...formData, orgEntityId });
                                }}
                            />
                            </Row>
                        {/* </Form.Item> */}
                    </Col>
                    <Divider></Divider>
                    <Col span={12}>
                        <ProFormSelect
                            name="jobCategoryId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.new_job_category',
                                defaultMessage: "New Job Category",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.jobCategories}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_new_job_category',
                                defaultMessage: "Select New Job Category",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="jobTitleId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.new_job_title',
                                defaultMessage: "New Job Title",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.jobTitles}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_new_job_title',
                                defaultMessage: "Select New Job Title",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={12}>
                        <ProFormSelect
                            name="payGradeId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.new_pay_grade',
                                defaultMessage: "New Pay Grade",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.payGrades}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_new_pay_grade',
                                defaultMessage: "Select New Pay Grade",
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
                            name="promotionTypeId"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.promotion_type',
                                defaultMessage: "Promotion Type",
                            })}
                            disabled={props.hasUpcomingJobs}
                            showSearch
                            options={props.data?.promotionTypes}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_promotion_type',
                                defaultMessage: "Select Promotion Type",
                            })}
                        // rules={[{ required: true, message: 'Required' }]}
                        />
                    </Col>
                    <Col span={24}>
                        <ProFormTextArea
                            name="promotionReason"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.promotion_reason',
                                defaultMessage: "Promotion Reason",
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
                    <Col span={24}>
                        <ProFormUploadButton
                            name="attachDocument"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.attach_document',
                                defaultMessage: "Attach Document (JPG or PDF)",
                            })}
                            disabled={props.hasUpcomingJobs}
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
                                                        id: 'pages.promotion.filesize',
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
                                                        id: 'pages.promotion.fileformat',
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
                    <Col span={12}>
                        <ProFormDatePicker
                            width="md"
                            name="effectiveDate"
                            format="DD-MM-YYYY"
                            label={intl.formatMessage({
                                id: 'employee_journey_update.promotion_effective_date',
                                defaultMessage: "Promotion Effective Date",
                            })}
                            disabled={props.hasUpcomingJobs}
                            placeholder={intl.formatMessage({
                                id: 'employee_journey_update.select_promotion_effective_date',
                                defaultMessage: "Select Promotion Effective Date",
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
                </Row>
            </ProForm>
        </Card>
    </>);
};

export default CreateNewPromotion;
