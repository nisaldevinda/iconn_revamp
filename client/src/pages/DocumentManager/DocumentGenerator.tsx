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
  message as Message,
  Typography,
  Transfer
} from 'antd';
import { useParams, history, useAccess, Access , useIntl } from 'umi';
import { Editor } from '@tinymce/tinymce-react';
import { getTemplateTokens } from '@/services/model';
import {
  getDocumentCategories,
  getDocumentsList,
  getEmployeeDocument,
  addBulkLetter
} from '@/services/documentTemplate';
import ProForm, { ProFormSelect } from "@ant-design/pro-form";
import { ITokenOption, IDocumentTemplateForm, IParams } from './data';
import { apiKey, tokenizeEditorObj, getPageSize } from './editorHelper';
import PermissionDeniedPage from './../403';
import { getEmployeeList ,getManagerList , getSubordinatesList ,getEmployeeListForLocation } from '@/services/dropdown';
import { getAllLocations } from '@/services/location';
import { LeftOutlined , RightOutlined, TransactionOutlined } from '@ant-design/icons';
import './styles.css';
import styles from './styles.less';
export default (): React.ReactNode => {
  const { Option } = Select;
  const intl = useIntl();
  const { Title,Text } = Typography;
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
  const [content, setContent] = useState<string>('');
  const [isTokenModalVisible, setIsTokenModalVisible] = useState(false);
  const [selectedToken, setSelectedToken] = useState(null);
  const [buttonText, setButtonText] = useState<string>('Save & Share');
  const [modalVisible, setModalVisible] = useState(false);
  const [categories , setCategories] = useState([]);
  const [templates , setTemplates] = useState([]);
  const [selectedEmployees, setSelectedEmployees] = useState([]);
  const [initializing, setInitializing] = useState(false);
  const [audienceMethod, setAudienceMethod] = useState([]);
  const [audienceType, setAudienceType] = useState('');
  const [adminEmployees, setAdminEmployees] = useState([]);
  const [managers, setManagers] = useState([]);
  const [locations, setLocations] = useState([]);
  const [targetKeys, setTargetKeys] = useState<string[]>([]);
  const [managerEmployees, setManagerEmployees] = useState([]);
  const [templateID , setTemplateID] = useState('');
  const [employeesList , setEmployeesList] = useState([]);
  const [templateName, setTemplateName] = useState('');
  const [index, setIndex] = useState(0);
  const [contentArray , setContentArray] = useState([]);
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

  const setPageSettings = () => {
    let pageSize = {};
    let contentStyle = {};
    const pageName = form.getFieldValue('pageSize');
    pageSize = getPageSize(pageName);
    const left = 10;
    const right = 10;
    const top = 10;
    const bottom = 10;
    contentStyle = `body { font-family:Arial; font-size:10pt; margin-left: ${left}mm; margin-right: ${right}mm; margin-top: ${top}mm; margin-bottom: ${bottom}mm; }`;
    setEditorInit({ ...editorInit, content_style: contentStyle });
  };

  const onMarginChange = () => {
    setPageSettings();
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

  const onFinish = async (formData:any) => {
   
    const { templateId, documentCategory} = formData;
      let audience = { ...selectedEmployees};
    
      if ( audienceType === 'QUERY' || audienceType === 'ALL') {
         let empIds = employeesList.map((emp) => {
            return emp.id
         })
        audience = {
          employeeIds: empIds
        };
      } else if (audienceType === 'CUSTOM' || audienceType === 'REPORT_TO') {
        audience = {
          employeeIds: targetKeys
        };
      } else {
        audience = {};
      }
    
    const requestData = {
      templateId,
      documentCategoryId :documentCategory,
      audienceType: audienceType ? audienceType : null,
      audienceData : audience,
      folderId: 4,
    };
    try {
      const { message, data } = await addBulkLetter(requestData);
      Message.success(message);
      
    } catch (err) {   
      Message.error({
        content: intl.formatMessage({
          id: 'pages.documentTemplate.failedToSave',
          defaultMessage: 'Failed to Save',
        }),
      });
    }
   
  };
  const fetchEmployeeData = async (templateId: string, employeeId: string) => {
    try {
      const { data } = await getEmployeeDocument(employeeId, templateId);
      const { name, content, pageSettings } = data;
      setContent(content);
    } catch (error) {
      Message.error({
        content: intl.formatMessage({
          id: 'pages.documentTemplate.failedToloadEmployeeData',
          defaultMessage: 'Failed to load Employee Data',
        }),
      });
    }  
  };

  useEffect( ()=> {
    if (templateID != '' && employeesList.length != 0) {
      setTemplateName(employeesList[index]['employeeName']);
      fetchEmployeeData(templateID,employeesList[index]['id'])
    }
  },[employeesList]);

  useEffect( ()=> {
    if (targetKeys.length !=0) {
      let employees = employeesList.filter((emp) =>{
        return targetKeys.includes(emp.id);
      });
      setEmployeesList(employees);
    }
  },[targetKeys.length !=0]);
 
  useEffect(() => {
      getAllCategories();
      getEmployees();
  },[]);
  
  const getEmployees = async () => {
    setInitializing(true);

    const adminEmployeesRes = await getEmployeeList("ADMIN");
    setAdminEmployees(adminEmployeesRes?.data.map(employee => {
      return {
        title: employee.employeeNumber+' | '+employee.employeeName,
        key: employee.id
      };
    }));
    setEmployeesList(adminEmployeesRes.data);
  
    const managerRes = await getManagerList();
    setManagers(managerRes?.data.map(manager => {
      return {
        label: manager.employeeNumber+' | '+manager.employeeName,
        value: manager.id
      };
    }));

    const locationRes = await getAllLocations();
    setLocations(Object.values(locationRes?.data.map(location => {
      return {
        label: location.name,
        value: location.id
      };
    })));

    setInitializing(false);
    const _audienceMethod = [];
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ALL', defaultMessage: 'All' })}`, value: 'ALL' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ASSIGN_TO_MANAGER', defaultMessage: 'Assign To Manager' })}`, value: 'REPORT_TO' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'LOCATION', defaultMessage: 'Location' })}`, value: 'QUERY' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'CUSTOM', defaultMessage: 'Custom' })}`, value: 'CUSTOM' });
    setAudienceMethod(_audienceMethod);
  }

  const getAllCategories = async() => {
    const {data} = await getDocumentCategories();
    setCategories(data);
  }

  
  return (
    <Access
      accessible={hasPermitted('document-template-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <div
        style={{
          backgroundColor: 'white',
          borderTopLeftRadius: '30px',
          paddingLeft: '50px',
          paddingTop: '50px',
          paddingBottom: '50px',
          width: '100%',
          paddingRight: '0px',
        }}
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
                        },
                      ]}
                      style={{ width: 320 }}
                    >
                      <Select
                        showSearch
                        placeholder={intl.formatMessage({
                          id: 'documentTemplate.documentCategory.select',
                          defaultMessage: 'Select Document Category.',
                        })}
                        optionFilterProp="children"
                        onChange={async (value) => {
                          const { data } = await getDocumentsList(value);
                          setTemplates(data);
                        }}
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
                  </Col>
                </Row>
                <Col span={10}>
                  <Form.Item
                    name="templateId"
                    label={intl.formatMessage({
                      id: 'documentTemplate.templateName',
                      defaultMessage: 'Letter Template',
                    })}
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'name',
                          defaultMessage: 'Required',
                        }),
                      },
                    ]}
                  >
                    <Select
                      showSearch
                      placeholder={intl.formatMessage({
                        id: 'documentTemplate.template.select',
                        defaultMessage: 'Select Letter Template.',
                      })}
                      optionFilterProp="children"
                      onChange={(value) => {
                        setTemplateID(value);
                      }}
                    >
                      {templates.map((template) => {
                        return (
                          <Option key={template.id} value={template.id}>
                            {template.name}
                          </Option>
                        );
                      })}
                    </Select>
                  </Form.Item>
                </Col>
                <Col span={10}>
                  <Form.Item
                    label={intl.formatMessage({
                      id: 'pages.documentManagerReport.audience',
                      defaultMessage: 'Share with',
                    })}
                  >
                    <Text type="secondary">
                      {intl.formatMessage({
                        id: 'pages.documentManagerReport.secondary.label',
                        defaultMessage: 'Select the employees you want the letter to be sent.',
                      })}
                    </Text>
                  </Form.Item>

                  <ProFormSelect
                    width="lg"
                    name="audienceMethod"
                    options={audienceMethod}
                    onChange={(value) => {
                      setContent('');
                      setTemplateName('');
                      setTargetKeys([]);
                      setManagerEmployees([]);
                      setAudienceType(value);
                    }}
                    placeholder={intl.formatMessage({
                      id: 'pages.document.audienceType',
                      defaultMessage: 'Select Audience Type',
                    })}
                  />
                </Col>
                <Col span={10}>
                  {!initializing && audienceType == 'REPORT_TO' && (
                    <ProFormSelect
                      width="lg"
                      name="reportToManager"
                      label={intl.formatMessage({
                        id: 'pages.documentManagerReport.SELECT_A_MANAGER',
                        defaultMessage: 'Select a Manager',
                      })}
                      options={managers}
                      rules={[
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'pages.documentManagerReport.topic',
                            defaultMessage: 'Required',
                          }),
                        },
                      ]}
                      onChange={async (value) => {
                        const { data } = await getSubordinatesList(value);
                        setManagerEmployees(
                          data.map((employee) => {
                            return {
                              title: employee.employeeName,
                              key: employee.id,
                            };
                          }),
                        );
                        setEmployeesList(data);
                        if (data.length === 0) {
                          setContent('');
                          setTemplateName('');
                        }
                      }}
                      placeholder={intl.formatMessage({
                        id: 'pages.document.manager',
                        defaultMessage: 'Select Manager',
                      })}
                    />
                  )}

                  {!initializing && audienceType == 'QUERY' && (
                    <ProFormSelect
                      width="lg"
                      name="queryLocation"
                      label={intl.formatMessage({
                        id: 'pages.documentManagerReport.SELECT_A_LOCATION',
                        defaultMessage: 'Select a Location',
                      })}
                      options={locations}
                      rules={[
                        {
                          required: true,
                          message: intl.formatMessage({
                            id: 'topic',
                            defaultMessage: 'Required',
                          }),
                        },
                      ]}
                      onChange={async (value) => {
                        const { data } = await getEmployeeListForLocation(value);
                        setEmployeesList(data);
                        if (data.length === 0) {
                          setContent('');
                          setTemplateName('');
                        }
                      }}
                      placeholder={intl.formatMessage({
                        id: 'pages.document.location',
                        defaultMessage: 'Select Location',
                      })}
                    />
                  )}
                </Col>
                <Col span={18}>
                  {!initializing && (audienceType == 'CUSTOM' || audienceType == 'REPORT_TO') && (
                    <Transfer
                      dataSource={audienceType == 'CUSTOM' ? adminEmployees : managerEmployees}
                      showSearch
                      filterOption={(search, item) => {
                        return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
                      }}
                      targetKeys={targetKeys}
                      onChange={(newTargetKeys: string[]) => {
                        setTargetKeys(newTargetKeys);
                      }}
                      render={(item) => item.title}
                      listStyle={{
                        width: 300,
                        height: 300,
                        marginBottom: 20,
                      }}
                    />
                  )}
                </Col>
                <div>
                  <Form.Item
                    name="content"
                    label={intl.formatMessage({
                      id: 'documentTemplate.content',
                      defaultMessage: 'Content',
                    })}
                  >
                    <Card style={{ backgroundColor: '#F1F3F6', width: 850 }}>
                      <Title level={5} style={{ marginBottom: '3%' }}>
                        {templateName}
                      </Title>
                      <Editor
                        apiKey={TINY_API_KEY}
                        onInit={(evt, editor) => (editorRef.current = editor)}
                        initialValue={content}
                        init={editorInit}
                        disabled={true}
                      />
                      <Row style={{ marginRight: 14, paddingTop: 20 }}>
                        <Col span={24} style={{ textAlign: 'right' }}>
                          <Form.Item>
                            <Space>
                              <Button
                                htmlType="button"
                                className={styles.nextButton}
                                onClick={() => {
                                  if (index > 0 && index < employeesList.length) {
                                    let indexVal = index - 1;
                                    setIndex(index - 1);
                                    fetchEmployeeData(templateID, employeesList[indexVal]['id']);
                                    setTemplateName(employeesList[indexVal]['employeeName']);
                                  }
                                }}
                              >
                                <LeftOutlined style={{ color: '#626D6C' }} />
                              </Button>
                              <Button
                                htmlType="button"
                                className={styles.nextButton}
                                onClick={() => {
                                  if (index < employeesList.length - 1) {
                                    let indexVal = index + 1;
                                    setIndex(index + 1);
                                    fetchEmployeeData(templateID, employeesList[indexVal]['id']);
                                    setTemplateName(employeesList[indexVal]['employeeName']);
                                  }
                                }}
                              >
                                <RightOutlined style={{ color: '#626D6C' }} />
                              </Button>
                            </Space>
                          </Form.Item>
                        </Col>
                      </Row>
                    </Card>
                  </Form.Item>
                </div>
                <Row>
                  <Col span={24} style={{ textAlign: 'right' }}>
                    <Form.Item>
                      <Space>
                        <Button
                          htmlType="button"
                          onClick={() => {
                            form.resetFields();
                            setContent('');
                            setEmployeesList([]);
                            setAdminEmployees([]);
                            setManagerEmployees([]);
                            setTargetKeys([]);
                            setTemplateName('');
                          }}
                        >
                          {intl.formatMessage({
                            id: 'pages.documentTemplate.reset',
                            defaultMessage: 'Reset',
                          })}
                        </Button>
                        <Button type="primary" htmlType="submit">
                          {intl.formatMessage({
                            id: 'pages.documentTemplate.reset',
                            defaultMessage: `${buttonText}`,
                          })}
                        </Button>
                      </Space>
                    </Form.Item>
                  </Col>
                </Row>
              </Form>
            </Col>
          </Card>
        </PageContainer>
      </div>
    </Access>
  );
};
