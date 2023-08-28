import React, { useEffect, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Col,
  Form,
  Space,
  Card,
  Button,
  message as Message,
} from 'antd';
import { useParams } from 'umi';
import { getFormTemplateInstanceInitialValues } from '@/utils/utils';
import { getFormTemplateInstance, updateFormTemplateInstance } from '@/services/template';
import FormContent from './formContent';

interface ExecutorProps {}

const Executor: React.FC<ExecutorProps> = () => {
  const { id } = useParams();
  const [form] = Form.useForm();

  const [loading, setLoading] = useState(false);
  const [instance, setInstance] = useState({});
  const [formContent, setFormContent] = useState(null);
  const [formTitle, setFormTitle] = useState('');
  const [formDiscription, setFormDiscription] = useState('');
  const [formValues, setFormValues] = useState({});
  const [editMode, setEditMode] = useState(false);

  useEffect(() => {
    const init = async () => {
      try {
        setLoading(true);
        const result = await getFormTemplateInstance(id);
        if (result.error) {
          Message.error(result.message);
        }
        setInstance(result.data);
        const editable = !(result.data.status == 'COMPLETED' || result.data.status == 'CANCELED');
        const blueprint = JSON.parse(result.data.blueprint);
        const intialObj = getFormTemplateInstanceInitialValues(blueprint.formContent);
        const savedData = result.data.response ? JSON.parse(result.data.response) : null;
        console.log('daft >>>', savedData);
        savedData ? setFormValues(savedData) : setFormValues(intialObj);
        // setFormValues(intialObj);
        setFormTitle(blueprint.formTitle);
        setFormDiscription(blueprint.formDiscription);
        setFormContent(blueprint.formContent);
        setEditMode(editable);
        setLoading(false);
      } catch (error) {
        console.log(error);
        setLoading(false);
      }
    };
    init();
  }, []);

  const onSaveHandler = async () => {
    try {
      setLoading(true);
      const result = await updateFormTemplateInstance(instance.id, { values: formValues });
      if (result.error) {
        Message.error(result.message);
      } else {
        Message.success(result.message);
        setEditMode(false);
      }
      setLoading(false);
    } catch (error) {
      console.error(error);
      setLoading(false);
    }
  }

  return (
    <PageContainer
      loading={loading}
      header={{
        title: formDiscription ? `${formTitle} - ${formDiscription}` : formTitle,
      }}
    >
      {formContent ? (
        <Card>
          <Col offset={1} span={16}>
            <Form
              form={form}
              layout="vertical"
              initialValues={formValues}
              name="control-hooks"
              onFinish={onSaveHandler}
            >
              <FormContent
                content={formContent}
                formReference={form}
                currentRecord={formValues}
                setCurrentRecord={setFormValues}
              />
              {editMode ? (
                <Col span={24} style={{ textAlign: 'right' }}>
                  <Form.Item>
                    <Space>
                      <Button type="primary" htmlType="submit">
                        Save
                      </Button>
                    </Space>
                  </Form.Item>
                </Col>
              ) : null}
            </Form>
          </Col>
        </Card>
      ) : null}
    </PageContainer>
  );
};

export default Executor;
