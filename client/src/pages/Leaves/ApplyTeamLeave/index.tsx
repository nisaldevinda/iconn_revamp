import React, { useEffect, useState } from 'react';
import _ from "lodash";
import { PageContainer } from '@ant-design/pro-layout';
import { Tabs} from 'antd';

import ApplyLeavePage from './applyTeamLeave';
import ApplyShortLeavePage from './applyTeamShortLeave';
import {
  checkShortLeaveAccessabilityForCompany
} from '@/services/leave';
const { TabPane } = Tabs;


const ApplyLeave: React.FC = () => {
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
    <PageContainer>
        <div className='leaveCard'>
            <Tabs type="card" onChange={(value) => {}}>
            <TabPane forceRender={true} tab="Leave" key="all">
                <ApplyLeavePage></ApplyLeavePage>
            </TabPane>
            {
              isShowShortLeaveTab ? 
              <TabPane forceRender={true} tab="Short Leave" key="2">
                  <ApplyShortLeavePage></ApplyShortLeavePage>
              </TabPane> : <></>
            }
            </Tabs>
        </div>
    </PageContainer>
  );
};

export default ApplyLeave;
