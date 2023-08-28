import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Form,
  Row,
  Col,
  Input,
  Select,
  Button,
  Card,
  Modal,
  Space,
  Divider,
  Typography,
  message as Message,
} from 'antd';
import { useParams, history, useAccess, Access , useIntl } from 'umi';
import { Editor } from '@tinymce/tinymce-react';
import { getTemplateTokens } from '@/services/model';
import {
  createDocumentTemplate,
  updateDocumentTemplate,
  getDocumentTemplate,
  createDocumentCategory,
  getDocumentCategories
} from '@/services/documentTemplate';
import { ITokenOption, IDocumentTemplateForm, IParams } from './data';
import { apiKey, tokenizeEditorObj, getPageSize } from './editorHelper';
import PermissionDeniedPage from './../403';
import { random } from 'lodash';
import { PlusOutlined } from '@ant-design/icons';
import ProForm ,{ ModalForm , ProFormText  } from '@ant-design/pro-form';

export default (): React.ReactNode => {
  const { Option } = Select;
  const { TextArea } = Input;
  const { Text } = Typography;
  const intl = useIntl();
  const { id } = useParams<IParams>();

  const templateLayout = {
    labelCol: { span: 20 },
    wrapperCol: { span: 20 },
  };

  const editorRef = useRef(null);
  const [templateTokens, setTemplateTokens] = useState<ITokenOption[]>([]);
  const [form] = Form.useForm();
  const [tokenForm] = Form.useForm();
  const [loading, setLoading] = useState<boolean>(false);
  const [buttonDisabled, setButtonDisabled] = useState(false);
  const [key, setKey] = useState<number>(1);
  const [content, setContent] = useState<string>('');
  const [isTokenModalVisible, setIsTokenModalVisible] = useState(false);
  const [selectedToken, setSelectedToken] = useState(null);
  const [buttonText, setButtonText] = useState<string>('Save');
  const [modalVisible, setModalVisible] = useState(false);
  const [categories , setCategories] = useState([]);
  const [pageSettingConfigs , setPageSettingConfigs] = useState({
    pageSize: 'a4',
    left: 10,
    right: 10,
    top: 10,
    bottom: 10
  });
  const [editorInit, setEditorInit] = useState<EditorProps>({
    ...tokenizeEditorObj,
    setup: function (editor) {
      editor.ui.registry.addButton('tokens', {
        text: 'Tokens',
        onAction: function () {
          setIsTokenModalVisible(true);
        },
      });
    },
  });
  const access = useAccess();
  const { hasPermitted } = access;


  const getEditorKey = () => {
    // get editor content
    const editorContent = editorRef.current?.getContent();
    if (editorContent) {
      // set if not empty
      setContent(editorContent);
    }
    return random(0, 9999999999);
  };


  const setPageSettings = () => {
    let pageSize = {};
    let contentStyle = {};
    const pageName = form.getFieldValue('pageSize');
    console.log(pageName);
    pageSize = getPageSize(pageName);
    const left = form.getFieldValue('marginLeft');
    const right = form.getFieldValue('marginRight');
    const top = form.getFieldValue('marginTop');
    const bottom = form.getFieldValue('marginBottom');

    contentStyle = `body { font-family:Arial; font-size:10pt; margin-left: ${left}mm; margin-right: ${right}mm; margin-top: ${top}mm; margin-bottom: ${bottom}mm; }`;
    setEditorInit({ ...editorInit, ...pageSize, content_style: contentStyle });
    setKey(getEditorKey());
  };


  const handleTokenModalOk = async () => {
    const { errorFields } = await tokenForm.validateFields();
    if (errorFields === undefined) {
      editorRef.current?.execCommand('mceInsertContent', false, selectedToken);
      setIsTokenModalVisible(false);
    }
  };

  const handleOnChange = (value) => {
    setSelectedToken(value);
  };

  const handleTokenModalCancel = () => {
    setIsTokenModalVisible(false);
  };

  const onFinish = async (formData: IDocumentTemplateForm) => {
    setButtonDisabled(true);
    const { name, description, pageSize, marginLeft, marginRight, marginTop, marginBottom , documentCategory} =
      formData;
    const requestData = {
      name,
      description,
      content: editorRef.current?.getContent(),
      pageSettings: { pageSize, marginLeft, marginRight, marginTop, marginBottom },
      documentCategoryId :documentCategory
    };

    if (id == undefined) {
      try {
        const { message, data } = await createDocumentTemplate(requestData);
        const { id: documentId } = data;
        history.push(`/settings/document-templates`);
        Message.success(message);
        setButtonDisabled(false);
      } catch (err) {
        setButtonDisabled(false);
        console.log(err);
      }
    } else {
      try {
        const { message } = await updateDocumentTemplate(id, requestData);
        Message.success(message);
        history.push(`/settings/document-templates`);
        setButtonDisabled(false);
      } catch (err) {
        setButtonDisabled(false);
        console.log(err);
      }
    }
  };
  const handleAddCategory =async(fields:any) => {
    try {
      const { data ,message } = await createDocumentCategory(fields);
      Message.success(message);
      getAllCategories();
      setModalVisible(false);
    } catch (error) {
        Message.error({
        content:
          intl.formatMessage({
            id: 'pages.documentTemplate.addCategory',
            defaultMessage: 'Failed to Add Category',
          }),
      });
    }
  }
  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      const tokenData = await getTemplateTokens();
      const tokens = tokenData.data.map((token: string) => {
        const text = token
          .replace(/_/g, ' ')
          .split(' ')
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' ');
        return {
          value: `{#${token}#}`,
          text,
        };
      });
      setTemplateTokens(tokens);
     
      if (id !== undefined) {
        const { data } = await getDocumentTemplate(id);
        const { name, description, content, pageSettings , documentCategoryId } = data;
        setButtonText('Update');
        setContent(content);
        form.setFieldsValue({ ...pageSettings, name, description ,documentCategory:documentCategoryId });
        setPageSettings();
        setLoading(false);
      } else {
        setLoading(false);
      }
    };

    try {
      fetchData();
      getAllCategories();
    } catch (error) {
      console.log('error:', error);
    }
  }, [id]);

  // useEffect(() => {
  //   setPageSettings();
  // }, [pageSettingConfigs]);


  const getAllCategories = async() => {
    const {data} = await getDocumentCategories();
    setCategories(data);
  }

  return (
    <Access
      accessible={hasPermitted('document-template-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer loading={loading}>
        <Modal
          title="Add Template Token"
          visible={isTokenModalVisible}
          okText="Add"
          onOk={handleTokenModalOk}
          onCancel={handleTokenModalCancel}
        >
          <Form form={tokenForm} layout="vertical">
            <Col span={24}>
              <Form.Item name="token" label="Token" rules={[{ required: true }]}>
                <Select
                  showSearch
                  placeholder="select a token"
                  optionFilterProp="children"
                  onChange={handleOnChange}
                  filterOption={(input, option) =>
                    option?.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                  }
                >
                  {templateTokens.map((token: ITokenOption) => {
                    return (
                      <Option value={token.value} key={token.value}>
                        {token.text}
                      </Option>
                    );
                  })}
                </Select>
              </Form.Item>
            </Col>
          </Form>
        </Modal>
        <Card>
          <Col offset={1} span={20}>
            <Form
              form={form}
              layout="vertical"
              initialValues={{
                pageSize: 'a4',
                name: '',
                description: '',
                marginLeft: '10',
                marginRight: '10',
                marginTop: '10',
                marginBottom: '10',
              }}
              name="control-hooks"
              onFinish={onFinish}
            >
              <Row>
                <Col span={10}>
                  <Space>
                    <Form.Item
                      name="documentCategory"
                      label={intl.formatMessage({
                        id: 'documentTemplate.documentCategory',
                        defaultMessage: 'Document Category',
                      })}
                      rules={[
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'documentTemplate.documentCategory.required',
                            defaultMessage: 'Required',
                          }),
                        }
                      ]}
                      style={{ width: 200 }}
                    >
                      <Select
                        showSearch
                        placeholder={intl.formatMessage({
                          id: 'documentTemplate.documentCategory.select',
                          defaultMessage: 'Select Document Category.',
                        })}
                        optionFilterProp="children"
                      >
                        {categories.map((category) => {
                          return (
                            <Option key={category.id} value={category.id}>
                              {category.name}
                            </Option>
                          );
                        })}
                      </Select>
                    </Form.Item>

                    <Button
                      style={{
                        marginTop: '10px'
                      }}
                      type='link'
                      onClick={() => {
                        setModalVisible(true);
                      }}
                    >
                      <PlusOutlined />  {intl.formatMessage({
                        id: 'documentTemplate.documentCategory.addCategory',
                        defaultMessage: 'Add Category.',
                      })}
                    </Button>
                  </Space>
                </Col>
              </Row>
              <Col span={10}>
                <Form.Item
                  name="name"
                  label="Name"
                  rules={[
                    { required: true, 
                      message: intl.formatMessage({
                        id: 'name',
                        defaultMessage: 'Required',
                      }),
                    },
                    { 
                      max: 100,
                      message: intl.formatMessage({
                        id: 'name',
                        defaultMessage: 'Maximum length is 100 characters',
                      })
                    },
                    {
                      pattern: /^\w+((?!\s{2}).)*$/,
                      message: intl.formatMessage({
                        id: 'name',
                        defaultMessage: 'Cannot contain more than one space.',
                      }),
                    },
                  ]}
                >
                  <Input />
                </Form.Item>
              </Col>
              <Col span={10}>
                <Form.Item
                  name="description"
                  label="Description"
                  rules={[
                    { 
                      max: 250 ,
                      message: intl.formatMessage({
                        id: 'description',
                        defaultMessage: 'Maximum length is 250 characters',
                      })
                    },
                    {
                      pattern: /^\w+((?!\s{2}).)*$/,
                      message: 'Cannot contain more than one space.',
                    },
                  ]}
                >
                  <TextArea rows={4} />
                </Form.Item>
              </Col>
              <Col span={16}>
              <Form.Item
                name="content"
                label="Content"
                // rules={[{ required: true, message: 'Content is required.' }]}
              >
                <Editor
                  apiKey={TINY_API_KEY}
                  key={key}
                  onInit={(evt, editor) => (editorRef.current = editor)}
                  initialValue={content}
                  init={editorInit}
                />
              </Form.Item>
              </Col>
              <Divider />
              <Col span={24}>
                <Form.Item>
                  <Space direction="vertical">
                    <Text strong>Page Settings</Text>
                    <Text type="secondary">
                      Adjust margins according to your document. Margins are specified in mm.
                      Default is 10mm
                    </Text>
                  </Space>
                </Form.Item>
              </Col>
              <Row></Row>
              <Row>
                <Col span={6}>
                  <Form.Item
                    {...templateLayout}
                    name="pageSize"
                    label="Page Size"
                    rules={[{ required: true, message: 'Page Size is required.' }]}
                  >
                    <Select
                      placeholder="Select a option and change input text above"
                      // onChange={setPageSettings}
                    >
                      <Option value="a4">A4</Option>
                      <Option value="a5">A5</Option>
                      <Option value="letter">Letter</Option>
                    </Select>
                  </Form.Item>
                </Col>
              </Row>
              <Row>
                <Col span={6}>
                  <Form.Item {...templateLayout} name="marginLeft" label="Margin Left">
                    <Input type="number" addonAfter="mm" onChange={(val)=>{
                      const configVals = {...pageSettingConfigs};
                      configVals.left =  parseInt(val.target.value);
                      setPageSettingConfigs(configVals);
                    }} />
                  </Form.Item>
                </Col>
                <Col span={6}>
                  <Form.Item {...templateLayout} name="marginRight" label="Margin Right">
                    <Input type="number" addonAfter="mm" onChange={(val)=> {
                      const configVals = {...pageSettingConfigs};
                      configVals.right =  parseInt(val.target.value);
                      setPageSettingConfigs(configVals);
                    }} />
                  </Form.Item>
                </Col>
              </Row>
              <Row>
                <Col span={6}>
                  <Form.Item {...templateLayout} name="marginTop" label="Margin Top">
                    <Input type="number" addonAfter="mm" onChange={(val)=> {
                      const configVals = {...pageSettingConfigs};
                      configVals.top =  parseInt(val.target.value);
                      setPageSettingConfigs(configVals);
                    }} />
                  </Form.Item>
                </Col>
                <Col span={6}>
                  <Form.Item {...templateLayout} name="marginBottom" label="Margin Bottom">
                    <Input type="number" addonAfter="mm" onChange={(val)=> {
                      const configVals = {...pageSettingConfigs};
                      configVals.bottom =  parseInt(val.target.value);
                      setPageSettingConfigs(configVals);
                    }} />
                  </Form.Item>
                </Col>
                <Col span={3}>
                  <Button style={{marginTop: 30}} onClick={() => {
                    setPageSettings();
                  }} type="primary">
                    {'Apply Page Settings'}
                  </Button>
                </Col>
              </Row>
              <Divider />
              <Row>
                <Col span={24} style={{ textAlign: 'right' }}>
                  <Form.Item>
                    <Space>
                      <Button
                        htmlType="button"
                        onClick={() => {
                          history.push(`/settings/document-templates`);
                        }}
                      >
                        Back
                      </Button>
                      <Button type="primary" htmlType="submit" disabled={buttonDisabled}>
                        {buttonText}
                      </Button>
                    </Space>
                  </Form.Item>
                </Col>
              </Row>
            </Form>
            {modalVisible &&
              <ModalForm
                width={500}
                title={intl.formatMessage({
                  id: 'pages.document.addNewDocumentCategory',
                  defaultMessage: 'Add Document Category',
                })}
                onFinish={async (values: any) => {
                  await handleAddCategory(values as any);
                }}
                visible={modalVisible}
                onVisibleChange={setModalVisible}
                submitter={{
                  searchConfig: {
                    submitText: intl.formatMessage({
                      id: 'pages.document.save',
                      defaultMessage: 'Save',
                    }),
                    resetText: intl.formatMessage({
                      id: 'pages.document.cancel',
                      defaultMessage: 'Cancel',
                    }),
                  },
                }}

              >
                <ProForm.Group>
                  <Col style={{ paddingLeft: 20 }} >
                    <ProFormText
                      width="md"
                      name="name"
                      label={intl.formatMessage({
                        id: 'pages.document.categoryName',
                        defaultMessage: 'Document Category Name',
                      })}
                      placeholder={intl.formatMessage({
                        id: 'pages.document.placeholder.categoryName',
                        defaultMessage: 'Enter a Document Category Name',
                      })}
                      rules={[
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'pages.document.categoryName.required',
                            defaultMessage: 'Required',
                          })
                        }
                      ]}
                    />
                  </Col>
                </ProForm.Group>
              </ModalForm>}
          </Col>
        </Card>
      </PageContainer>
    </Access>
  );
};
