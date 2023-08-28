import EmployeeGroups from '@/components/LeaveConfigTab/EmployeeGroups';
import General from '@/components/LeaveConfigTab/General';
import LeaveAccure from '@/components/LeaveConfigTab/LeaveAccure';
import WhoCanApply from '@/components/LeaveConfigTab/WhoCanApply';
import PermissionDeniedPage from '@/pages/403';
import { getCountries } from '@/services/countryService';
import { getLeaveType } from '@/services/leaveEntitlment';
import { PageContainer } from '@ant-design/pro-layout';
import { Card, Col, Row, Tabs } from 'antd';
import React, { useEffect, useState } from 'react';
import { Access, Link, useAccess, useParams } from 'umi';
import { getAllEmployeeGroupsByLeaveTypeId } from '@/services/leave';


const Config=(props)=> {

const [selectedLeaveType, setSelectedLeaveType]=useState({})
const [addButtonVisible, setAddButtonVisible]=useState(true)
const [employeeGroupOptions,setEmployeeGroupOptions]=useState([])
const [currentTab,setCurrentTab]=useState(null)
const { TabPane } = Tabs;
const access = useAccess();
const { hasPermitted } = access;
const { id } = useParams();

useEffect(() => {
    fetchData()

}, [])   

useEffect(() => {
    if ((currentTab == 1 || currentTab == 4) && selectedLeaveType.id) {
      getGroupData(selectedLeaveType.id);
    }

}, [currentTab])   

  const  fetchData = async()=>{
   try{
    const leaveData =  await getLeaveType(id)
    const {data} = leaveData;
    if(data){
      // eslint-disable-next-line no-restricted-syntax
        console.log(leaveData);
        await setSelectedLeaveType(leaveData.data);
        getGroupData(id);
    }

   }
   catch(err){
       console.error(err)
   }

  }

  const getGroupData = async (leaveTypeId) => {
    let params = {
        leaveTypeId: leaveTypeId
    }

    const employeeGroups=await getAllEmployeeGroupsByLeaveTypeId(params);

    let arr = [];
    if(employeeGroups.data){
        arr = employeeGroups.data.map(el=>{
            return {
                value:el.id,
                label:el.name,
                disabled: false

            }
        })
        setEmployeeGroupOptions(arr);

    }

}


  return (
    <Access
    accessible={hasPermitted('leave-type-config')}
    fallback={<PermissionDeniedPage />}
>
        <PageContainer
        header={{
            title: selectedLeaveType.name,

          }}
        >
<Card>
<Tabs tabPosition={"left"} onChange={(val)=>{
    console.log(val);
    setCurrentTab(val);
    setAddButtonVisible(true)
}}>
        <TabPane  tab="General" key="1">
        <General employeeGroupOptions = {employeeGroupOptions} type={"General"} fetchLeaveTypeData={fetchData} sub="Set basic rules for each leave period." data={ selectedLeaveType}/>
        </TabPane>
        {
          selectedLeaveType.employeesCanApply ? 
          <TabPane tab="Who Can Apply" key="2">
          <WhoCanApply type="Who Can Apply" sub={"Set basic rules for each employee groups."} data={ selectedLeaveType}/>
          </TabPane> : <></>
        }
        <TabPane tab="Employee Group" key="3"> 
        <EmployeeGroups type={"Employee Groups"} sub={"Set basic rules for each employee groups."} addButtonVisible={addButtonVisible} data={ selectedLeaveType} setAddButtonVisible={setAddButtonVisible}/>
        </TabPane>
        <TabPane tab="Leave Accrue" key="4">
        <LeaveAccure employeeGroupOptions = {employeeGroupOptions}  type={"Accrual Rules"} sub={"Set up accrual rules for a specific Leave Type"} data={ selectedLeaveType}/>
        </TabPane>
      </Tabs>
    
</Card>
        </PageContainer>
        </Access>
       
    );
}

export default Config;