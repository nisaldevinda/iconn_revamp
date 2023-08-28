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
  Switch,
  Radio,
  DatePicker,
  TreeSelect,
} from 'antd';
import { useParams, history, useAccess, Access, useIntl } from 'umi';
import { Editor } from '@tinymce/tinymce-react';
import { random } from 'lodash';
import { getTemplateTokens, getWorkflowTemplateTokens } from '@/services/model';
import {
  createEmailTemplate,
  updateEmailTemplate,
  getEmailTemplate,
  getEmailTemplateContents,
  getEmailTemplateContent,
  getEmailNotificationTreeData,
  getWorkflowContexts,
  getEmailTemplateContentsByContextId
} from '@/services/emailTemplate';
import { ITokenOption, IDocumentTemplateForm, IParams } from './data';
import { apiKey, tokenizeEditorObj, getPageSize } from './editorHelper';
import moment from 'moment';
import { ProFormSelect } from '@ant-design/pro-form';
import { queryActionData, queryContextBaseActionData } from '@/services/workflowServices';
import styles from './index.less';
import { getAllLocations } from '@/services/location';
import { getAllDepartment } from '@/services/department';
import { queryUserRoles } from '@/services/userRole';
import { getAllUser } from '@/services/user';
import _ from 'lodash';
import PermissionDeniedPage from './../403';

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

  const access = useAccess();
  const { hasPermitted } = access;
  const editorRef = useRef(null);
  const [templateTokens, setTemplateTokens] = useState<ITokenOption[]>([]);
  const [form] = Form.useForm();
  const [tokenForm] = Form.useForm();
  const [loading, setLoading] = useState<boolean>(false);
  const [isSelectNextActionPerformer, setIsSelectNextActionPerformer] = useState<boolean>(false);
  const [isWorkflowTemplate, setIsWorkflowTemplate] = useState<boolean>(false);
  const [key, setKey] = useState<number>(1);
  const [content, setContent] = useState<string>('');
  const [selectedActionId, setSelectedActionId] = useState<number | null>(null);
  const [isTokenModalVisible, setIsTokenModalVisible] = useState(false);
  const [selectedToken, setSelectedToken] = useState(null);
  const [isActionBased, setIsActionBased] = useState(true);
  const [isTemplateExist, setIsTemplateExist] = useState(false);
  const [notificationFrequency, setNotificationFrequency] = useState("once");
  const [reminderType, setReminderType] = useState("onTheSameDate");
  // const [treeData, setTreeData] = useState([
  //   { pId: 0, value: 'allUsers', title: 'Users' },
  //   { pId: 1, value: 'allRoles', title: 'Roles' },
  //   { pId: 2, value: 'allDepartments', title: 'Departments' },
  //   { pId: 3, value: 'allLocations', title: 'Locations' },
  // ])
  const [treeData, setTreeData] = useState<any>([])
  // const [toTreeData, setToTreeData] = useState([
  //   { pId: 0, value: 'manger', title: 'Manager', isLeaf: true },
  //   { pId: 1, value: 'employee', title: 'Employee', isLeaf: true },
  //   { pId: 2, value: 'allRoles', title: 'Roles'},
  // ])
  const [toTreeData, setToTreeData] = useState<any>([])
  const [selectedNotificationType, setSelectedNotificationType] = useState<string>('actionBased')
  const [contentTemplates, setContentTemplates] = useState([]);
  const [nextPerformActions, setNextPerformActions] = useState([]);
  const [templateContentId, setTemplateContentId] = useState()
  const [filteredActionData, setFilteredActionData] = useState([])



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

  const getEditorKey = () => {
    // get editor content
    const editorContent = editorRef.current?.getContent();
    if (editorContent) {
      // set if not empty
      setContent(editorContent);
    }
    return random(0, 9999999999);
  };



