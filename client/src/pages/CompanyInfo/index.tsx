import React, { useState, useEffect } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import { FooterToolbar, PageContainer } from '@ant-design/pro-layout';
import { FormattedMessage, useIntl } from 'umi';
import _ from 'lodash';
import { Button, Card, Col, Form, Input, message, Popconfirm, Row, Space, Spin, Upload } from 'antd';
import { getCompany, storeCompanyImages, updateCompany } from '@/services/company';
import { canDeleteEntity } from '@/services/department';
import ProForm from '@ant-design/pro-form';
import FormInput from '@/components/FormInput';
import './index.css';
import { SubmitterProps } from '@ant-design/pro-form/lib/components/Submitter';
import { APIResponse } from '@/utils/request';
import moment from 'moment';
import { MinusCircleOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import ColorPicker from '@/components/ColorPicker';
import { getBase64 } from '@/utils/fileStore';

moment.locale('en-gb');

const EditCompany: React.FC = () => {
  const intl = useIntl();
  const [companyFormRef] = Form.useForm();

  const [companyModel, setCompanyModel] = useState<ModelType>();
  const [values, setValues] = useState({});
  const [recentlyChangedValue, setRecentlyChangedValue] = useState({});
  const [companyData, setCompanyData] = useState();
  const [submitting, setSubmitting] = useState<boolean>(false);
  const [loading, setloading] = useState(false);
  const [iconImgList, setIconImgList] = useState([]);
  const [coverImgList, setCoverImgList] = useState([]);

  useEffect(() => {
    if (!companyModel) {
      getModel(Models.Company).then((model) => {
        if (model && model.data) {
          setCompanyModel(model.data);
        }
      });
    }
  }, []);

  useEffect(() => {
    fetchCompanyData();
  }, []);

  const fetchCompanyData = async () => {
    getCompany().then((response) => {
      if (response && response.data) {
        const data = response.data;
        covertMonthToDateString(data);
        const entries = data.levels ? Object.keys(data.levels).map((level) => {
          return {
            level: `Level ${level.substring(5)}`,
            name: data.levels[level],
          };
        }) : [];

        companyFormRef.setFieldsValue({
          ...data,
          levels: entries,
        });
        const { coverImage, iconImage } = data;

        if (iconImage) {
          const { data } = iconImage;
          iconImage.thumbUrl = data;
          setIconImgList([iconImage]);
        }

        if (coverImage) {
          const { data } = coverImage;
          coverImage.thumbUrl = data;
          setCoverImgList([coverImage]);
        }

        setValues(data);
        setCompanyData(data);
      }
    });
  };

  const covertMonthToDateString = (companyData: any) => {
    if (companyData['leavePeriodStartingMonth'] && companyData['leavePeriodEndingMonth']) {
      // setting the leave period starting month date string
      let leavePeriodStartingMonthSymbol = moment.monthsShort(
        companyData['leavePeriodStartingMonth'] - 1,
      );
      let leavePeriodStartDate = Date.parse(leavePeriodStartingMonthSymbol + '1,2022');
      let leavePeriodStartDateString = new Date(leavePeriodStartDate);
      companyData['leavePeriodStartingMonth'] = moment(leavePeriodStartDateString).format(
        'YYYY-MM-DD',
      );

      // setting the leave ending period month date string
      let leavePeriodEndingMonthSymbol = moment.monthsShort(
        companyData['leavePeriodEndingMonth'] - 1,
      );
      let leavePeriodEndDate = Date.parse(leavePeriodEndingMonthSymbol + '1,2022');
      let leavePeriodEndDateString = new Date(leavePeriodEndDate);
      companyData['leavePeriodEndingMonth'] = moment(leavePeriodEndDateString).format('YYYY-MM-DD');
    }
  };

  const submitForm = () => {
    const key = 'updating';
    message.loading({
      content: intl.formatMessage({
        id: 'updating',
        defaultMessage: 'Updating...',
      }),
      key,
    });

    values.leavePeriodStartingMonth = moment(values.leavePeriodStartingMonth).month() + 1;
    values.leavePeriodEndingMonth = moment(values.leavePeriodEndingMonth).month() + 1;

    setSubmitting(true);

    const levelData = companyFormRef.getFieldValue('levels').map((levelObj) => {
      const keyLabel = `level${levelObj.level.substring(6)}`;
      return {
        [keyLabel]: levelObj.name,
      };
    });
    const levelObj = Object.assign({}, ...levelData);
    const formValues = { ...values, levels: levelObj };
    updateCompany(formValues.id, formValues)
      .then((response: APIResponse) => {
        if (response.error) {
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToUpdate',
                defaultMessage: 'Failed to Update',
              }),
            key,
          });
          if (response.data && Object.keys(response.data).length !== 0) {
            for (const feildName in response.data) {
              const errors = response.data[feildName];
              companyFormRef.setFields([
                {
                  name: feildName,
                  errors: errors,
                },
              ]);
            }
          }
          return;
        }
        setSubmitting(false);
        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullyUpdated',
              defaultMessage: 'Successfully updated',
            }),
          key,
        });
      })
      .catch((error: APIResponse) => {
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'failedToUpdate',
              defaultMessage: 'Failed to update',
            }),
          key,
        });
        setSubmitting(false);
      });
  };

  const customSubmitter: SubmitterProps = {
    render: (props, dom) => (
      <>
        <FooterToolbar>
          <Popconfirm
            key="reset"
            title={intl.formatMessage({
              id: 'are_you_sure',
              defaultMessage: 'Are you sure?',
            })}
            onConfirm={() => {
              companyFormRef.setFieldsValue(companyData);
              setValues(companyData);
            }}
            okText="Yes"
            cancelText="No"
          >
            <Button>
              <FormattedMessage id="RESET" defaultMessage="Reset" />
            </Button>
          </Popconfirm>
          <Button type="primary" key="submit" loading={submitting} onClick={submitForm}>
            <FormattedMessage id="UPDATE" defaultMessage="Update" />
          </Button>
        </FooterToolbar>
      </>
    ),
  };

  const handleChange = async (info, imgType: string) => {
    try {
      const data = await getBase64(info.file.originFileObj);
      const reqData = {
        fileName: info.file.name,
        fileSize: info.file.size,
        data,
      };

      const response = await storeCompanyImages(imgType, reqData);
      console.log(response);
      let fileList = [...info.fileList];
      // 1. Limit the number of uploaded files
      // Only to show two recent uploaded files, and old ones will be replaced by the new
      fileList = fileList.slice(-1);
      // 2. Read from response and show file link
      fileList = fileList.map((file) => {
        file.status = 'success';
        return file;
      });
      imgType === 'icon' ? setIconImgList(fileList) : setCoverImgList(fileList);
    } catch (error) {
      console.error(error);
    }
  };

  const handleOnEntityDelete = async (key: number, callback: any) => {
    try {
      const level = `level${key + 1}`;
      const response = await canDeleteEntity(level);
      const { data } = response;
      if (data.canDelete) {
        callback(key);
      } else {
        level != 'level1'
          ? message.error('The organization level is already in use')
          : message.error('Can\'t delete organization Level 1');
      }
    } catch (error) {
      console.error(error);
    }
  }

  const beforeUpload = (file: RcFile) => {
    const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png';
    if (!isJpgOrPng) {
      message.error('You can only upload JPG/PNG file!');
    }
    const isLt2M = file.size / 1024 / 1024 < 2;
    if (!isLt2M) {
      message.error('Image must smaller than 2MB!');
    }
    return isJpgOrPng && isLt2M;
  };

  return (
    <PageContainer>
      {_.isUndefined(companyModel) ? (
        <Spin />
      ) : (
        <ProForm
          form={companyFormRef}
          onValuesChange={setRecentlyChangedValue}
          submitter={customSubmitter}
        >
          <Card
            title={intl.formatMessage({
              id: 'general_info_card',
              defaultMessage: 'General Information',
            })}
            className="general-info-card"
          >
            <Row>
              <FormInput
                key="name"
                fieldName="name"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="taxCode"
                fieldName="taxCode"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="registrationNo"
                fieldName="registrationNo"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="rootEmployee"
                fieldName="rootEmployee"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
          </Card>
          <Card
            title={intl.formatMessage({
              id: 'contact_and_address_card',
              defaultMessage: 'Contact and Address',
            })}
            className="contact-card"
          >
            <Row>
              <FormInput
                key="phone"
                fieldName="phone"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="fax"
                fieldName="fax"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="email"
                fieldName="email"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="street1"
                fieldName="street1"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="street2"
                fieldName="street2"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="city"
                fieldName="city"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="country"
                fieldName="country"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="stateProvince"
                fieldName="stateProvince"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="zipCode"
                fieldName="zipCode"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />

              <FormInput
                key="timeZone"
                fieldName="timeZone"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
            <Row>
              <FormInput
                key="notes"
                fieldName="notes"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
          </Card>
          <Card
            title={intl.formatMessage({
              id: 'leave_config_card',
              defaultMessage: 'Leave Configuration',
            })}
            className="leave-config-card"
          >
            <Row>
              <FormInput
                key="leavePeriodStartingMonth"
                fieldName="leavePeriodStartingMonth"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
              <FormInput
                key="leavePeriodEndingMonth"
                fieldName="leavePeriodEndingMonth"
                model={companyModel}
                form={companyFormRef}
                values={values}
                setValues={setValues}
                recentlyChangedValue={recentlyChangedValue}
              />
            </Row>
          </Card>
          <Card
            title={intl.formatMessage({
              id: 'org_level_config_card',
              defaultMessage: 'Organization Level Configuration',
            })}
            className="org-level-config-card"
          >
            <Row>
              <Col span={8}>
                <Form.List name="levels">
                  {(fields, { add, remove }) => (
                    <>
                      {fields.map(({ key, name, ...restField }) => (
                        <Row gutter={[8, 8]}>
                          <Col span={11}>
                            <Form.Item
                              {...restField}
                              name={[name, 'level']}
                              rules={[{ required: true, message: 'Missing Entity Level' }]}
                            >
                              <Input placeholder="Entity Level" disabled />
                            </Form.Item>
                          </Col>
                          <Col span={11}>
                            <Form.Item
                              {...restField}
                              name={[name, 'name']}
                              rules={[{ required: true, message: 'Missing Entity Name' }]}
                            >
                              <Input placeholder="Entity Name" />
                            </Form.Item>
                          </Col>
                          <Col span={2}>
                            <Button
                              danger
                              icon={<MinusCircleOutlined />}
                              disabled={fields.length - 1 != name}
                              onClick={() => {
                                handleOnEntityDelete(name, remove);
                              }}
                            />
                          </Col>
                        </Row>
                        // {/* </Space> */}
                      ))}
                      <Form.Item>
                        <Button
                          type="dashed"
                          onClick={() => {
                            const levelData = companyFormRef.getFieldValue('levels');
                            add({ level: `Level ${levelData.length + 1}` }, levelData.length + 1);
                          }}
                          block
                          icon={<PlusOutlined />}
                        >
                          Add Entity Level
                        </Button>
                      </Form.Item>
                    </>
                  )}
                </Form.List>
              </Col>
            </Row>
          </Card>
          <Card
            title={intl.formatMessage({
              id: 'theme_config_card',
              defaultMessage: 'Theme',
            })}
            className="theme-config-card"
          >
            <Row>
              <ColorPicker
                fieldName="primaryColor"
                label="Primary Color"
                onChange={(primaryColor) => {
                  setValues({ ...values, primaryColor });
                }}
                value={values.primaryColor}
                readOnly={true}
              />
              <ColorPicker
                fieldName="textColor"
                label="Text Color"
                onChange={(textColor) => {
                  setValues({ ...values, textColor });
                }}
                value={values.textColor}
                readOnly={true}
              />
            </Row>
            <Row className='appThemeRow'>
              <Col span={10}>
                <Upload
                  listType="picture"
                  fileList={iconImgList}
                  maxCount={1}
                  beforeUpload={beforeUpload}
                  onChange={(info) => {
                    handleChange(info, 'icon');
                  }}
                  showUploadList={{ showRemoveIcon: false }}
                >
                  <Button icon={<UploadOutlined />}>Upload Application Icon</Button>
                </Upload>
              </Col>
              <Col span={2}></Col>
              <Col span={10}>
                <Upload
                  listType="picture"
                  fileList={coverImgList}
                  maxCount={1}
                  beforeUpload={beforeUpload}
                  onChange={(info) => {
                    handleChange(info, 'cover');
                  }}
                  showUploadList={{ showRemoveIcon: false }}
                >
                  <Button icon={<UploadOutlined />}>Upload Application Cover</Button>
                </Upload>
              </Col>
            </Row>
          </Card>
        </ProForm>
      )}
    </PageContainer>
  );
};

export default EditCompany;
