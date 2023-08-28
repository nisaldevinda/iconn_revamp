import React, { useState, useEffect, useRef } from 'react';
import ProForm, { ProFormRadio, ProFormSelect, ProFormText, ProFormUploadButton } from '@ant-design/pro-form';
import { PageContainer } from '@ant-design/pro-layout';
import { Card, Form, Typography, Transfer, Spin, message, Divider, Space, Button, Input, Upload } from 'antd';
import { useIntl, useParams, history, useAccess, Access } from 'umi';
import { Editor } from '@tinymce/tinymce-react';
import { apiKey, tokenizeEditorObj } from './editorHelper';
import { updateNotice, getNotice, getAllNotices, addNotice } from '@/services/notice';
import { getModel, Models } from "@/services/model";
import { genarateEmptyValuesObject } from '@/utils/utils';
import { getEmployeeList, getManagerList } from '@/services/dropdown';
import { getAllLocations } from '@/services/location';
import { queryCurrent } from '@/services/user';
import _ from 'lodash';
import PermissionDeniedPage from './../403';
import { getAllNoticeCategory, addNoticeCategory } from '@/services/noticeCategory';
import { PlusOutlined } from '@ant-design/icons';
import { UploadOutlined } from '@ant-design/icons';
import { getBase64 } from '@/utils/fileStore';

export type NoticeFormRouteParams = {
  id: string
};