const contentTypeOnChange=async (e)=>{
  const contentData= await getEmailTemplateContent(e);
     //   setTemplateContentId(contentData.data.templateName)
       // form.setFieldsValue({'contentId':contentData.data.templateName})
       console.log(contentData)
      setContent(contentData.data.content);
  
  form.setFieldsValue({contentId:e});

}
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
  const notificationTypeOnChange = e => {
    setSelectedNotificationType(e.target.value);
    if (e.target.value === 'actionBased') {
      setIsActionBased(true)
    }
    else {
      setIsActionBased(false)
    }
  }

  const getContextList = async () => {
    const actions: any = [];

    const res = await getWorkflowContexts();

  
    await res.data.forEach(async (element: any) => {
      await actions.push({ value: element['id'], label: element['contextName'] });
    });

    return actions;
  };

  const getContextBaseActions = async (value) => {
    // const data = await queryContextBaseActionData(params);
    let actionData =  [
      { value: 1, label: 'Create Action' },
      { value: 2, label: 'Level Wise Approve Action' },
      { value: 3, label: 'Reject Action' },
      { value: 4, label: 'Cancel Action'},
      { value: 5, label: 'Finale Approve Action'},
    ];

    setFilteredActionData(actionData);
  };

 
  const emailMessageOnChange = e => {
    if (e.target.value == 'newTemplate')
      setIsTemplateExist(false);
    else
      setIsTemplateExist(true)

  }
  const renderWhenTo = (frequency) => {

    switch (frequency) {
      case "once":
        return (
          <>
            <Row gutter={8}>
              <Col span={6}><Form.Item label="Send Notification: " /></Col>
              <Col span={12} >

                <Form.Item name="reminderType" rules={[{ required: true }]} >
                  <Select onChange={e => { setReminderType(e) }} >
                    <Select.Option value="onTheSameDate">On the same Day</Select.Option>
                    <Select.Option value="Before">Before</Select.Option>
                    <Select.Option value="after">After</Select.Option>

                  </Select>
                </Form.Item>
              </Col>
              {reminderType !== 'onTheSameDate' ? <>
                <Col span={6}>
                  <Form.Item name="reminderValue" rules={[{ required: true }]} >
                    <Input type="number" addonAfter={"Days"} />
                  </Form.Item>
                </Col>
              </> : <></>}

            </Row>

          </>

        )

      case "monthly":
        return (<>

          <Row gutter={8}>
            <Col span={6}><Form.Item label="Send Notification: " /></Col>
            <Col span={12} >

              <Form.Item name="reminderType" rules={[{ required: true }]} >
                <Select onChange={e => { setReminderType(e) }} >
                  <Select.Option value="onTheSameDate">On the same Day And Monthly</Select.Option>
                  <Select.Option value="Before">Monthly Before</Select.Option>
                  <Select.Option value="after">Monthly After</Select.Option>

                </Select>
              </Form.Item>
            </Col>
            {reminderType !== 'onTheSameDate' ? <>
              <Col span={6}>
                <Form.Item name="reminderValue" rules={[{ required: true }]} >
                  <Input type="number" addonAfter={"Days"} />
                </Form.Item>
              </Col>
            </> : <></>}

          </Row>
        </>)

      case "annually":
        return (<>
          <Row gutter={8}>
            <Col span={4}><Form.Item label="Send Notification: " /></Col>
            <Col span={12} >

              <Form.Item name="reminderType" rules={[{ required: true }]} >
                <Select onChange={e => { setReminderType(e) }} >
                  <Select.Option value="onTheSameDate">On the same Day And Annually</Select.Option>
                  <Select.Option value="Before">Annually Before</Select.Option>
                  <Select.Option value="after">Annually After</Select.Option>

                </Select>
              </Form.Item>
            </Col>
            {reminderType !== 'onTheSameDate' ? <>
              <Col span={6}>
                <Form.Item name="reminderValue" rules={[{ required: true }]} >
                  <Input type="number" addonAfter={"Days"} />
                </Form.Item>
              </Col>
            </> : <></>}

          </Row>

        </>)

      default:
        return (<></>)
    }

  }


  const onFinish = async (formData: IDocumentTemplateForm) => {
    // const { formName, alertName, description, forGroups, status,from,to,cc, bcc,replyTo,subject, notificationType, emailMessage } = formData;
    let formDatan=formData
    formDatan['from']=JSON.stringify(formData.from)
    formDatan['to']=JSON.stringify(formData.to)
    formDatan['cc']=JSON.stringify(formData.cc)
    formDatan['bcc']=JSON.stringify(formData.bcc)

    const requestData = {
      ...formDatan,
      content: editorRef.current?.getContent(),
    };

    if (requestData['actionId']) {
      requestData['actionId'] = requestData['actionId'].toString();
    }

    if (requestData['nextPerformActions']) {
      requestData['nextPerformActions'] = JSON.stringify(requestData['nextPerformActions']);
    }

    if (id === undefined) {
      try {
        const { message, data } = await createEmailTemplate(requestData);
        const { id: documentId } = data;
        history.push(`/settings/email-notifications`);
        Message.success(message);
      } catch (err) {
        console.log(err);
      }
    } else {
      try {
        const { message } = await updateEmailTemplate(id, requestData);
        Message.success(message);
      } catch (err) {
        console.log(err);
      }
    }
  };

  useEffect(() => {
    setLoading(true);
    const fetchData = async () => {
      // setLoading(true);
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

      const contentTemplateData = await getEmailTemplateContents({});
      setContentTemplates(contentTemplateData.data);
      
      if (id !== undefined) {
        const { data } = await getEmailTemplate(id);
        const contentData= await getEmailTemplateContent(data.contentId);
     //   setTemplateContentId(contentData.data.templateName)
        form.setFieldsValue({'emailMessage':'existingTemplate'});

        if (data['actionId'] != null) {
          setSelectedActionId(parseInt(data['actionId']));
        }
        if (data['workflowContextId'] != null) {
          const contentTemplateData = await getEmailTemplateContentsByContextId({contextId: data['workflowContextId']});
          setContentTemplates(contentTemplateData.data);
          getContextBaseActions(data['workflowContextId']);
        }

        if (data['formName'] == 'workflow') {
          setIsWorkflowTemplate(true);
        } else {
          setIsWorkflowTemplate(false);
        }
        if(!Number.isNaN(data.contentId)) 
        {        
          await setIsTemplateExist(true)
          setContent(contentData.data.content);
        }
        data['contentId']=contentData.data.id;
        form.setFieldsValue({...data });

        if (data['to'].includes('nextActionPerformer')) {
          setIsSelectNextActionPerformer(true);
          setNextPerformActions(data['nextPerformActions']);
        } else {
          setIsSelectNextActionPerformer(false);
        }

        if (data['workflowContextId'] != null) {
          const tokenData = await getWorkflowTemplateTokens({'workflowContextId' : data['workflowContextId']});
          setTemplateTokens(tokenData.data);
        }
        setLoading(false);
      } else {
        setLoading(false);
      }
    };

    try {
      getEmailNotificationTreeData().then((res) => {
        setTreeData(res.data.commonTreeData);
        setToTreeData(res.data.workflowRelateTreeData);
        fetchData();
      });

      
    } catch (error) {
      console.log('error:', error);
    }
  }, [id]);
  const { SHOW_PARENT } = TreeSelect;

  const tProps = {
    treeData,
    treeCheckable: true,
    showCheckedStrategy: SHOW_PARENT,
    placeholder: 'Please select',
    style: {
      width: '100%',
    },
  };

  const toTProps = {
    treeData : toTreeData,
    treeCheckable: true,
    showCheckedStrategy: SHOW_PARENT,
    onChange: (val)=> {
      if (val.includes('nextActionPerformer')) {
        setIsSelectNextActionPerformer(true);
      } else {
        setIsSelectNextActionPerformer(false);
      }

    },
    placeholder: 'Please select',
    style: {
      width: '100%',
    },
  };

  return (
    <Access
      accessible={hasPermitted('email-template-read-write')}
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
          <Col offset={1} span={24}>
            <Form
              form={form}
              layout="vertical"
              initialValues={{
                formName: '',
                alertName: '',
                description: '',
                status: '',
                from: [],
                to: [],
                cc: [],
                bcc: [],
                subject: '',
                notificationType: 'actionBased',
                actionId: 'ww',
                date: 'birthdays',
                frequency: 'once',
                reminderType: 'onTheSameDate',
                reminderValue: 0,
                emailMessage: 'newTemplate',
                content: '',
              }}
              name="control-hooks"
              onFinish={onFinish}
            >
              <Col span={16}>
                <Row gutter={16}>
                  <Col span={12}>
                    <Form.Item name="formName" label="Form Name" 
                      rules={[
                        { 
                          required: true,
                          message: intl.formatMessage({
                            id: 'formName',
                            defaultMessage: 'Required',
                          }),
                        }
                      ]}
                    >
                      <Select onChange={(val) => {
                          
                          if (val == 'workflow') {
                            setIsWorkflowTemplate(true);
                            setSelectedNotificationType('actionBased');
                            form.setFieldsValue({
                              notificationType: 'actionBased',
                            });
                            setIsActionBased(true);
                            
                          } else {
                            setIsWorkflowTemplate(false);
                            setSelectedNotificationType('dateBased');
                            form.setFieldsValue({
                              notificationType: 'dateBased',
                            });
                            setIsActionBased(false);
                          }
                      }}>
                        <Select.Option value="workflow">Workflow</Select.Option>
                        <Select.Option value="other1">Other</Select.Option>
                      </Select>
                    </Form.Item>
                  </Col>
                  <Space />
                  <Col span={12}>
                    {
                      isWorkflowTemplate ? (
                        <Form.Item
                          name="workflowContextId"
                          label="Workflow Context"
                          rules={[
                            {
                              required: true,
                              message: 'Required',
                            },
                          ]}
                        >
                          <ProFormSelect
                            name="select"
                            // options={selectorEmployees}
                            fieldProps={{
                              optionItemRender(item) {
                                return item.label;
                              },
                              onChange: async (value) => {
                                const tokenData = await getWorkflowTemplateTokens({'workflowContextId': value});
                                setTemplateTokens(tokenData.data);

                                if (value) {
                                  getContextBaseActions(value);
                                } else {
                                  setFilteredActionData([]);
                                }

                                const contentTemplateData = await getEmailTemplateContentsByContextId({contextId: value});
                                setContentTemplates(contentTemplateData.data);
                              },
                            }}
                            request={getContextList}
                            placeholder="Select Workflow Context"
                            style={{ marginBottom: 0 }}
                          />
                        </Form.Item>
                      ) : (
                        <></>
                      )
                    }
                  </Col>
                </Row>
                <Col span={24}>
                  <Form.Item name="alertName" label="Alert Name"
                    rules={[
                      { 
                        required: true ,
                        message: intl.formatMessage({
                          id: 'alertName',
                          defaultMessage: 'Required',
                        }),
                      },
                      {
                        max: 100,
                        message: intl.formatMessage({
                          id: 'roleName',
                          defaultMessage: 'Maximum length is 100 characters.',
                        }),
                      },
                      {
                        pattern: /^\w+((?!\s{2}).)*$/,
                        message:'Cannot contain more than one space' 
                      }
                    ]}
                  >
                    <Input />
                  </Form.Item>
                </Col>
                <Col span={24}>
                  <Form.Item name="description" label="Description" 
                    rules={[
                      {
                        max: 256,
                        message: intl.formatMessage({
                          id: 'roleName',
                          defaultMessage: 'Maximum length is 256 characters.',
                        }),
                      },
                      {
                        pattern: /^\w+((?!\s{2}).)*$/,
                        message:'Cannot contain more than one space' 
                      }
                    ]}
                  >
                   <TextArea rows={4} />
                  </Form.Item>
                </Col>

                <Col span={8}>
                  <Form.Item name="status" label="Status">
                    {form.getFieldValue('status') ? (
                      <Switch checkedChildren="Enable" unCheckedChildren="Disable" defaultChecked />
                    ) : (
                      <Switch checkedChildren="Enable" unCheckedChildren="Disable" />
                    )}
                  </Form.Item>
                </Col>
                {
                    isWorkflowTemplate ? (
                      <></>
                    ) : (

                    <Form.Item name="from" label="From" 
                      rules={[
                        { 
                          required: true,
                          message:intl.formatMessage({
                            id: 'from',
                            defaultMessage: 'Required',
                          })
                        }
                      ]}
                    >
                      <TreeSelect  {...tProps} />
                    </Form.Item>
                    )
                  }

                {
                  isWorkflowTemplate ? (
                    <Row gutter={16}>
                        <Col span={12}>
                          <Form.Item name="to" label="To " 
                            rules={[
                              { 
                                required: true ,
                                message:intl.formatMessage({
                                  id: 'to',
                                  defaultMessage: 'Required',
                                })
                              }
                            ]}
                          >
                            {
                              <TreeSelect value={form.to} {...toTProps} />
                            }
                          </Form.Item>
                        </Col>
                        {
                          isSelectNextActionPerformer ? (
                            <Col span={12}>
                              <ProFormSelect
                                  valuePropName="option"
                                  mode="multiple"
                                  name="nextPerformActions"
                                  value={nextPerformActions}
                                  label="Next Perform Actions"
                                  options={filteredActionData}
                                  // request={async () => {
                                  //   const data = await queryActionData({});
                                  //   return data.data.map((value : any) => {
                                  //     const aName = value.actionName;
                                  //     const actionid = value.id;
                                  //     return {
                                  //       label: aName,
                                  //       value: actionid,
                                  //     };
                                  //   });
                                  // }}
                                  fieldProps={{
                                      onChange: (value) => {
                                        setNextPerformActions(value);
                                      },

                                  }}
                                  rules={[
                                    { 
                                      required: true ,
                                      message:intl.formatMessage({
                                        id: 'to',
                                        defaultMessage: 'Required',
                                      })
                                    }
                                  ]}
                                  placeholder="Select an Action"
                              /> 
                            </Col>
                          ) : (
                            <></>
                          )
                        }
                    </Row>
                  ) : (
                    <Form.Item name="to" label="To " 
                      rules={[
                        { 
                          required: true ,
                          message:intl.formatMessage({
                            id: 'to',
                            defaultMessage: 'Required',
                          })
                        }
                      ]}
                    >
                      <TreeSelect {...tProps} />
                    </Form.Item>
                  )
                }
                
                <Form.Item name="cc" label="Cc">
                  <TreeSelect {...tProps} />
                </Form.Item>

                <Form.Item name="bcc" label="Bcc">
                  <TreeSelect {...tProps} />{' '}
                </Form.Item>

                <Form.Item name="subject" label="Subject" 
                  rules={[
                    { 
                      required: true ,
                      message:intl.formatMessage({
                        id: 'subject',
                        defaultMessage: 'Required',
                      })
                    },
                    {
                      pattern: /^\w+((?!\s{2}).)*$/,
                      message:'Cannot contain more than one space' 
                    }
                  ]}
                >
                  <Input />
                </Form.Item>
                <Form.Item
                  name="notificationType"
                  label="Notification Type"
                >
                  <Radio.Group defaultValue={'actionBased'} value={selectedNotificationType} onChange={notificationTypeOnChange}>
                    <Space direction="vertical">
                      <Col offset={4}>
                        <Radio disabled={true} value="actionBased">Action Based</Radio>
                        <Radio disabled={true} value="dateBased">Date Based</Radio>
                      </Col>
                    </Space>
                  </Radio.Group>
                </Form.Item>
                {isActionBased ? (
                    <ProFormSelect
                      valuePropName="option"
                      value= {selectedActionId}
                      name="actionId"
                      label="Action"
                      options={filteredActionData}
                      fieldProps={{
                          onChange: (value) => {
                              setSelectedActionId(value);
                          }
                      }}
                      placeholder="Select an Action"
                      rules={[
                        { 
                          required: true,
                          message: intl.formatMessage({
                            id: 'actionId',
                            defaultMessage: 'Required',
                          }) 
                        }
                      ]}
                  />                  
                ) : (
                  <>
                    <Col span={16}>
                      <Form.Item name="date" label="Date" 
                        rules={[
                          { 
                            required: true,
                            message: 'Required' 
                          }
                        ]}
                      >
                        <Select>
                          <Select.Option value="birthdays">Employee Birthday</Select.Option>
                          <Select.Option value="anniversary">Employee Anniversary</Select.Option>
                        </Select>
                      </Form.Item>
                    </Col>
                    <Col span={16}>
                      <Form.Item name="frequency" label="Frequency" 
                        rules={[
                          { 
                            required: true,
                            message: 'Required' 
                          }
                        ]}
                      >
                        <Select
                          onChange={(e) => {
                            setNotificationFrequency(e);
                          }}
                        >
                          <Select.Option value="once">Once</Select.Option>
                          <Select.Option value="monthly">Monthly</Select.Option>
                          <Select.Option value="annually">Annually</Select.Option>
                        </Select>
                      </Form.Item>
                    </Col>

                    {renderWhenTo(notificationFrequency)}
                  </>
                )}

                <Form.Item name="emailMessage" label="Email Message" 
        
                >
                  <Radio.Group defaultValue={'newTemplate'} onChange={emailMessageOnChange}>
                    <Space direction="vertical">
                      <Col offset={3}>
                        <Radio disabled = {id ? true : false} value="newTemplate">Create New Template</Radio>
                        <Radio value="existingTemplate">Select Existing Template</Radio>
                      </Col>
                    </Space>
                  </Radio.Group>
                </Form.Item>
                {isTemplateExist ? (
                  <>
                    <Form.Item name="contentId" label="Template">
                      <Select onSelect={contentTypeOnChange} >
                        {contentTemplates.map((value) => {
                          const aName = value.templateName;
                          const contentId = value.id;
                          return <Select.Option value={contentId}>{aName}</Select.Option>;
                        })}
                      </Select>
                    </Form.Item>
                  </>
                ) : (
                  <>
                    <Form.Item name="templateName" label="Template Name"
                      rules={[
                        { 
                          required: true ,
                          message:intl.formatMessage({
                            id: 'templateName',
                            defaultMessage: 'Required',
                          })
                        },
                        {
                          pattern: /^\w+((?!\s{2}).)*$/,
                          message:'Cannot contain more than one space' 
                        }
                      ]}  
                    >
                      <Input />
                    </Form.Item>
                  </>
                )}
              </Col>
              <Col span={24}>
                <Divider style={{ width: '100%' }} orientation="left">
                  Customize Email Content{' '}
                </Divider>
              </Col>
              <Form.Item label="Content">
                <Editor
                  apiKey={TINY_API_KEY}
                  key={key}
                  onInit={(evt, editor) => (editorRef.current = editor)}
                  initialValue={content}
                  init={editorInit}
                />
              </Form.Item>
              <Row>
                <Col span={24} style={{ textAlign: 'right' }}>
                  <Form.Item>
                    <Space>
                      <Button
                        htmlType="button"
                        onClick={() => {
                          history.push(`/settings/email-notifications`);
                        }}
                      >
                        Back
                      </Button>
                      <Button type="primary" htmlType="submit">
                        Save
                      </Button>
                    </Space>
                  </Form.Item>
                </Col>
              </Row>
            </Form>
          </Col>
        </Card>
      </PageContainer>
    </Access>
  );
};
