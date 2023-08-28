import React, { useEffect, useState } from 'react';
import { getModel, Models, ModelType } from '@/services/model';
import _, { values } from 'lodash';
import { Row, Col, FormInstance, Form, Divider, Transfer, message } from 'antd';
import { useIntl } from 'react-intl';
import { Access, useAccess } from 'umi';
import { ProFormText, ProFormDatePicker, ProFormSwitch, ProFormDigit } from '@ant-design/pro-form';
import OrgSelector from '@/components/OrgSelector';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getAllJobCategories } from '@/services/jobCategory';
import { getClaimTypesByEntityId } from '@/services/expenseModule';

export type CreateFormProps = {
  model: Partial<ModelType>;
  values: {};
  setValues: (values: any) => void;
  addGroupFormVisible: boolean;
  editGroupFormVisible: boolean;
  claimCategoriesList: any;
  isEditView: boolean;
  form: FormInstance;
  emptySwitch: any;
};

const CreatePool: React.FC<CreateFormProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [model, setModel] = useState<any>();
  const [isGlobalAdmin, setIsGlobalAdmin] = useState<boolean>(false);
  const [employmentStatusList, setEmploymentStatusList] = useState([]);
  const [claimList, setClaimList] = useState([]);
  const [jobCategoryList, setJobCategoryList] = useState([]);
  const [empStatusTargetKeys, setEmpStatusTargetKeys] = useState<string[]>([]);
  const [jobCatTargetKeys, setJobCatTargetKeys] = useState<string[]>([]);
  const [claimTargetKeys, setClaimTargetKeys] = useState<string[]>([]);

  useEffect(() => {
    getOptions();
  }, []);

  useEffect(() => {
    if (props.values.id != null && props.values.id > 0) {
      setClaimTargetKeys(props.values.allocatedClaimTypes);
      setEmpStatusTargetKeys(props.values.allowEmploymentStatuses);
      setJobCatTargetKeys(props.values.allowJobCategories);
    }
  }, [props.values.id]);

  const getOptions = async () => {
    try {
      const claimTypes = await getClaimTypesByEntityId({
        orgEntityId: props.values.allowOrgEntityId,
      });

      const claimTypesArray = claimTypes.data.map((claimType) => {
        return {
          title: claimType.typeName,
          key: claimType.id,
        };
      });
      setClaimList(claimTypesArray);

      const empStatusData = await getAllEmploymentStatus();
      const empStatusArray = empStatusData.data.map((empStatus) => {
        return {
          title: empStatus.name,
          key: empStatus.id,
        };
      });
      setEmploymentStatusList(empStatusArray);

      //get job Categories
      const jobCategoryData = await getAllJobCategories();

      const jobCatArray = jobCategoryData.data.map((jobCat) => {
        return {
          title: jobCat.name,
          key: jobCat.id,
        };
      });
      setJobCategoryList(jobCatArray);
    } catch (err) {
      console.error(err);
    }
  };

  const getOrgEntityRelatedClaimTypes = async (entityId) => {
    const claimTypes = await getClaimTypesByEntityId({ orgEntityId: entityId });
    const claimTypesArray = claimTypes.data.map((claimType) => {
      return {
        title: claimType.typeName,
        key: claimType.id,
      };
    });
    setClaimList(claimTypesArray);
  };

  return (
    <Row
      style={{
        overflowY: 'auto',
        maxHeight: props.isEditView ? 730 : 600,
      }}
      gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}
    >
      <Col span={24}>
        <ProFormText
          name="name"
          label="Package Name"
          width="48%"
          rules={[
            {
              required: true,
              message: 'Required',
            },
          ]}
          fieldProps={{
            onChange: (value) => {
              const currentValues = { ...props.values };
              currentValues['name'] =
                !_.isNull(value.target.value) && !_.isUndefined(value.target.value)
                  ? value.target.value
                  : null;
              props.setValues(currentValues);
            },
            autoComplete: 'none',
          }}
          initialValue={null}
        />
      </Col>
      <Col span={24}>
        {/* <Form.Item name="orgEntityId" rules={[{ required: true }]}> */}
        <Row gutter={24}>
          <OrgSelector
            value={props.values.allowOrgEntityId}
            setValue={(orgEntityId: number) => {
              console.log(orgEntityId);
              const currentValues = { ...props.values };
              currentValues['allowOrgEntityId'] = orgEntityId;
              props.setValues(currentValues);
              getOrgEntityRelatedClaimTypes(orgEntityId);
              setClaimTargetKeys([]);
              // const formData = props.form.getFieldsValue();
              // props.form.setFieldsValue({ ...formData, orgEntityId });
            }}
          />
        </Row>
        {/* </Form.Item> */}
      </Col>
      <Divider></Divider>
      <Col span={24}>
        <Form.Item
          label={intl.formatMessage({
            id: 'shiftAssign.selectJobCategories',
            defaultMessage: 'Select Job Categories',
          })}
          name={'allowJobCategories'}
        >
          <Transfer
            dataSource={jobCategoryList}
            showSearch
            filterOption={(search, item) => {
              return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
            }}
            targetKeys={jobCatTargetKeys}
            onChange={(newTargetKeys: string[]) => {
              setJobCatTargetKeys(newTargetKeys);
              const currentValues = { ...props.values };
              currentValues['allowJobCategories'] = newTargetKeys;
              props.setValues(currentValues);
            }}
            render={(item) => item.title}
            listStyle={{
              width: 600,
              height: 300,
              marginBottom: 20,
            }}
            locale={{
              itemUnit: 'Job Category',
              itemsUnit: 'Job Categories',
            }}
          />
        </Form.Item>
      </Col>
      <Col span={24}>
        <Form.Item
          label={intl.formatMessage({
            id: 'shiftAssign.selectJobCategories',
            defaultMessage: 'Select Employment Status',
          })}
          name={'allowEmploymentStatuses'}
        >
          <Transfer
            dataSource={employmentStatusList}
            showSearch
            filterOption={(search, item) => {
              return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
            }}
            targetKeys={empStatusTargetKeys}
            onChange={(newTargetKeys: string[]) => {
              setEmpStatusTargetKeys(newTargetKeys);
              const currentValues = { ...props.values };
              currentValues['allowEmploymentStatuses'] = newTargetKeys;
              props.setValues(currentValues);
            }}
            render={(item) => item.title}
            listStyle={{
              width: 600,
              height: 300,
              marginBottom: 20,
            }}
            locale={{
              itemUnit: 'Employment Status',
              itemsUnit: 'Employment Statuses',
            }}
          />
        </Form.Item>
      </Col>
      <Col span={24}>
        <Form.Item
          label={intl.formatMessage({
            id: 'shiftAssign.selectJobCategories',
            defaultMessage: 'Select Claim Types',
          })}
          rules={[{ required: true, message: 'Required' }]}
          name={'allocatedClaimTypes'}
        >
          <Transfer
            dataSource={claimList}
            showSearch
            filterOption={(search, item) => {
              return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
            }}
            targetKeys={claimTargetKeys}
            onChange={(newTargetKeys: string[]) => {
              setClaimTargetKeys(newTargetKeys);
              const currentValues = { ...props.values };
              currentValues['allocatedClaimTypes'] = newTargetKeys;
              props.setValues(currentValues);
            }}
            render={(item) => item.title}
            listStyle={{
              width: 600,
              height: 300,
              marginBottom: 20,
            }}
            locale={{
              itemUnit: 'Claim Type',
              itemsUnit: 'Claim Types',
            }}
          />
        </Form.Item>
      </Col>
    </Row>
  );
};

export default CreatePool;
