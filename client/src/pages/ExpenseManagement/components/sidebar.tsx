import React, { useEffect, useState } from 'react';
import { Tabs } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import ClaimCategory from '../fragments/ClaimCategories';
import ClaimTypes from '../fragments/ClaimTypes';
import ClaimAssignments from '../fragments/ClaimAssignments';
import ClaimAllocations from '../fragments/ClaimAllocations';

const { TabPane } = Tabs;

const WorkflowSidebar: React.FC = () => {
  const [currentTab, setCurrentTab] = useState(null);
  const [claimCatRefresh, setClaimCatRefresh] = useState(0);
  const [claimTypeRefresh, setClaimTypeRefresh] = useState(0);
  const [claimPkgRefresh, setClaimPkgRefresh] = useState(0);
  const [claimAllocRefresh, setClaimAllocRefresh] = useState(0);

  useEffect(() => {
    if (currentTab == 1) {
      setClaimCatRefresh((prev) => prev + 1);
    } else if (currentTab == 2) {
      setClaimTypeRefresh((prev) => prev + 1);
    } else if (currentTab == 3) {
      setClaimPkgRefresh((prev) => prev + 1);
    } else if (currentTab == 4) {
      setClaimAllocRefresh((prev) => prev + 1);
    }
  }, [currentTab]);

  return (
    <PageContainer style={{ background: 'white' }}>
      <Tabs
        defaultActiveKey="0"
        tabPosition={'left'}
        onChange={(val) => {
          console.log(val);
          setCurrentTab(val);
        }}
        style={{ height: 900 }}
      >
        {
          <>
            <TabPane tab={'Claim Categories'} key={1}>
              <ClaimCategory refresh={claimCatRefresh} />
            </TabPane>
            <TabPane tab={'Claim Types'} key={2}>
              <ClaimTypes refresh={claimTypeRefresh} />
            </TabPane>
            <TabPane tab={'Claim Assignments'} key={3}>
              <ClaimAssignments refresh={claimPkgRefresh} />
            </TabPane>
            <TabPane tab={'Claim Allocations'} key={4}>
              <ClaimAllocations refresh={claimAllocRefresh} />
            </TabPane>
          </>
        }
      </Tabs>
    </PageContainer>
  );
};

export default WorkflowSidebar;
