import React, { useEffect, useState } from 'react';
import _ from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import {
  message,
  Popconfirm,
  Tooltip,
  Form,
  Row,
  Col,
  Space,
  Spin,
  Tag,
  Input,
  Button,
  Tabs,
} from 'antd';
import { useIntl } from 'react-intl';
import request, { APIResponse } from '@/utils/request';
import { hasGlobalAdminPrivileges } from '@/utils/permission';
import { Access, useAccess } from 'umi';
import { DrawerForm, ModalForm, ProFormSelect } from '@ant-design/pro-form';
import { genarateEmptyValuesObject } from '@/utils/utils';
import { ProFormText } from '@ant-design/pro-form';
import { generateProFormFieldValidation } from '@/utils/validator';
import { CopyOutlined } from '@ant-design/icons';
import AssignLeavePage from './assignLeave';
import AssignShortLeavePage from './assignShortLeave';
// import ApplyShortLeavePage from './applyShortLeave';
const { TabPane } = Tabs;
import { checkShortLeaveAccessabilityForCompany } from '@/services/leave';

const AssignLeave: React.FC = () => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [isShowShortLeaveTab, setIsShowShortLeaveTab] = useState(false);

  useEffect(() => {
    checkShortLeaveAccessability();
  }, []);

  const checkShortLeaveAccessability = async () => {
    try {
      const response = await checkShortLeaveAccessabilityForCompany({});
      setIsShowShortLeaveTab(response.data.isMaintainShortLeave);
    } catch (err) {
      console.log(err);
    }
  };

  return (
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
      <PageContainer>
        <div className="leaveCard">
          <Tabs type="card" onChange={(value) => {}}>
            <TabPane forceRender={true} tab="Leave" key="all">
              <AssignLeavePage></AssignLeavePage>
            </TabPane>
            {isShowShortLeaveTab ? (
              <TabPane forceRender={true} tab="Short Leave" key="2">
                <AssignShortLeavePage></AssignShortLeavePage>
              </TabPane>
            ) : (
              <></>
            )}
          </Tabs>
        </div>
      </PageContainer>
    </div>
  );
};

export default AssignLeave;