const NoticeForm: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const editorRef = useRef(null);

  const { id } = useParams<NoticeFormRouteParams>();
  const { hasPermitted, hasAnyPermission } = access;
  const { Text } = Typography;
  const [form] = Form.useForm();

  const [newCategory, setNewCategory] = useState<string>();
  const [model, setModel] = useState();
  const [isEditForm, setIsEditForm] = useState(false);
  const [initializing, setInitializing] = useState(false);
  const [refreshing, setRefresing] = useState(false);
  const [notice, setNotice] = useState({});
  const [content, setContent] = useState<string>('');
  const [editorInit, setEditorInit] = useState<EditorProps>({ ...tokenizeEditorObj });
  const [audienceMethod, setAudienceMethod] = useState([]);
  const [adminEmployees, setAdminEmployees] = useState([]);
  const [managerEmployees, setManagerEmployees] = useState([]);
  const [managers, setManagers] = useState([]);
  const [locations, setLocations] = useState([]);
  const [targetKeys, setTargetKeys] = useState<string[]>([]);
  const [categories, setCategories] = useState<Array<{ label: number, value: string }>>([]);
  const [attachment, setAttachment] = useState<Object>();

  useEffect(() => {
    init();
  }, []);

  useEffect(() => {
    refresh();
  }, [id, model]);

  useEffect(() => {
    handleNoticeTypeChange();
  }, [notice?.type]);

  const init = async () => {
    setInitializing(true);

    if (!model) {
      await getModel(Models.Notice).then((_model) => {
        if (_model && _model.data) {
          setModel(_model.data);
        }
      });
    }

    if (hasPermitted('employee-read')) {
      const adminEmployeesRes = await getEmployeeList("ADMIN");
      setAdminEmployees(adminEmployeesRes?.data.map(employee => {
        return {
          title: employee.employeeNumber+' | '+employee.employeeName,
          key: employee.id
        };
      }));
    }

    // const managerEmployeesRes = await getEmployeeList("MANAGER");
    // setManagerEmployees(managerEmployeesRes?.data.map(employee => {
    //   return {
    //     label: employee.employeeName,
    //     value: employee.id
    //   };
    // }));

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

    const categoryRes = await getAllNoticeCategory();
    setCategories(Object.values(categoryRes?.data.map(category => {
      return {
        label: category.name,
        value: category.id
      };
    })));

    setInitializing(false);
  }

  const refresh = async () => {
    setRefresing(true);

    if (model) {
      let initialObject = genarateEmptyValuesObject(model);
      initialObject['type'] = !hasAnyPermission(['company-notice-read-write'])
        && hasAnyPermission(['team-notice-read-write'])
        ? 'TEAM_NOTICES'
        : 'COMPANY_NOTICES';
      setNotice(initialObject);
      form.setFieldsValue(initialObject);
    }

    if (id) {
      setIsEditForm(true);

      const response = await getNotice(id);
      const audienceMethod = response.data?.audienceMethod;
      const audienceData = JSON.parse(response.data?.audienceData);

      switch (audienceMethod) {
        case 'REPORT_TO':
          response.data.reportToManager = audienceData?.reportTo;
          response.data.queryLocation = null;
          break;
        case 'QUERY':
          response.data.queryLocation = audienceData?.locationId;
          response.data.reportToManager = null;
          break;
        case 'CUSTOM':
          setTargetKeys(audienceData?.employeeIds);
          break;
        default:
          response.data.audienceData = {};
          break;
      }

      setNotice(response.data);
      form.setFieldsValue(response.data);
      setContent(response.data?.description);
    } else {
      setIsEditForm(false);
    }

    setRefresing(false);
  }

  const handleNoticeTypeChange = async () => {
    if (notice?.type == 'COMPANY_NOTICES') {
      const _audienceMethod = [];
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ALL', defaultMessage: 'All' })}`, value: 'ALL' });
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ASSIGN_TO_MANAGER', defaultMessage: 'Assign To Manager' })}`, value: 'REPORT_TO' });
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'LOCATION', defaultMessage: 'Location' })}`, value: 'QUERY' });
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'CUSTOM', defaultMessage: 'Custom' })}`, value: 'CUSTOM' });
      setAudienceMethod(_audienceMethod);

      const isSelectedOptionInAudienceMethodList = _.isEmpty(_audienceMethod.filter(method => method.value == notice?.audienceMethod));
      if (isSelectedOptionInAudienceMethodList) {
        const _notice = { ...notice };
        _notice['audienceMethod'] = 'ALL';
        setNotice(_notice);
        form.setFieldsValue(_notice);
      }
    } else if (notice?.type == 'TEAM_NOTICES') {
      const _audienceMethod = [];
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ASSIGNED_TO_ME', defaultMessage: 'Assigned To Me' })}`, value: 'ASSIGNED_TO_ME' });
      _audienceMethod.push({ label: `${intl.formatMessage({ id: 'CUSTOM', defaultMessage: 'Custom' })}`, value: 'CUSTOM' });
      setAudienceMethod(_audienceMethod);

      const isSelectedOptionInAudienceMethodList = _.isEmpty(_audienceMethod.filter(method => method.value == notice?.audienceMethod));
      if (isSelectedOptionInAudienceMethodList) {
        const _notice = { ...notice };
        _notice['audienceMethod'] = 'ASSIGNED_TO_ME';
        setNotice(_notice);
        form.setFieldsValue(_notice);
      }
    }
  }

  const onFinish = async (formData: any) => {
    setRefresing(true);

    try {
      const _notice = { ...notice };

      if (_notice.contentType == 'ATTACHMENT') {
        _notice.description = null;
        _notice.attachment = attachment;
      } else {
        _notice.description = editorRef.current?.getContent();
        _notice.attachment = null;
      }

      switch (_notice.audienceMethod) {
        case 'REPORT_TO':
          _notice.audienceData = {
            reportTo: _notice.reportToManager
          };
          delete _notice.reportToManager;
          break;
        case 'QUERY':
          _notice.audienceData = {
            locationId: _notice.queryLocation
          };
          delete _notice.queryLocation;
          break;
        case 'CUSTOM':
          _notice.audienceData = {
            employeeIds: targetKeys
          };
          break;
        case 'ASSIGNED_TO_ME':
          const currentUser = await queryCurrent();
          const currentEmployeeId = currentUser?.data?.employeeId;
          _notice.audienceData = {
            reportTo: currentEmployeeId
          };
          break;
        default:
          _notice.audienceData = {};
          break;
      }

      const response = isEditForm ? await updateNotice(id, _notice) : await addNotice(_notice);
      message.success(response.message);
      history.goBack();
    } catch (error) {
      message.error(error.message);
    }

    setRefresing(false);
  }

  const addNewCategory = async () => {
    setRefresing(true);

    if (!newCategory) {
      setRefresing(false);
      return;
    }

    const newCategoryRes = await addNoticeCategory({
      name: newCategory
    });

    if (newCategoryRes.error) {
      message.error(newCategoryRes.message);
      setRefresing(false);
      return;
    }

    let newCategories = [
      ...categories,
      {
        label: newCategoryRes.data.name,
        value: newCategoryRes.data.id
      }
    ];

    setCategories(newCategories);
    setNewCategory(undefined);
    form.setFieldsValue({
      noticeCategoryId: newCategoryRes.data.id
    });

    setRefresing(false);
  }

  const beforeAttachmentUpload = (file) => {
    const isPDF = file.type === 'application/pdf';
    if (!isPDF) {
      message.error(intl.formatMessage({
        id: 'you_can_only_upload_pdf_file',
        defaultMessage: 'You can only upload PDF file!',
      }));
    }

    const isLt2M = file.size / 1024 / 1024 < 2;
    if (!isLt2M) {
      message.error(intl.formatMessage({
        id: 'file_must_smaller_than_2mb',
        defaultMessage: 'File must smaller than 2MB!',
      }));
    }

    return isPDF && isLt2M;
  }

  const onAttachmentChange = async (info: any) => {
    let status = info?.file?.status;

    if (status == 'done') {
      const data = await getBase64(info.file.originFileObj);
      setAttachment({
        fileName: info?.file?.name,
        fileSize: info?.file?.size,
        data
      });
    } else if (status == 'removed') {
      setAttachment(undefined);
    }
  };

  return (
    <Access
      accessible={hasAnyPermission(['company-notice-read-write']) || hasAnyPermission(['team-notice-read-write'])}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer
        extra={isEditForm &&
          <ProFormSelect
            showSearch
            request={async () =>
              getAllNotices().then((response) =>
                response.data?.map((notice: any) => {
                  return {
                    label: notice.topic,
                    value: notice.id,
                  };
                }),
              )
            }
            fieldProps={{
              value: !initializing && !refreshing ? notice?.id : '',
              onChange: (noticeId) => history.push(`/notices/${noticeId}`),
            }}
            label={intl.formatMessage({
              id: 'notice_quick_switch',
              defaultMessage: 'Notice Quick Switch',
            })}
          />
        }
      >
        <Card>
          <Spin spinning={initializing || refreshing}>
            <ProForm
              initialValues={notice}
              form={form}
              submitter={{
                searchConfig: {
                  submitText: isEditForm
                    ? intl.formatMessage({
                      id: 'update',
                      defaultMessage: 'Update',
                    })
                    : intl.formatMessage({
                      id: 'add',
                      defaultMessage: 'Add',
                    }),
                  resetText: intl.formatMessage({
                    id: 'reset',
                    defaultMessage: 'Reset',
                  }),
                },
              }}
              onValuesChange={() => setNotice(form.getFieldsValue())}
              onFinish={onFinish}
            >
              <ProFormText
                width="lg"
                name="topic"
                label={intl.formatMessage({
                  id: 'topic',
                  defaultMessage: 'Topic',
                })}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'topic',
                      defaultMessage: 'Required.',
                    })
                  },
                  {
                    max: 100,
                    message: intl.formatMessage({
                      id: 'name',
                      defaultMessage: 'Maximum length is 100 characters.',
                    })
                  }
                ]}
              />
              <ProFormSelect
                width="lg"
                name="noticeCategoryId"
                label={intl.formatMessage({
                  id: 'category',
                  defaultMessage: 'Category',
                })}
                options={categories}
                fieldProps={{
                  dropdownRender: (menu) => (
                    <>
                      {menu}
                      <Divider style={{ margin: '8px 0' }} />
                      <Space style={{ padding: '0 8px 4px' }}>
                        <Input
                          width="md"
                          value={newCategory}
                          onChange={(event) => setNewCategory(event.target.value)}
                          onPressEnter={addNewCategory}
                        />
                        <Button type="text" icon={<PlusOutlined />} onClick={addNewCategory}>
                          {intl.formatMessage({
                            id: 'add_new_notice_category',
                            defaultMessage: 'New',
                          })}
                        </Button>
                      </Space>
                    </>
                  )
                }}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'topic',
                      defaultMessage: 'Required.',
                    })
                  }
                ]}
              />
              <ProFormRadio.Group
                width="lg"
                name="type"
                label={intl.formatMessage({
                  id: 'NOTICE_TYPE',
                  defaultMessage: 'Notice Type',
                })}
                options={[
                  {
                    label: `${intl.formatMessage({
                      id: 'COMPANY_NOTICES',
                      defaultMessage: 'Company Notices',
                    })}`,
                    value: 'COMPANY_NOTICES',
                    disabled: !hasPermitted('company-notice-read-write')
                  },
                  {
                    label: `${intl.formatMessage({
                      id: 'TEAM_NOTICES',
                      defaultMessage: 'Team Notices',
                    })}`,
                    value: 'TEAM_NOTICES',
                    disabled: !hasPermitted('company-notice-read-write')
                  }
                ]}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'topic',
                      defaultMessage: 'Required.',
                    })
                  }
                ]}
              />
              <ProFormRadio.Group
                width="lg"
                name="contentType"
                label={intl.formatMessage({
                  id: 'CONTENT_CREATE_OTION',
                  defaultMessage: 'Content Create Option',
                })}
                options={[
                  {
                    label: `${intl.formatMessage({
                      id: 'TEXT_EDITOR',
                      defaultMessage: 'Use Text Editor',
                    })}`,
                    value: 'TEXT',
                  },
                  {
                    label: `${intl.formatMessage({
                      id: 'ATTACHMENT',
                      defaultMessage: 'Use Attachment',
                    })}`,
                    value: 'ATTACHMENT',
                  }
                ]}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'REQUIRED',
                      defaultMessage: 'Required.',
                    })
                  }
                ]}
              />
              {form.getFieldValue('contentType') == 'ATTACHMENT'
                ? <Form.Item
                  name="attachment"
                  label={intl.formatMessage({
                    id: 'attachment',
                    defaultMessage: 'Attachment',
                  })}
                  rules={[
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'topic',
                        defaultMessage: 'Required.',
                      })
                    }
                  ]}
                >
                  <Upload
                    accept='.pdf'
                    maxCount={1}
                    beforeUpload={beforeAttachmentUpload}
                    onChange={onAttachmentChange}
                    customRequest={({ onSuccess }) => onSuccess('ok')}
                  >
                    <Button icon={<UploadOutlined />}>
                      {intl.formatMessage({
                        id: 'upload_max_2mb_pdf',
                        defaultMessage: 'Upload (PDF, Max Size 2MB)',
                      })}
                    </Button>
                  </Upload>
                </Form.Item>
                : <Form.Item
                  label={intl.formatMessage({
                    id: 'content',
                    defaultMessage: 'Content',
                  })}
                  rules={[
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'topic',
                        defaultMessage: 'Required.',
                      })
                    }
                  ]}
                >
                  <Editor
                    apiKey={TINY_API_KEY}
                    initialValue={content}
                    init={editorInit}
                    onInit={(evt, editor) => (editorRef.current = editor)}
                  />
                </Form.Item>
              }
              <ProFormSelect
                width="lg"
                name="status"
                label={intl.formatMessage({
                  id: 'status',
                  defaultMessage: 'Approval Status',
                })}
                valueEnum={{
                  Unpublished: `${intl.formatMessage({
                    id: 'Unpublished',
                    defaultMessage: 'Unpublished',
                  })}`,
                  Published: `${intl.formatMessage({
                    id: 'Published',
                    defaultMessage: 'Published',
                  })}`,
                  Draft: `${intl.formatMessage({
                    id: 'Draft',
                    defaultMessage: 'Draft',
                  })}`,
                  Archived: `${intl.formatMessage({
                    id: 'Archived',
                    defaultMessage: 'Archived',
                  })}`
                }}
                rules={
                  [
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'topic',
                        defaultMessage: 'Required',
                      })
                    },
                  ]
                }
              />

              <Form.Item
                label={intl.formatMessage({
                  id: 'audience',
                  defaultMessage: 'Audience',
                })}
                required
              >
                <Text type="secondary">
                  {intl.formatMessage({
                    id: 'secondary.label',
                    defaultMessage: 'Select the employees you want the notice to be displayed.',
                  })}
                </Text>
              </Form.Item>

              <ProFormSelect
                width="lg"
                name="audienceMethod"
                rules={
                  [
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'topic',
                        defaultMessage: 'Required',
                      })
                    },
                  ]
                }
                options={audienceMethod}
              />

              {!initializing && notice?.audienceMethod == 'REPORT_TO' &&
                <ProFormSelect
                  width="lg"
                  name="reportToManager"
                  label={intl.formatMessage({
                    id: 'SELECT_A_MANAGER',
                    defaultMessage: 'Select a Manager',
                  })}
                  options={managers}
                  fieldProps={{
                    mode: 'multiple',
                  }}
                  rules={
                    [
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'topic',
                          defaultMessage: 'Required',
                        })
                      },
                    ]
                  }
                />
              }

              {!initializing && notice?.audienceMethod == 'QUERY' &&
                <ProFormSelect
                  width="lg"
                  name="queryLocation"
                  label={intl.formatMessage({
                    id: 'SELECT_A_LOCATION',
                    defaultMessage: 'Select a Location',
                  })}
                  options={locations}
                  rules={
                    [
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'topic',
                          defaultMessage: 'Required',
                        })
                      },
                    ]
                  }
                />
              }

              {!initializing && notice?.audienceMethod == 'CUSTOM' &&
                <Transfer
                  dataSource={adminEmployees}
                  showSearch
                  filterOption={(search, item) => { return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0; }}
                  targetKeys={targetKeys}
                  onChange={(newTargetKeys: string[]) => {
                    setTargetKeys(newTargetKeys);
                  }}
                  render={item => item.title}
                  listStyle={{
                    width: 300,
                    height: 300,
                    marginBottom: 20
                  }}
                />
              }

            </ProForm>
          </Spin>
        </Card>
      </PageContainer>
    </Access>
  );
};

export default NoticeForm;
