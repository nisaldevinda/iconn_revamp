import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { message, Skeleton, Row, Col, Tooltip, Popconfirm, Space, Badge } from 'antd';
import { FormattedMessage, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import Button from 'antd/lib/button';
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import { DrawerForm, ModalForm, ProFormDatePicker, ProFormDigit } from '@ant-design/pro-form';
import { getPayGrade } from '@/services/PayGradeService';
import { querySalaryComponent } from '@/services/SalaryComponentService';
import moment from 'moment';

interface EmployeeProfileSalarySectionProps {
  values: any,
  permission: any,
  scope?: string,
  tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
}

const EmployeeProfileSalarySection: React.FC<EmployeeProfileSalarySectionProps> = (props) => {
  const intl = useIntl();

  const [loading, setLoading] = useState<boolean>(false);
  const [currentJob, setCurrentJob] = useState();
  const [modalVisible, setModalVisible] = useState(false);
  const [currentIndex, setCurrentIndex] = useState<number>();
  const [currentRecord, setCurrentRecord] = useState<any>();
  const [payGrade, setPayGrade] = useState();
  const [salaryComponents, setSalaryComponents] = useState<any[]>();
  const [data, setData] = useState([]);
  const [currentRecordOnEffectiveDate, setCurrentRecordOnEffectiveDate] = useState<number>();

  useEffect(() => {
    init();

    let currentId = props.values['currentSalariesId'] ?? null;
    setCurrentRecordOnEffectiveDate(currentId);
  }, [props.values]);

  useEffect(() => {
    processRecords(salaryComponents);
  }, [props.values['salaries'], salaryComponents]);

  const init = async () => {
    setLoading(true);

    await setupPayGrade();

    setLoading(false);
  }

  const setupPayGrade = async (date?: string) => {
    let payGradeId;
    if (date) {
      let _jobs = [...props.values.jobs];
      _jobs.sort((first: any, second: any) =>
        (moment(first.effectiveDate, 'YYYY-MM-DD') > moment(second.effectiveDate, 'YYYY-MM-DD')) ? 1
          : ((moment(second.effectiveDate, 'YYYY-MM-DD') > moment(first.effectiveDate, 'YYYY-MM-DD')) ? -1 : 0));
      _jobs.forEach(_job => {
        if (_job.effectiveDate <= date) {
          payGradeId = _job.payGradeId;
        } else {
          return;
        }
      });
    } else {
      payGradeId = props.values.jobs.find(job => job.id == props.values.currentJobsId)?.payGradeId;
    }

    if (payGradeId) {
      const payGradeRes = await getPayGrade(payGradeId);
      const salaryComponentRes = await querySalaryComponent();

      if (!payGradeRes.error && !salaryComponentRes.error) {
        const salaryComponents = salaryComponentRes.data;
        setSalaryComponents(salaryComponents);

        const payGrade = payGradeRes.data;
        const salaryComponentIds = JSON.parse(payGrade?.salaryComponentIds);

        if (_.isArray(salaryComponentIds)) {
          const _salaryComponents = salaryComponentIds?.map(id => salaryComponents.find(component => component.id == id));
          setPayGrade({ ...payGradeRes.data, salaryComponents: _salaryComponents });
        }

        processRecords(salaryComponents);
      }
    }
  }

  const formatter = (data: {
    id: number,
    effectiveDate: string,
    salaryDetails: string
  }) => {
    let _data = { ...data };
    const _salaryDetails: any[] = JSON.parse(data.salaryDetails);
    _salaryDetails.forEach(_salaryComponent => {
      _data[_salaryComponent.salaryComponentId] = _salaryComponent.value;
    })
    return _data;
  }

  const processRecords = (salaryComponents) => {
    if (!salaryComponents) {
      setData([]);
      return;
    }

    const _data = props.values['salaries'].map(salary => {
      return {
        ...salary,
        processedSalaryDetails: salary.salaryDetails && !_.isEmpty(JSON.parse(salary.salaryDetails))
          ? JSON.parse(salary.salaryDetails).map(salaryDetail => {
            return {
              ...salaryDetail,
              salaryComponent: salaryComponents.find(component => component.id == salaryDetail.salaryComponentId)
            };
          })
          : []
      };
    })

    setData(_data);
  }

  const columns = [
    {
      "title": "Effective Date",
      "dataIndex": "effectiveDate"
    },
    {
      "title": "Salary Details",
      "dataIndex": "salaryDetails",
      render: (text, record, index, action) => [
        <ul style={{ padding: 0 }}>
          {record?.processedSalaryDetails?.map((salaryComponent: any) =>
            <li>{salaryComponent.salaryComponent.name}: {salaryComponent.value}</li>)
          }
        </ul>
      ]
    },
    {
      title: intl.formatMessage({
        id: 'actions',
        defaultMessage: 'Actions',
      }),
      valueType: 'option',
      align: 'center',
      fixed: 'right',
      width: 80,
      render: (text, record, index, action) => [
        hasEditPermission()
          ? <Tooltip title={
            intl.formatMessage({
              id: 'edit',
              defaultMessage: 'Edit',
            })
          }>
            <a data-key={`${'salaries'}.${record.id}.edit`} onClick={() => {
              setCurrentIndex(index);
              setCurrentRecord(formatter(record));
              setModalVisible(true);
            }}>
              <EditOutlined />
            </a>
          </Tooltip>
          : <></>,
        hasEditPermission() && record.recordCanDelete
          ? <Popconfirm
            title={intl.formatMessage({
              id: 'are_you_sure',
              defaultMessage: 'Are you sure?'
            })}
            onConfirm={() => removeRecord(index, record)}
            okText="Yes"
            cancelText="No"
          >
            <Tooltip title={
              intl.formatMessage({
                id: 'delete',
                defaultMessage: 'Delete',
              })
            }>
              <a data-key={`${'salaries'}.${record.id}.delete`}> <DeleteOutlined /> </a>
            </Tooltip>
          </Popconfirm>
          : <></>
      ]
    },
    {
      valueType: 'option',
      fixed: 'left',
      width: 1,
      render: (_, record: any) => {
        return <Space>
          {record.isCurrentRecordOnEffectiveDate ? <Badge status='success' dot={true} /> : null}
        </Space>
      }
    }
  ];

  const removeRecord = (index: number, record: any) => {
    setLoading(true);

    // let currentValues = { ...props.values };
    // let currentRecords = [...currentValues[fieldName]];

    // if (props.tabularDataDeleter) {
    //     props.tabularDataDeleter(currentValues['id'], fieldName, record['id']);
    // } else {
    //     currentRecords.splice(index, 1);
    //     currentValues[fieldName] = currentRecords;

    //     props.setValues(currentValues);

    //     const instanceData = { 'id': currentValues['id'] };
    //     instanceData[fieldName] = currentValues[fieldName];
    //     props.formSubmit(instanceData);
    // }

    setLoading(false);
  }

  const setupCurrentJob = async () => {
    if (props?.values?.jobs && props?.values?.currentJobsId) {
      const _currentJob = props?.values?.jobs.find(job => job.id == props?.values?.currentJobsId);
      setCurrentJob(_currentJob ?? undefined);
    }
  }

  const hasViewPermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    return props?.permission['employee']?.viewOnly.includes('employeeSalarySection')
      || props?.permission['employee']?.canEdit.includes('employeeSalarySection');
  }

  const hasEditPermission = () => {
    if (_.isArray(props?.permission) && props?.permission.includes('*')) return true;
    return props?.permission['employee']?.canEdit.includes('employeeSalarySection');
  }

  const hasCreatePermission = () => {
    return hasEditPermission();
  }

  const onFinish = async (values: any, id?: number) => {
    const isUpdate = id ? true : false;

    const key = 'messageKey';
    message.loading({
      content: intl.formatMessage({
        id: 'saving',
        defaultMessage: 'Saving ...',
      }),
      key,
    });

    let data = isUpdate ? {
      id,
      effectiveDate: moment(values.effectiveDate, 'DD-MM-YYYY').format('YYYY-MM-DD'),
      salaryDetails: {}
    } : {
      effectiveDate: moment(values.effectiveDate, 'DD-MM-YYYY').format('YYYY-MM-DD'),
      salaryDetails: {}
    };

    data.salaryDetails = JSON.stringify(payGrade?.salaryComponents?.map((salaryComponent: any) => {
      return {
        'salaryComponentId': salaryComponent.id,
        'value': values[salaryComponent.id]
      };
    }));

    isUpdate
      ? props.tabularDataUpdater(props.values.id, 'salaries', id, data)
        .then(response => {
          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullyUpdated',
                defaultMessage: 'Successfully Updated',
              }),
            key,
          });
          setModalVisible(false);
        })
        .catch(error => {
          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'failedToUpdate',
                defaultMessage: 'Failed to update',
              }),
            key,
          });
        })
      : props.tabularDataCreator(props.values.id, 'salaries', data)
        .then(response => {
          message.success({
            content:
              response.message ??
              intl.formatMessage({
                id: 'successfullyCreated',
                defaultMessage: 'Successfully Created',
              }),
            key,
          });
          setModalVisible(false);
        })
        .catch(error => {
          message.error({
            content:
              error.message ??
              intl.formatMessage({
                id: 'failedToCreate',
                defaultMessage: 'Failed to create',
              }),
            key,
          });
        });
  }

  const genarateForm = () => {
    return <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
      <Col data-key="effectiveDate" span={12}>
        <ProFormDatePicker
          width="md"
          name="effectiveDate"
          label="Effective Date"
          placeholder="Select Effective Date (DD-MM-YYYY)"
          rules={[{ required: true, message: "Required" }]}
          fieldProps={{
            format: 'DD-MM-YYYY',
            showToday: true,
          }}
        />
      </Col>
      {payGrade?.salaryComponents?.map((salaryComponent: any) =>
        <Col data-key={salaryComponent.id} span={12}>
          <ProFormDigit
            width="md"
            name={salaryComponent.id}
            label={salaryComponent.name}
            fieldProps={{
              type: "number",
              autoComplete: "none",
              step: "0.01",
              onKeyDown: (evt) => ((evt.key === 'e') && evt.preventDefault()),
              min: 0
            }}
          />
        </Col>
      )}
    </Row>;
  }

  return loading ? (<Skeleton active className='dynamic-form-skeleton' />) : _.isEmpty(columns) ? <FormattedMessage id="noAccess" defaultMessage="No Access" /> : (
    <div style={{ padding: 16, width: "100%" }}>
      <Col data-key={'salaries'} span={24}>
        <ProTable
          pagination={{ pageSize: 20, defaultPageSize: 20, hideOnSinglePage: true }}
          id={'salaries'}
          rowKey="id"
          columns={columns}
          dataSource={!loading ? data.map((record: any) => {
            return {
              ...record,
              isCurrentRecordOnEffectiveDate: currentRecordOnEffectiveDate == record.id ? true : false
            };
          }) : []}
          options={false}
          search={false}
          // actionRef={actionRef}
          toolBarRender={() => [
            hasCreatePermission()
              ? <Button
                data-key={`${'salaries'}.add`}
                type="primary"
                key="add"
                onClick={() => {
                  setCurrentIndex(null);
                  setCurrentRecord(null);
                  setModalVisible(true);
                }}
              >
                <PlusOutlined /> <FormattedMessage id="pages.user.new" defaultMessage="New" />
              </Button>
              : <></>
          ]}
        />

        {_.isEmpty(currentIndex) && _.isEmpty(currentRecord)
          ? <ModalForm
            key={'salaries'.concat('Modal')}
            title={`Add Salary`}
            modalProps={{
              destroyOnClose: true,
            }}
            visible={modalVisible}
            onVisibleChange={setModalVisible}
            submitter={{
              searchConfig: {
                submitText: intl.formatMessage({
                  id: 'add',
                  defaultMessage: 'Add',
                }),
                resetText: intl.formatMessage({
                  id: 'cancel',
                  defaultMessage: 'Cancel',
                }),
              },
            }}
            width="60vw"
            onFinish={values => onFinish(values)}
          >
            {genarateForm()}
          </ModalForm>
          : <DrawerForm
            key={`salariesModal`}
            title={`Edit Salary`}
            drawerProps={{
              destroyOnClose: true,
            }}
            width="40vw"
            visible={modalVisible}
            onVisibleChange={setModalVisible}
            submitter={{
              searchConfig: {
                submitText: intl.formatMessage({
                  id: 'update',
                  defaultMessage: 'Update',
                }),
                resetText: intl.formatMessage({
                  id: 'cancel',
                  defaultMessage: 'Cancel',
                }),
              },
            }}
            initialValues={currentRecord}
            onFinish={values => onFinish(values, currentRecord.id)}
          >
            {genarateForm()}
          </DrawerForm>
        }
      </Col>
    </div>);
};

export default EmployeeProfileSalarySection;
