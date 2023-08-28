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
import { getResignationProcess, createResignationProcess, updateResignationProcess } from '@/services/resignationProcess';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllFormTemplates } from '@/services/template';

const ResignationProcessConfig = () =>  {

    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [form] = Form.useForm();
    const { id } = useParams();
    const primaryBtnText = id ? 'Update' : 'Save';

    const [jobCategories, setJobCategories] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(false);
    const [initialValues, setInitialValues] = useState({
      name: '',
      orgEntityId: 1,
      jobCategoryIds: [],
      formTemplateId: '',
    });

    const onFinishHandler = async (formData: any) => {
      try {
        setLoading(true);
        let result;
        if (id) {
          result = await updateResignationProcess(id, formData);
        } else {
          result = await createResignationProcess(formData);
        }
        if (result.error) {
          Message.error(result.message);
        } else {
          Message.success(result.message);
          history.push(`/settings/config-resignation-process`);
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
          const formTemplateResult = await getAllFormTemplates();
          const templateOptions = formTemplateResult.data.map(({ id, name }) => {
            return {
              label: name,
              value: id,
            };
          });
          setJobCategories(jobCategoryOptions);
          setTemplates(templateOptions);
          if (id !== undefined) {
            const resignationProcess = await getResignationProcess(id);
            setInitialValues(resignationProcess.data);
            form.setFieldsValue(resignationProcess.data);
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
        accessible={hasPermitted('config-resignation-process-read-write')}
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer
          loading={loading}
          header={{
            title: intl.formatMessage({
              id: `pages.resignationprocess.title`,
              defaultMessage: 'Resignation Process Configuration',
            }),
          }}
        >
          <Card>
            <Col offset={1} span={24}>
              <Form
                form={form}
                layout="vertical"
                initialValues={initialValues}
                name="control-hooks"
                onFinish={onFinishHandler}
              >
                <Row>
                  <Col span={12}>
                    <Form.Item
                      label="Resignation Process Group Name"
                      name="name"
                      rules={[{ required: true }]}
                    >
                      <Input placeholder="Resignation Process Group Name" />
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
                      label="Exit Interview Form"
                      name="formTemplateId"
                      rules={[{ required: true }]}
                    >
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
                          form.resetFields();
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

export default ResignationProcessConfig;