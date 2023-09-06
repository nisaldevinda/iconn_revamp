import React, { useEffect, useState } from 'react';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { Tabs } from 'antd';
import ApplyLeavePage from './applyLeave';
import ApplyShortLeavePage from './applyShortLeave';
import {
  checkShortLeaveAccessabilityForCompany
} from '@/services/leave';
import { Access, useAccess } from 'umi';
import PermissionDeniedPage from '@/pages/403';
const { TabPane } = Tabs;

const ApplyLeave: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    checkShortLeaveAccessability();
  }, []);

  const [isShowShortLeaveTab, setIsShowShortLeaveTab] = useState(false);

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
        <Access accessible={hasPermitted('my-leave-request')} fallback={<PermissionDeniedPage />}>
          <div className="leaveCard">
            <Tabs type="card" onChange={(value) => {}}>
              <TabPane forceRender={true} tab="Leave" key="all">
                <ApplyLeavePage></ApplyLeavePage>
              </TabPane>
              {isShowShortLeaveTab ? (
                <TabPane forceRender={true} tab="Short Leave" key="2">
                  <ApplyShortLeavePage></ApplyShortLeavePage>
                </TabPane>
              ) : (
                <></>
              )}
            </Tabs>
          </div>
        </Access>
      </PageContainer>
    </div>
  );
};

export default ApplyLeave;
