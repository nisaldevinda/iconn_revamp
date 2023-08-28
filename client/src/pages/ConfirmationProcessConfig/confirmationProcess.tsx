import React, { useEffect, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { Access, useIntl, useAccess, useParams, history } from 'umi';
import {
  Form,
  Row,
  Col,
  Input,
  Select,
  Button,
  Card,
  Space,
  message as Message
} from 'antd';
import PermissionDeniedPage from '../403';
import OrgSelector from '@/components/OrgSelector';
import {
  getConfirmationProcess,
  createConfirmationProcess,
  updateConfirmationProcess,
} from '@/services/confirmationProcess';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getAllFormTemplates } from '@/services/template';

const ConfirmationProcessConfig = () =>  {

    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [form] = Form.useForm();
    const { id } = useParams();
    const primaryBtnText = id ? 'Update' : 'Save';

    const [jobCategories, setJobCategories] = useState([]);
    const [employmentTypes, setEmploymentTypes] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(false);

    const onFinishHandler = async (formData: any) => {
      try {
        setLoading(true);
        let result;
        if (id) {
          result = await updateConfirmationProcess(id, formData);
        } else {
          result = await createConfirmationProcess(formData);
        }
        if (result.error) {
          Message.error(result.message);
        } else {
          Message.success(result.message);
          history.push(`/settings/config-confirmation-process`);
        }
        setLoading(false);
      } catch (error) {
        setLoading(false);
        console.error(error);
      }
    }

    useEffect(() => {
      const loadFormData = async () => {
        try {
          setLoading(true);
          const jobCategoryResult = await getAllJobCategories();
          const jobCategoryOptions = jobCategoryResult.data.map(({ id, name }) => {
            return {
              label: name,
              value: id,
            };
          });
          const employementTypeResult = await getAllEmploymentStatus();
          const employementTypeOptions = employementTypeResult.data.map(({ id, title }) => {
            return {
              label: title,
              value: id,
            };
          });
          const formTemplateResult = await getAllFormTemplates();
          const templateOptions = formTemplateResult.data.map(({ id, name }) => {
            return {
              label: name,
              value: id,
            };
          });
          setJobCategories(jobCategoryOptions);
          setEmploymentTypes(employementTypeOptions);
          setTemplates(templateOptions);
          if (id !== undefined) {
            const confirmationProcess = await getConfirmationProcess(id);
            form.setFieldsValue(confirmationProcess.data);
          }
          setLoading(false);
        } catch (error) {
          setLoading(false);
          console.error(error);
        }
      };
      loadFormData();
    }, [id]);

    return (
      <Access
        accessible={hasPermitted('config-confirmation-process-read-write')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer
          loading={loading}
          header={{
            title: intl.formatMessage({
              id: `pages.confirmationprocess.title`,
              defaultMessage: 'Confirmation Process Configuration',
            }),
          }}
        >
          <Card>
            <Col offset={1} span={24}>
              <Form
                form={form}
                layout="vertical"
                initialValues={{
                  name: '',
                  orgEntityId: 1,
                  jobCategoryIds: [],
                  formTemplateId: '',
                }}
                name="control-hooks"
                onFinish={onFinishHandler}
              >
                <Row>
                  <Col span={12}>
                    <Form.Item
                      label="Confirmation Process"
                      name="name"
                      rules={[{ required: true }]}
                    >
                      <Input placeholder="Confirmation Process" />
                    </Form.Item>
                  </Col>
                </Row>
                <Form.Item name="orgEntityId" rules={[{ required: true }]}>
                  <Row gutter={24}>
                    <OrgSelector
                      value={form.getFieldValue('orgEntityId')}
                      setValue={(orgEntityId: number) => {
                        const formData = form.getFieldsValue();
                        form.setFieldsValue({ ...formData, orgEntityId });
                      }}
                    />
                  </Row>
                </Form.Item>
                <Row>
                  <Col span={12}>
                    <Form.Item
                      label="Job Categories"
                      name="jobCategoryIds"
                      rules={[{ required: true }]}
                    >
                      <Select
                        mode="multiple"
                        allowClear
                        placeholder="Please select"
                        defaultValue={[]}
                        onChange={() => {}}
                        options={jobCategories}
                      />
                    </Form.Item>
                  </Col>
                </Row>
                <Row>
                  <Col span={12}>
                    <Form.Item
                      label="Employment Types"
                      name="employmentTypeIds"
                      rules={[{ required: true }]}
                    >
                      <Select
                        mode="multiple"
                        allowClear
                        placeholder="Please select"
                        defaultValue={[]}
                        onChange={() => {}}
                        options={employmentTypes}
                      />
                    </Form.Item>
                  </Col>
                </Row>
                <Row>
                  <Col span={12}>
                    <Form.Item label="Template" name="formTemplateId" rules={[{ required: true }]}>
                      <Select
                        showSearch
                        placeholder="Select a template"
                        optionFilterProp="children"
                        onChange={() => {}}
                        filterOption={(input, option) =>
                          (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                        }
                        options={templates}
                      />
                    </Form.Item>
                  </Col>
                </Row>
                <Col span={24} style={{ textAlign: 'right' }}>
                  <Form.Item>
                    <Space>
                      <Button
                        htmlType="button"
                        onClick={() => {
                          console.log('Reset >>>');
                        }}
                      >
                        Reset
                      </Button>
                      <Button type="primary" htmlType="submit">
                        {primaryBtnText}
                      </Button>
                    </Space>
                  </Form.Item>
                </Col>
              </Form>
            </Col>
          </Card>
        </PageContainer>
      </Access>
    );
}

export default ConfirmationProcessConfig;