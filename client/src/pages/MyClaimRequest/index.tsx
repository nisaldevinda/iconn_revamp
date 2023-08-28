import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { useIntl, FormattedMessage, history, Access, useAccess } from 'umi';
import {
  Col,
  message,
  Row,
  Card,
  Button,
  Form,
  DatePicker,
  Input,
  InputNumber,
  Upload,
  Divider,
  Empty,
  Popconfirm,
} from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { UploadOutlined } from '@ant-design/icons';
import {
  ProFormSelect,
} from '@ant-design/pro-form';
import { PlusOutlined, DeleteFilled } from '@ant-design/icons';
import {
  getEmployeeEligibleClaimTypes,
  getClaimAllocationDetails,
  createEmployeeClaimRequest,
} from '@/services/expenseModule';
import { financialYears } from '@/services/financialYear';
import moment from 'moment';
import { getBase64 } from '@/utils/fileStore';
import PermissionDeniedPage from '../403';

interface ResignationsProps {
  data: any;
}

const ClaimRequest: React.FC<ResignationsProps> = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;
  const intl = useIntl();

  const [isAllocationEnable, setIsAllocationEnable] = useState(false);
  const [isClaimTypeSelected, setIsClaimTypeSelected] = useState(false);
  const [selectedClaimMonth, setSelectedClaimMonth] = useState<moment.Moment>();
  const [claimTypes, setClaimTypes] = useState([]);
  const [claimTypesArr, setClaimTypesArr] = useState([]);
  const [totalReceiptAmountLabel, setTotalReceiptAmountLabel] = useState('0.00');
  const [allocatedAmountLabel, setAllocatedAmountLabel] = useState('0.00');
  const [allocatedAmount, setAllocatedAmount] = useState(0);
  const [usedAmountLabel, setUsedAmountLabel] = useState('0.00');
  const [usedAmount, setUsedAmount] = useState(0);
  const [balanceAmountLabel, setBalanceAmountLabel] = useState('0.00');
  const [balanceAmount, setBalanceAmount] = useState(0);
  const [totalReceiptAmount, setTotalReceiptAmount] = useState(0);
  const [financeYearList, setFinanceYearList] = useState([]);
  const [financeYearListArr, setFinanceYearListArr] = useState([]);
  const [selectedFinancialYear, setSelectedFinancialYear] = useState(null);
  const [selectedFinancialYearData, setSelectedFinancialYearData] = useState(null);
  const [selectedClaimTypeId, setSelectedClaimTypeId] = useState(null);
  const [selectedClaimTypeData, setSelectedClaimTypeData] = useState(null);
  const [form] = Form.useForm();

  useEffect(() => {
    getClaimTypes();
    getfinancialYears();
  }, []);

  useEffect(() => {
    if (selectedClaimTypeId && selectedFinancialYear) {
      if (selectedClaimTypeData?.isAllocationEnable) {
        getAllocationDetails();
        console.log(selectedClaimTypeData);
      } else {
        setAllocatedAmount(0);
        setAllocatedAmountLabel('0.00');
        setUsedAmount(0);
        setUsedAmountLabel('0.00');
        setBalanceAmount(0);
        setBalanceAmountLabel('0.00');
        setIsAllocationEnable(false);
      }
    }
  }, [selectedClaimTypeId, selectedFinancialYear]);

  const initValues: object = {
    // leaveType: null,
    // date: null,
    // fromDateLeavePeriodType: 1,
    // toDateLeavePeriodType: 1,
    // fromTime: null,
    // toTime: null,
    // reason: '',
  };

  const applyClaimRequest = async () => {
    try {
      form.validateFields();
      if (selectedClaimTypeData?.amountType == 'MAX_AMOUNT' && selectedClaimTypeData?.maxAmount) {
        if (totalReceiptAmount > selectedClaimTypeData?.maxAmount) {
          message.error({
            content: intl.formatMessage({
              id: 'maxAmountValidationErrorMsg',
              defaultMessage: 'Total receipt amount should be less than claim type max amount.',
            }),
          });
          return;
        }
      }

      if (selectedClaimTypeData?.isAllocationEnable) {
        if (totalReceiptAmount > balanceAmount) {
          message.error({
            content: intl.formatMessage({
              id: 'allocatedAmountValidationErrorMsg',
              defaultMessage:
                'Total receipts amount should be less than claim type employee eligible balance amount.',
            }),
          });
          return;
        }
      }

      let receiptArray =
        form.getFieldValue('relatedReceipts') != undefined
          ? form.getFieldValue('relatedReceipts')
          : [];

      let receiptList = [];
      for (let receipt of receiptArray) {
        console.log(receipt);

        const selectedAttachment: Array<object> = [];
        if (receipt.attachment) {
          for (let index = 0; index < receipt.attachment.fileList.length; index++) {
            const base64File = await getBase64(receipt.attachment.fileList[index].originFileObj);
            selectedAttachment[index] = {
              fileName: receipt.attachment.fileList[index].name,
              fileSize: receipt.attachment.fileList[index].size,
              data: base64File,
            };
          }
        }
        let formattedBreak = {
          receiptNumber: receipt.receiptNumber,
          receiptDate: moment(receipt.receiptDate, 'DD-MM-YYYY').format('YYYY-MM-DD'),
          receiptAmount: receipt.receiptAmount,
          attachment: selectedAttachment,
        };

        receiptList.push(formattedBreak);
      }

      let params = {
        financialYear: selectedFinancialYear,
        claimType: selectedClaimTypeId,
        claimMonth:
          selectedClaimTypeData?.orderType == 'MONTHLY'
            ? moment(selectedClaimMonth, 'YYYY/MMMM').format('YYYY/MMMM')
            : null,
        totalReceiptAmount: totalReceiptAmount,
        receiptList: JSON.stringify(receiptList),
      };

      const result = await createEmployeeClaimRequest(params);
      message.success(result.message);
      history.push('/ess/my-requests');
    } catch (error) {
      if (!_.isEmpty(error)) {
        let errorMessage;
        if (error.message) {
          message.error({
            content: error.message,
          });
        }
      }
    }
  };
  const getAllocationDetails = async () => {
    const allocationData = await getClaimAllocationDetails({
      financialYearId: selectedFinancialYear,
      claimType: selectedClaimTypeId,
    });

    if (allocationData.data) {
      let allocatedAmount = allocationData.data['allocatedAmount'];
      let allocatedAmountLabel = allocationData.data['allocatedAmount']
        ? allocationData.data['allocatedAmount'].toFixed(2).toString()
        : '0.00';

      let usedAmount = allocationData.data['usedAmount'] + allocationData.data['pendingAmount'];
      let usedAmountLabel = usedAmount ? usedAmount.toFixed(2).toString() : '0.00';

      let balanceAmount = allocatedAmount - usedAmount;
      let balanceAmountLabel = balanceAmount ? balanceAmount.toFixed(2).toString() : '0.00';

      setAllocatedAmount(allocatedAmount);
      setAllocatedAmountLabel(allocatedAmountLabel);
      setUsedAmount(usedAmount);
      setUsedAmountLabel(usedAmountLabel);
      setBalanceAmount(balanceAmount);
      setBalanceAmountLabel(balanceAmountLabel);
    } else {
      setAllocatedAmount(0);
      setAllocatedAmountLabel('0.00');
      setUsedAmount(0);
      setUsedAmountLabel('0.00');
      setBalanceAmount(0);
      setBalanceAmountLabel('0.00');
    }

    setIsAllocationEnable(true);
    console.log(allocationData);
  };
  const getfinancialYears = async () => {
    const financialYearData = await financialYears({});

    const financialYearArray = financialYearData.data.map((finYear: any) => {
      return {
        label: finYear.financialDateRangeString,
        value: finYear.id,
      };
    });
    setFinanceYearList(financialYearArray);

    setFinanceYearListArr(financialYearData.data);
  };
  const getClaimTypes = async () => {
    try {
      const actions: any = [];
      const res = await getEmployeeEligibleClaimTypes({});

      if (!_.isEmpty(res.data)) {
        setClaimTypes(res.data);
      }

      res.data.forEach(async (element: any) => {
        actions.push({ value: element['id'], label: element['typeName'] });
      });
      setClaimTypesArr(actions);
    } catch (error) {
      console.log('error:', error);
    }
  };

  const disabledClaimMonths = (current) => {
    let compareFromDate = moment(selectedFinancialYearData?.fromYearAndMonth, 'YYYY-MM-DD').format(
      'YYYY/MMMM',
    );
    let compareToDate = moment(selectedFinancialYearData?.toYearAndMonth, 'YYYY-MM-DD').format(
      'YYYY/MMMM',
    );
    let currentDate = moment(current, 'YYYY/MMMM').format('YYYY/MMMM');

    const isPreviousDay =
      moment(compareFromDate, 'YYYY/MMMM') <= moment(currentDate, 'YYYY/MMMM') &&
      moment(compareToDate, 'YYYY/MMMM') >= moment(currentDate, 'YYYY/MMMM');

    return !isPreviousDay;
  };

  const disabledReceiptDates = (current) => {
    if (selectedClaimTypeData?.orderType == 'ANNUALY') {
      let compareFromDate = moment(
        selectedFinancialYearData?.fromYearAndMonth,
        'YYYY-MM-DD',
      ).format('DD-MM-YYYY');
      let compareToDate = moment(selectedFinancialYearData?.toYearAndMonth, 'YYYY-MM-DD').format(
        'DD-MM-YYYY',
      );
      let currentDate = moment(current, 'DD-MM-YYYY').format('DD-MM-YYYY');

      const isPreviousDay =
        moment(compareFromDate, 'DD-MM-YYYY') <= moment(currentDate, 'DD-MM-YYYY') &&
        moment(compareToDate, 'DD-MM-YYYY') >= moment(currentDate, 'DD-MM-YYYY');

      return !isPreviousDay;
    } else {
      let compareFromDate = selectedClaimMonth.startOf('month').format('DD-MM-YYYY');
      let compareToDate = selectedClaimMonth.endOf('month').format('DD-MM-YYYY');
      let currentDate = moment(current, 'DD-MM-YYYY').format('DD-MM-YYYY');

      const isPreviousDay =
        moment(compareFromDate, 'DD-MM-YYYY') <= moment(currentDate, 'DD-MM-YYYY') &&
        moment(compareToDate, 'DD-MM-YYYY') >= moment(currentDate, 'DD-MM-YYYY');

      return !isPreviousDay;
    }
  };

  const calculateTotalAmount = () => {
    let receipts = form.getFieldValue('relatedReceipts')
      ? form.getFieldValue('relatedReceipts')
      : [];

    let totalAmount = 0;
    receipts.map((el, index) => {
      totalAmount += el.receiptAmount;
    });

    setTotalReceiptAmount(totalAmount);
    if ((totalAmount ^ 0) !== totalAmount) {
      let label = totalAmount !== 0 ? totalAmount.toFixed(2).toString() : '0.00';
      setTotalReceiptAmountLabel(label);
    } else {
      let label = totalAmount !== 0 ? totalAmount.toString() + '.00' : '0.00';
      setTotalReceiptAmountLabel(label);
    }
  };
  const canShowAttachment = (key: any) => {
    let receiptArray =
      form.getFieldValue('relatedReceipts') != undefined
        ? form.getFieldValue('relatedReceipts')
        : [];

    if (
      receiptArray[key]['attachment']?.fileList.length != undefined &&
      receiptArray[key]['attachment']?.fileList.length > 0
    ) {
      return false;
    } else {
      return true;
    }
  };
  const uploaderProps = {
    beforeUpload: (file) => {
      const isValidFormat = file.type === 'image/jpeg' || file.type === 'application/pdf';
      if (!isValidFormat) {
        message.error('File format should be JPG or PDF');
      }
      return isValidFormat || Upload.LIST_IGNORE;
    },
    // showUploadList: false,
    maxCount: 1,
    onChange({ file, fileList }) {
      if (file.status !== 'uploading') {
        form.setFieldsValue({ upload: fileList });
        // setAttachmentList(fileList);
        // setfileFormatError(false);
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
          // setAttachmentList([...fileList]);
        }
      }
    },
  };

  return (
    <PageContainer>
      <Access
        accessible={hasPermitted('my-leave-entitlements')}
        fallback={<PermissionDeniedPage />}
      >
        <Row style={{ width: '100%' }} gutter={16}>
          <Col span={16}>
            <Card
              title={intl.formatMessage({
                id: 'claimRequest',
                defaultMessage: 'Claim Request',
              })}
            >
              <Form form={form} layout="vertical" initialValues={initValues}>
                <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                  <Col span={12}>
                    <ProFormSelect
                      width={'100%'}
                      name="financialYear"
                      placeholder={intl.formatMessage({
                        id: 'financialYearDropdownLabel',
                        defaultMessage: 'Select Financial Year',
                      })}
                      label={'Financial Year'}
                      rules={[{ required: true, message: 'Required' }]}
                      options={financeYearList}
                      showSearch
                      onChange={(val: any) => {
                        setSelectedFinancialYear(val);

                        const index = financeYearListArr.findIndex((fy) => val == fy.id);
                        setSelectedFinancialYearData(financeYearListArr[index]);
                        // setSelectedFinacialYearForBulkAllocation(val);
                      }}
                    />
                  </Col>
                  <Col span={12}>
                    <ProFormSelect
                      width={'100%'}
                      name="claimType"
                      disabled={!selectedFinancialYear}
                      placeholder={intl.formatMessage({
                        id: 'claimTypeDropdownLabel',
                        defaultMessage: 'Select Claim Type',
                      })}
                      label={'Claim Type'}
                      options={claimTypesArr}
                      rules={[{ required: true, message: 'Required' }]}
                      showSearch
                      onChange={(val: any) => {
                        // if (val) {
                        //   setIsClaimTypeSelected(true);
                        // } else {
                        //   setIsClaimTypeSelected(false);
                        // }

                        setSelectedClaimTypeId(val);

                        const index = claimTypes.findIndex((claim) => val == claim.id);
                        // setIsAllocationEnable(claimTypes[index]['isAllocationEnable'])
                        setSelectedClaimTypeData(claimTypes[index]);
                        form.setFieldsValue({ claimMonth: null });

                        if (claimTypes[index]['orderType'] == 'ANNUALY') {
                          let receipts = [];
                          let tempObj = {
                            id: 'new',
                            receiptNumber: null,
                            receiptDate: null,
                            receiptAmount: null,
                            attachment: null,
                          };
                          receipts.push(tempObj);

                          form.setFieldsValue({ relatedReceipts: receipts });
                          setIsClaimTypeSelected(true);
                        } else {
                          form.setFieldsValue({ relatedReceipts: [] });
                          setIsClaimTypeSelected(false);
                        }
                      }}
                    />
                  </Col>
                  {selectedClaimTypeData?.orderType == 'MONTHLY' ? (
                    <Col span={12}>
                      <Form.Item
                        name={'claimMonth'}
                        label={intl.formatMessage({
                          id: 'claimMonth',
                          defaultMessage: 'Claim Month',
                        })}
                        rules={[
                          {
                            required: true,
                            message: intl.formatMessage({
                              id: 'required',
                              defaultMessage: 'Required',
                            }),
                          },
                        ]}
                      >
                        <DatePicker
                          format="YYYY/MMMM"
                          picker="month"
                          style={{ width: '100%' }}
                          disabledDate={disabledClaimMonths}
                          placeholder={intl.formatMessage({
                            id: 'selectClaimMonth',
                            defaultMessage: 'Select Claim Month',
                          })}
                          onChange={(value) => {
                            if (value) {
                              let receipts = [];
                              let tempObj = {
                                id: 'new',
                                receiptNumber: null,
                                receiptDate: null,
                                receiptAmount: null,
                                attachment: null,
                              };
                              receipts.push(tempObj);

                              form.setFieldsValue({ relatedReceipts: receipts });
                              setIsClaimTypeSelected(true);
                            } else {
                              form.setFieldsValue({ relatedReceipts: [] });
                              setIsClaimTypeSelected(false);
                            }

                            setSelectedClaimMonth(value);
                          }}
                        />
                      </Form.Item>
                    </Col>
                  ) : (
                    <></>
                  )}

                  <Divider />
                  <Col span={24} style={{ paddingBottom: 8, fontSize: 16, fontWeight: 'bold' }}>
                    <FormattedMessage id="breakDetails" defaultMessage="Receipt Details" />
                  </Col>
                  {isClaimTypeSelected ? (
                    <Col span={24}>
                      <Form.List name="relatedReceipts">
                        {(fields, { add, remove }) => (
                          <>
                            <div
                            // style={{
                            //   overflowY: 'auto',
                            //   maxHeight: 2500,
                            //   marginBottom: 20,
                            // }}
                            >
                              {fields.map(({ key, name, ...restField }) => (
                                <Row
                                  gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}
                                  style={{ width: '100%' }}
                                >
                                  <Col span={6}>
                                    <Form.Item
                                      name={[name, 'receiptNumber']}
                                      label="Receipt No"
                                      style={{ width: '100%' }}
                                      rules={[{ required: true, message: 'Required' }]}
                                    >
                                      <Input style={{ borderRadius: 6 }} placeholder="Entity Name" />
                                    </Form.Item>
                                  </Col>
                                  <Col span={6}>
                                    <Form.Item
                                      name={[name, 'receiptDate']}
                                      label="Receipt Date"
                                      style={{ width: '100%' }}
                                      rules={[{ required: true, message: 'Required' }]}
                                    >
                                      <DatePicker
                                        placeholder="Select Receipt Date (DD-MM-YYYY)"
                                        style={{ width: '100%' }}
                                        format={'DD-MM-YYYY'}
                                        disabledDate={disabledReceiptDates}
                                      />
                                    </Form.Item>
                                  </Col>
                                  <Col span={6}>
                                    <Form.Item
                                      name={[name, 'receiptAmount']}
                                      label="Receipt Amount"
                                      style={{ width: '100%' }}
                                      rules={[{ required: true, message: 'Required' }]}
                                    >
                                      <InputNumber
                                        onChange={(val) => {
                                          calculateTotalAmount();
                                        }}
                                        placeholder={'Receipt Amount'}
                                        style={{ width: '100%' }}
                                        min={0}
                                        precision={2}
                                      />
                                    </Form.Item>
                                  </Col>
                                  {selectedClaimTypeData['isAllowAttachment'] ? (
                                    <Col span={4}>
                                      <Form.Item
                                        name={[name, 'attachment']}
                                        label="Attachment"
                                        style={{ width: '100%' }}
                                        rules={
                                          selectedClaimTypeData['isAttachmentMandatory']
                                            ? [{ required: true, message: 'Required' }]
                                            : []
                                        }
                                      >
                                        <Upload
                                          beforeUpload={(file) => {
                                            const isValidFormat =
                                              file.type === 'image/jpeg' ||
                                              file.type === 'application/pdf';
                                            if (!isValidFormat) {
                                              message.error('File format should be JPG or PDF');
                                            }
                                            const isLt2M = file.size / 1024 / 1024 < 2;
                                            if (!isLt2M) {
                                              message.error('File must smaller than 2MB!');
                                              return false;
                                            }

                                            return isValidFormat || Upload.LIST_IGNORE;
                                          }}
                                          maxCount={1}
                                          onChange={({ file, fileList }) => {
                                            console.log(file);
                                            if (file.status !== 'uploading') {
                                              form.setFieldsValue({ upload: fileList });
                                              // setAttachmentList(fileList);
                                              // setfileFormatError(false);
                                            }

                                            if (file.status == 'removed') {
                                              let receiptArray =
                                                form.getFieldValue('relatedReceipts') != undefined
                                                  ? form.getFieldValue('relatedReceipts')
                                                  : [];
                                              receiptArray[key]['attachment'] = null;

                                              form.setFieldsValue({ relatedReceipts: receiptArray });
                                            }
                                            // for handle error
                                            if (file.status === 'error') {
                                              const { uid } = file;
                                              const index = fileList.findIndex(
                                                (file: any) => file.uid == uid,
                                              );
                                              const newFile = { ...file };
                                              if (index > -1) {
                                                newFile.status = 'done';
                                                newFile.percent = 100;
                                                delete newFile.error;
                                                fileList[index] = newFile;
                                                // setAttachmentList([...fileList]);
                                              }
                                            }
                                          }}
                                          className="upload-btn"
                                        >
                                          {canShowAttachment(key) ? (
                                            <Button
                                              style={{
                                                borderRadius: 6,
                                                marginBottom: 30,
                                                width: 150,
                                              }}
                                              icon={<UploadOutlined />}
                                            >
                                              {intl.formatMessage({
                                                id: 'upload',
                                                defaultMessage: 'Upload',
                                              })}
                                            </Button>
                                          ) : (
                                            <></>
                                          )}
                                        </Upload>
                                      </Form.Item>
                                    </Col>
                                  ) : (
                                    <></>
                                  )}

                                  {/* <Col span={6}></Col> */}
                                  <Col span={2}>
                                    {form.getFieldValue(['relatedReceipts', key, 'id']) == 'new' ? (
                                      key != 0 ? (
                                        <DeleteFilled
                                          onClick={() => {
                                            // remove(name)
                                            let newReceipts = [];
                                            let receipts = form.getFieldValue('relatedReceipts')
                                              ? form.getFieldValue('relatedReceipts')
                                              : [];

                                            receipts.map((el, index) => {
                                              if (index != key) {
                                                newReceipts.push(el);
                                              }
                                            });
                                            form.setFieldsValue({
                                              relatedReceipts: newReceipts,
                                            });
                                            calculateTotalAmount();
                                          }}
                                          style={{ marginTop: 35, fontSize: 20 }}
                                        />
                                      ) : (
                                        <></>
                                      )
                                    ) : (
                                      <></>
                                    )}
                                  </Col>
                                </Row>
                              ))}
                            </div>
                            <Row style={{ marginTop: 25 }}>
                              <Col span={6}>
                                <Button
                                  type="dashed"
                                  style={{
                                    backgroundColor: '#E4eff1',
                                    borderColor: '#E4eff1',
                                    borderRadius: 6,
                                  }}
                                  disabled={!isClaimTypeSelected}
                                  onClick={() => {
                                    // add();
                                    let receipts = form.getFieldValue('relatedReceipts')
                                      ? form.getFieldValue('relatedReceipts')
                                      : [];
                                    let tempObj = {
                                      id: 'new',
                                      receiptNumber: null,
                                      receiptDate: null,
                                      receiptAmount: null,
                                      attachment: null,
                                    };
                                    receipts.push(tempObj);

                                    form.setFieldsValue({ relatedReceipts: receipts });
                                  }}
                                  block
                                  icon={<PlusOutlined />}
                                >
                                  Add Receipt
                                </Button>
                              </Col>
                              <Col span={18}>
                                <div style={{ float: 'right', marginRight: 0 }}>
                                  <span style={{ fontSize: 24, fontWeight: 'bold' }}>
                                    <FormattedMessage
                                      id="totalAmounts"
                                      defaultMessage="Total Amount : "
                                    />
                                    {totalReceiptAmountLabel}
                                  </span>
                                </div>
                              </Col>
                            </Row>
                          </>
                        )}
                      </Form.List>
                    </Col>
                  ) : (
                    <Col span={24}>
                      <Empty description={'No Receipt Data'}></Empty>
                    </Col>
                  )}
                </Row>
                <Divider />
                <Row>
                  <Col span={24}>
                    <Row justify="end">
                      <Popconfirm
                        key="reset"
                        title={intl.formatMessage({
                          id: 'are_you_sure',
                          defaultMessage: 'Are you sure?',
                        })}
                        onConfirm={() => {
                          // setWorkingDaysCount(null);
                          // form.resetFields();
                          // setAttachmentList([]);
                          // setFromDateLeavePeriodType('FULL_DAY');
                          // setSelectedLeaveTypeObject({
                          //   fullDayAllowed: true,
                          //   halfDayAllowed: true,
                          //   shortLeaveAllowed: false,
                          // });
                          // setShowCount(false);
                        }}
                        okText="Yes"
                        cancelText="No"
                      >
                        <Button style={{ borderRadius: 6 }}>
                          {intl.formatMessage({
                            id: 'cancel',
                            defaultMessage: 'Cancel',
                          })}
                        </Button>
                      </Popconfirm>
                      <Button
                        type="primary"
                        onClick={applyClaimRequest}
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
          <Col span={8}>
            <Row className="allocationDetails" style={{ height: 150, marginBottom: 16 }}>
              <Card
                title={intl.formatMessage({
                  id: 'allocationDetails',
                  defaultMessage: 'Allocation Details',
                })}
                style={{ width: '100%' }}
              >
                {isAllocationEnable ? (
                  <Row>
                    <Col span={24}>
                      <Row style={{ fontSize: 18, fontWeight: 'bold', color: 'gray' }}>
                        Allocated Amount
                      </Row>
                      <Row style={{ fontSize: 28, fontWeight: 'bold' }}>{allocatedAmountLabel}</Row>
                    </Col>
                    <Col style={{ marginTop: 20 }} span={24}>
                      <Row>
                        <Col span={12}>
                          <Row style={{ fontSize: 18, fontWeight: 'bold', color: 'gray' }}>
                            Used Amount
                          </Row>
                          <Row style={{ fontSize: 28, fontWeight: 'bold', color: '#faa630' }}>
                            {usedAmountLabel}
                          </Row>
                        </Col>
                        <Col span={12}>
                          <Row style={{ fontSize: 18, fontWeight: 'bold', color: 'gray' }}>
                            Balance Amount
                          </Row>
                          <Row style={{ fontSize: 28, fontWeight: 'bold', color: '#74b425' }}>
                            {balanceAmountLabel}
                          </Row>
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                ) : (
                  <Empty description={'No Allocation Details'}></Empty>
                )}
              </Card>
            </Row>
          </Col>
        </Row>
      </Access>
    </PageContainer>
  );
};

export default ClaimRequest;
