import React, { useEffect, useState } from 'react';
import {
  Button,
  Card,
  Col,
  Input,
  Radio,
  Row,
  Upload,
  TimePicker,
  message,
  Form,
  Popconfirm,
  Typography,
  DatePicker,
} from 'antd';
import { FormattedMessage } from 'react-intl';
import ProCard from '@ant-design/pro-card';
import { useIntl, history } from 'umi';
import moment from 'moment';
import { FieldTimeOutlined, UploadOutlined } from '@ant-design/icons';
import {
  addShortLeave,
  calculateWorkingDaysCountForShortLeave,
  getShiftData,
} from '@/services/leave';
import { getBase64 } from '@/utils/fileStore';
import '../style.css';
import _ from 'lodash';
import { ModalForm } from '@ant-design/pro-form';
import LeaveHistoryList from './employeeLeaveHistoryList';

const ApplyShortLeave: React.FC = () => {
  const [shiftData, setShiftData] = useState<any>(null);
  const [shortLeaveDate, setShortLeaveDate] = useState<Date | null>(null);
  const [fromTime, setFromTime] = useState<string | null>(null);
  const [toTime, setToTime] = useState<string | null>(null);
  const [radioVal, setRadioVal] = useState<any | null>(1);
  const [shortleaveType, setShortLeaveType] = useState<string | null>('IN_SHORT_LEAVE');
  const [selectedleaveTypeObject, setSelectedLeaveTypeObject] = useState({
    fullDayAllowed: true,
    halfDayAllowed: true,
    shortLeaveAllowed: false,
  });
  const [isAttachementMandatory, setIsAttachementMandatory] = useState<boolean>(false);
  const [canShowAttachement, setCanShowAttachement] = useState<boolean>(true);
  const [isWorkingDay, setIsWorkingDay] = useState<boolean>(false);
  const [leaveReason, setLeaveReason] = useState<string | null>(null);
  const [attachmentList, setAttachmentList] = useState<any>([]);
  const [shortLeaveDuration, setShortLeaveDuration] = useState<any>(90);
  const [hourCount, setHourCount] = useState<number | null>();
  const [fileFormatError, setfileFormatError] = useState(false);
  const [form] = Form.useForm();
  const intl = useIntl();
  const [listModalVisible, handleListModalVisible] = useState<boolean>(false);
  const { Text } = Typography;
  const [showCount, setShowCount] = useState(false);

  useEffect(() => {
    if (
      (shortleaveType == 'IN_SHORT_LEAVE' || shortleaveType == 'OUT_SHORT_LEAVE') &&
      shortLeaveDate
    ) {
      calculateWorkingDaysCount();
    }
  }, [shortLeaveDate]);

  const getShiftDataForLeaveDate = async (periodType: any) => {
    try {
      const res = await getShiftData(shortLeaveDate, shortLeaveDate);

      if (res.data.shift === null) {
        let msg = 'There is no any related shift for your selected date.';
        setShowCount(false);
        return message.error(msg);
      }

      setShiftData(res.data.shift);
      let shortLeaveDefaultDuration =
        res.data.shift !== null ? res.data.shift.short_leave_duration : null;
      if (res.data.shift !== null) {
        setShortLeaveDuration(res.data.shift.short_leave_duration);
      } else {
        setShortLeaveDuration(null);
      }

      form.setFieldsValue({ timeRange: null });

      if (
        periodType == 'IN_SHORT_LEAVE' &&
        res.data.shift != null &&
        shortLeaveDate &&
        shortLeaveDefaultDuration !== null
      ) {
        let time = moment(res.data.shift.startTime, 'HH:mm')
          .add(shortLeaveDefaultDuration, 'minutes')
          .format('HH:mm');

        let toTime = shortLeaveDefaultDuration ? moment(time, 'HH:mm') : null;
        let timeRangeArr = [moment(res.data.shift.startTime, 'HH:mm'), toTime];
        calculateHourCount(timeRangeArr);
        form.setFieldsValue({ timeRange: timeRangeArr });
        setToTime(time);
        setFromTime(res.data.shift.startTime);
      }

      if (
        periodType == 'OUT_SHORT_LEAVE' &&
        res.data.shift != null &&
        shortLeaveDate &&
        shortLeaveDefaultDuration !== null
      ) {
        let time = moment(res.data.shift.endTime, 'HH:mm')
          .subtract(shortLeaveDefaultDuration, 'minutes')
          .format('HH:mm');
        let fromTime = shortLeaveDefaultDuration ? moment(time, 'HH:mm') : null;

        let timeRangeArr = [fromTime, moment(res.data.shift.endTime, 'HH:mm')];

        form.setFieldsValue({ timeRange: timeRangeArr });
        setToTime(res.data.shift.endTime);
        setFromTime(time);
      }
    } catch (error) {
      console.log('error:', error);
    }

  };

  const calculateHourCount = async (timeRangeArr) => {
    let fromTime = timeRangeArr[0];
    let toTime = timeRangeArr[1];

    let duration = moment.duration(toTime.diff(fromTime));
    let hours = duration.asHours();
    setHourCount(hours);
  };

  const uploaderProps = {
    beforeUpload: (file) => {
      const isValidFormat = file.type === 'image/jpeg' || file.type === 'application/pdf';
      if (!isValidFormat) {
        message.error('File format should be JPG or PDF');
      }
      return isValidFormat || Upload.LIST_IGNORE;
    },
    onChange({ file, fileList }) {
      if (file.status !== 'uploading') {
        form.setFieldsValue({ upload: fileList });
        setAttachmentList(fileList);
        setfileFormatError(false);
      }
      // for handle error
      if (file.status === 'error') {
        const { uid } = file;
        const index = fileList.findIndex((file: any) => file.uid == uid);
        const newFile = { ...file };
        if (index > -1) {
          newFile.status = 'done';
          newFile.percent = 100;
          delete newFile.error;
          fileList[index] = newFile;
          setAttachmentList([...fileList]);
        }
      }
    },
  };

  const changeTimeRange = (ranges: object) => {
    if (ranges != null) {
      setShowCount(true);
      setFromTime(ranges[0].format('HH:mm'));
      setToTime(ranges[1].format('HH:mm'));
      calculateHourCount(ranges);
    } else {
      setShowCount(false);
      setFromTime(null);
      setToTime(null);
      setHourCount(null);
    }
  };

  const showListModal = async (event) => {
    await handleListModalVisible(true);
  };

  const calculateWorkingDaysCount = async () => {
    if (shortLeaveDate) {
      const res = await calculateWorkingDaysCountForShortLeave(shortLeaveDate);
      if (res.data.isWorkingDay) {
        setIsWorkingDay(true);
        getShiftDataForLeaveDate(shortleaveType);
      } else {
        setShowCount(false);
        setIsWorkingDay(false);
        form.setFields([
          {
            name: 'date',
            errors: ['select working day'],
          },
        ]);
      }
    } else {
      // setWorkingDaysCount(null);
    }
  };

  const handleDateChange = (value) => {
    if (value != null) {
      setShowCount(true);
      let date = !_.isNull(value) && !_.isUndefined(value) ? value.format('YYYY-MM-DD') : null;

      setShortLeaveDate(date);
    } else {
      setShowCount(false);
      setShortLeaveDate(null);
      form.setFieldsValue({ timeRange: [null, null] });
    }
  };

  const applyLeave = async () => {
    try {
      if (fileFormatError) {
        message.error('File format should be JPG or PDF');
      } else {
        if (!isWorkingDay) {
          form.setFields([
            {
              name: 'date',
              errors: ['select working day'],
            },
          ]);
          return;
        }

        await form.validateFields();
        const selectedAttachment: Array<object> = [];

        if (shiftData === null) {
          let msg = 'There is no any related shift for your selected date.';
          setShowCount(false);
          return message.error(msg);
        }

        if (canShowAttachement) {
          for (let index = 0; index < attachmentList.length; index++) {
            const base64File = await getBase64(attachmentList[index].originFileObj);
            selectedAttachment[index] = {
              fileName: attachmentList[index].name,
              fileSize: attachmentList[index].size,
              data: base64File,
            };
          }
        }

        if (fromTime && toTime) {
          let ftime = moment(fromTime, 'HH:mm');
          let ttime = moment(toTime, 'HH:mm');

          let duration = moment.duration(ttime.diff(ftime));

          let diffFromMin = duration.asMinutes();

          if (diffFromMin > shortLeaveDuration) {
            let hrs = shortLeaveDuration / 60;
            let msg = 'Short leave duration cannot exceed ' + hrs + ' hours.';
            return message.error(msg);
          }
        }

        const params: any = {
          shortLeaveType: shortleaveType,
          date: shortLeaveDate,
          fromTime: fromTime,
          toTime: toTime,
          reason: leaveReason,
          numberOfMinutes: (hourCount * 60).toString(),
          attachmentList: selectedAttachment,
          isGoThroughWf: true,
        };

        const result = await addShortLeave(params);
        message.success(result.message);
        history.push('/ess/my-requests');
      }
    } catch (error) {
      if (!_.isEmpty(error)) {
        let errorMessage;
        let errorMessageInfo;

        if (error.message && error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        if (error.message) {
          message.error({
            content: error.message ? (
              <>
                {errorMessage ?? error.message}
                <br />
                <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                  {errorMessageInfo ?? ''}
                </span>
              </>
            ) : (
              <></>
            ),
          });
        }
      }
    }
  };

  const changeLeavePeriod = (event) => {
    setRadioVal(event);
    switch (event) {
      case 1:
        setShortLeaveType('IN_SHORT_LEAVE');
        if (shortLeaveDate) {
          getShiftDataForLeaveDate('IN_SHORT_LEAVE');
        }
        break;
      case 2:
        setShortLeaveType('OUT_SHORT_LEAVE');
        if (shortLeaveDate) {
          getShiftDataForLeaveDate('OUT_SHORT_LEAVE');
        }
        break;
      default:
        break;
    }
  };

  const initValues: object = {
    leaveType: null,
    date: null,
    timeRange: null,
    shortleaveType: 1,
    reason: '',
  };

  return (
    <>
      <div>
        {/* <PageContainer
            header={{
              ghost: true,
            }}
          > */}
        <ProCard
          direction="column"
          ghost
          gutter={[0, 16]}
          style={{ padding: 0, margin: 0, height: '100%' }}
        >
          <Row style={{ width: '100%' }} gutter={16}>
            <Col flex="auto" order={2} xl={{ order: 1 }}>
              <Card
                title={intl.formatMessage({
                  id: 'shortLeaveTitle',
                  defaultMessage: 'Short Leave',
                })}
                extra={
                  <Button
                    type="primary"
                    danger={true}
                    style={{ backgroundColor: '#FFA500', borderColor: '#FFA500' }}
                    icon={<FieldTimeOutlined />}
                    onClick={showListModal}
                  >
                    {intl.formatMessage({
                      id: 'leaveHistory',
                      defaultMessage: 'Leave History',
                    })}
                  </Button>
                }
              >
                <Form form={form} layout="vertical" initialValues={initValues}>
                  {
                    <Row style={{ marginLeft: 12 }}>
                      <Col style={{ display: 'flex' }}>
                        <Form.Item
                          style={{ marginBottom: 16, width: 150, marginRight: 20 }}
                          name="date"
                          label="Date"
                          rules={
                            shortleaveType !== 'FULL_DAY'
                              ? [
                                {
                                  required: true,
                                  message: 'Required',
                                },
                              ]
                              : []
                          }
                        >
                          <DatePicker
                            name="date"
                            style={{ width: '100%' }}
                            format={'DD-MM-YYYY'}
                            onChange={handleDateChange}
                          />
                        </Form.Item>
                        <Form.Item
                          style={{ marginBottom: 12 }}
                          name="shortleaveType"
                          label={
                            <FormattedMessage
                              id="period"
                              defaultMessage={intl.formatMessage({
                                id: 'shortLeaveType',
                                defaultMessage: 'Short Leave Type',
                              })}
                            />
                          }
                        >
                          <Radio.Group
                            onChange={(event) => changeLeavePeriod(event.target.value)}
                            value={radioVal}
                          >
                            <Radio value={1}>
                              {intl.formatMessage({
                                id: 'in',
                                defaultMessage: 'In',
                              })}
                            </Radio>
                            <Radio value={2}>
                              {intl.formatMessage({
                                id: 'out',
                                defaultMessage: 'Out',
                              })}
                            </Radio>
                          </Radio.Group>
                        </Form.Item>
                      </Col>
                    </Row>
                  }

                  <Row style={{ marginLeft: 12 }} justify="start">
                    <Col></Col>
                  </Row>
                  <Row style={{ marginLeft: 12 }}>
                    <Col span={18} style={{ width: '100%' }}>
                      <Row>
                        <Col span={8}>
                          <Form.Item
                            style={{ marginBottom: 16, width: 250 }}
                            label="Time Period"
                            name="timeRange"
                            rules={[
                              {
                                required: true,
                                message: 'Required',
                              },
                            ]}
                          >
                            <TimePicker.RangePicker
                              name="timeRange"
                              disabled
                              format={'HH:mm'}
                              onChange={changeTimeRange}
                            />
                          </Form.Item>
                        </Col>
                        <Col span={8} offset={2}>
                          {showCount ? (
                            <span>
                              <Text style={{ color: '#626D6C', fontSize: 14 }}>
                                {intl.formatMessage({
                                  id: 'totalHours',
                                  defaultMessage: 'Total Hours',
                                })}
                              </Text>
                              <div
                                style={{
                                  verticalAlign: ' text-top',
                                  textAlign: 'left',
                                  height: 38,
                                  fontSize: 32,
                                }}
                              >
                                <Text
                                  style={{
                                    fontWeight: 400,
                                    color: '#626D6C',
                                  }}
                                >
                                  {hourCount}{' '}
                                  {Number(hourCount) > 1
                                    ? intl.formatMessage({
                                      id: 'hours',
                                      defaultMessage: 'Hours',
                                    })
                                    : intl.formatMessage({
                                      id: 'hour',
                                      defaultMessage: 'Hour',
                                    })}
                                </Text>
                              </div>
                            </span>
                          ) : (
                            <></>
                          )}
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                  <Row style={{ marginLeft: 12 }}>
                    <Col span={10} style={{}}>
                      {/* <Row><Col><FormattedMessage id="reason" defaultMessage="Reason" /></Col></Row> */}
                      <Row>
                        <Col style={{ width: '100%' }}>
                          <Form.Item
                            style={{ marginBottom: 16 }}
                            name="reason"
                            label={<FormattedMessage id="reason" defaultMessage="Reason" />}
                            rules={[{ max: 250, message: 'Maximum length is 250 characters.' }]}
                          >
                            <Input.TextArea
                              maxLength={251}
                              rows={4}
                              onChange={(event) => {
                                setLeaveReason(event.target.value);
                              }}
                            />
                          </Form.Item>
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                  <Row style={{ marginLeft: 12, marginBottom: 12 }}>
                    <Col span={9}>
                      {canShowAttachement ? (
                        <Form.Item
                          name="upload"
                          label={
                            <FormattedMessage
                              id="attachDocument"
                              defaultMessage="Attach Document"
                            />
                          }
                          rules={[
                            {
                              required: isAttachementMandatory,
                              message: 'Required',
                            },
                          ]}
                        >
                          <Upload {...uploaderProps} className="upload-btn">
                            <Button style={{ borderRadius: 6 }} icon={<UploadOutlined />}>
                              {
                                intl.formatMessage({
                                  id: 'upload',
                                  defaultMessage: 'Upload',
                                })
                              }
                            </Button>
                            <span style={{ paddingLeft: 8, color: '#AAAAAA' }}> {intl.formatMessage({
                              id: 'jpgOrPdf',
                              defaultMessage: 'JPG or PDF',
                            })}</span>
                          </Upload>
                          {fileFormatError && (
                            <Row style={{ color: '#ff4d4f' }}>
                              <Col>
                                <FormattedMessage
                                  id="attachDocument"
                                  defaultMessage="File format should be JPG or PDF"
                                />
                              </Col>
                            </Row>
                          )}
                        </Form.Item>
                      ) : (
                        <></>
                      )}
                    </Col>
                  </Row>
                  <Row>
                    <Col span={10}>
                      <Row justify="end">
                        <Popconfirm
                          key="reset"
                          title={intl.formatMessage({
                            id: 'are_you_sure',
                            defaultMessage: 'Are you sure?',
                          })}
                          onConfirm={() => {
                            setHourCount(null);
                            form.resetFields();
                            setAttachmentList([]);
                            setShortLeaveType('IN_SHORT_LEAVE');
                            setSelectedLeaveTypeObject({
                              fullDayAllowed: true,
                              halfDayAllowed: true,
                              shortLeaveAllowed: false,
                            });
                            setShowCount(false);
                          }}
                          okText="Yes"
                          cancelText="No"
                        >
                          <Button style={{ borderRadius: 6 }}>
                            {intl.formatMessage({
                              id: 'reset',
                              defaultMessage: 'Reset',
                            })}
                          </Button>
                        </Popconfirm>
                        <Button
                          type="primary"
                          onClick={applyLeave}
                          style={{ marginLeft: 25, borderRadius: 6 }}
                        >
                          {intl.formatMessage({
                            id: 'Apply',
                            defaultMessage: 'Apply',
                          })}
                        </Button>
                      </Row>
                    </Col>
                  </Row>
                </Form>
              </Card>
            </Col>
          </Row>
        </ProCard>
        {/* </PageContainer> */}
      </div>

      <ModalForm
        width={'90%'}
        title={intl.formatMessage({
          id: 'leaveHistoryList',
          defaultMessage: 'Leave History',
        })}
        modalProps={{
          destroyOnClose: true,
          bodyStyle: {height: 700}
        }}
        onFinish={async (values: any) => { }}
        visible={listModalVisible}
        onVisibleChange={handleListModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={{
          render: () => {
            return [<>{[]}</>];
          },
        }}
      >
        <LeaveHistoryList listType="shortLeave"></LeaveHistoryList>
      </ModalForm>
    </>
  );
};

export default ApplyShortLeave;
