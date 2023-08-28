import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
    Form,
    Row,
    Col,
    Select,
    Button,
    Card,
    Space,
    message as Message,
    Input,
    Typography,
    Transfer,
    DatePicker,
    Spin,
    Divider,
    Checkbox
} from 'antd';
import { useParams, history, useAccess, Access, useIntl } from 'umi';

import ProForm, { ProFormSelect } from "@ant-design/pro-form";
import OrgSelector from '@/components/OrgSelector';

import PermissionDeniedPage from './../403';
import { getEmployeeListForDepartment, getEmployeeListForLocation, getEmployeeListForDepartmentAndLocation } from '@/services/dropdown';
import { getAllWorkPatterns, getWorkPatternEmployees, assignWorkPattern } from '@/services/workPattern';
import { getAllLocations } from '@/services/location';
import { getWorkShiftsList } from '@/services/workShift';
import styles from './styles.less';
import { getAllDepartment } from '@/services/department';
import { assignShifts } from '@/services/shiftAssign';
import { getAssignShifts, getUnAssignedEmployeeList } from '@/services/shiftAssign';
import moment from 'moment';
import './styles.css';

export default (): React.ReactNode => {
    const { Option } = Select;
    const intl = useIntl();
    const { TextArea } = Input;
    const { id } = useParams<IParams>();
    const [form] = Form.useForm();
    const [shifts, setShifts] = useState([]);
    const [loading, setLoading] = useState<boolean>(false);
    const [selectedEmployees, setSelectedEmployees] = useState([]);
    const [enableLocationFiltering, setEnableLocationFiltering] = useState<boolean>(false);
    const [enableOrgStructureFiltering, setEnableOrgStructureFiltering] = useState<boolean>(false);
    const [orgEntityId, setOrgEntityId] = useState(null);
    const [selectedPattern, setSelectedPattern] = useState('');
    const [locations, setLocations] = useState([]);
    const [targetKeys, setTargetKeys] = useState<string[]>([]);
    const [locationId, setLocationId] = useState('');
    const [departmentId, setDepartmentId] = useState('');
    const [employeesList, setEmployeesList] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [assignedEmployees , setAssignedEmployees] = useState([]);
    const access = useAccess();
    const { hasPermitted } = access;

    const onFinish = async (formData: any) => {

        const requestData = formData;
        requestData.selectedEmployees = targetKeys;
        requestData.effectiveDate = formData.effectiveDate ? moment(formData.effectiveDate).format("YYYY-MM-DD") : null;

        try {
            const { message, data } = await assignWorkPattern(requestData);
            Message.success(message);

        } catch (err) {
            console.log(err);
        }

    };

    useEffect(() => {
        fetchData();
    }, []);

    useEffect(() => {
        if (selectedPattern) {
            getShiftUnAssignEmployees(locationId, orgEntityId);
        }
    }, [locationId, orgEntityId]);

    const getShiftUnAssignEmployees = async(locId, orgId) => {
        let params = {
            'locationId' : locId,
            'orgEntityId': orgId,
            'targetKeys' : JSON.stringify(targetKeys)
        }

        const { data } = await getUnAssignedEmployeeList(params);
        const employeeArray = data.map(employee => {
            return {
                title: employee.employeeNumber+' | '+employee.employeeName,
                key: employee.id
            }
        });
        const targetArray = employeesList.filter((employee) =>{
           if (targetKeys.includes(employee.key)) {
            return employee;
           }
        });
    
        const sourceEmployeeList = employeeArray.concat(targetArray);
        setEmployeesList(sourceEmployeeList);
    } 

    const fetchData = async () => {
        try {

            const { data } = await getAllWorkPatterns();
            setShifts(data);

            const locationData = await getAllLocations();
            const locationArray = Object.values(locationData?.data.map(location => {
                return {
                    label: location.name,
                    value: location.id
                };
            }));
            setLocations(locationArray);

            const departmentData = await getAllDepartment();
            const departmentArray = Object.values(departmentData?.data.map(department => {
                return {
                    label: department.name,
                    value: department.id
                };
            }));
            setDepartments(departmentArray);

        } catch (error) {
            Message.error({
                content: error.message ? (
                    <>
                        {errorMessage ?? error.message}
                        <br />
                        <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                            {errorMessageInfo ?? ''}
                        </span>
                    </>
                ) : (
                    intl.formatMessage({
                        id: 'failedToLoas',
                        defaultMessage: 'Cannot Load Workshifts',
                    })
                ),
                key,
            });
        }
    }

    return (
        <Access
            accessible={hasPermitted('document-template-read-write')}
            fallback={<PermissionDeniedPage />}
        >
            <PageContainer loading={loading}>
                <Card>
                    <Spin spinning={loading}>
                        <Col offset={1} span={20}>
                            <Form
                                form={form}
                                layout="vertical"
                                onFinish={onFinish}
                            >
                                <Row>
                                    <Col span={5}>
                                        <Form.Item
                                            name="workPatternId"
                                            label={intl.formatMessage({
                                                id: 'workPatternAssign.workPatternId',
                                                defaultMessage: 'Work Pattern',
                                            })}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: intl.formatMessage({
                                                        id: 'workPatternAssign.workPatternId.required',
                                                        defaultMessage: 'Required',
                                                    }),
                                                }
                                            ]}
                                            className={styles.shiftField}
                                        >
                                            <Select
                                                showSearch

                                                placeholder={intl.formatMessage({
                                                    id: 'workPatternAssign.workPattern.placeholder',
                                                    defaultMessage: '-Select Work Pattern-',
                                                })}
                                                optionFilterProp="children"
                                                onChange={async (value) => {
                                                    setLoading(true);

                                                    let params = {
                                                        'locationId' : enableLocationFiltering ?  locationId : null,
                                                        'orgEntityId': enableOrgStructureFiltering ? orgEntityId : null,
                                                        'targetKeys' : targetKeys ? JSON.stringify(targetKeys) : []
                                                    }
                                                    const { data } = await getUnAssignedEmployeeList(params);
                                                    const employeeArray = data.map(employee => {
                                                        return {
                                                            title: employee.employeeNumber+' | '+employee.employeeName,
                                                            key: employee.id
                                                        }
                                                    });
                                                    setSelectedPattern(value);

                                                    const assignedEmp = await getWorkPatternEmployees(value);

                                                    const employeeAssignedArray = assignedEmp.data.map(employee => {
                                                        return {
                                                            title: employee.employeeNumber+' | '+employee.employeeName,
                                                            key: employee.employeeId
                                                        }
                                                    });
                                                    setAssignedEmployees(employeeAssignedArray);
                                                    const targetArray = assignedEmp.data.map(employee => {
                                                        return employee.employeeId
                                                    });

                                                    const sourceEmployeeList = employeeArray.concat(employeeAssignedArray);
                                                    setEmployeesList(sourceEmployeeList);
                                                    setTargetKeys(targetArray);

                                                    setLoading(false);

                                                }}
                                            >
                                                {shifts.map((shift) => {
                                                    return (
                                                        <Option key={shift.id} value={shift.id}>
                                                            {shift.name}
                                                        </Option>
                                                    );
                                                })}
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                </Row>
                                <Divider/>
                                {/* <Row style={{fontSize: 20}}>Filter By</Row> */}
                                <Row style={{marginTop: 20}}>
                                    <Col span={2}>
                                        <h3>Filtered By</h3>
                                    </Col>
                                    <Col span={4}>
                                        <Checkbox
                                            name="filterByOrgStructure"
                                            className={styles.inOvertimeField}
                                            onChange={(value) => {
                                                setEnableOrgStructureFiltering(value.target.checked);

                                                if (!value.target.checked) {
                                                    setOrgEntityId(null);
                                                } else {
                                                    setOrgEntityId(1);
                                                }

                                            }}
                                            checked={enableOrgStructureFiltering}
                                        >

                                            {intl.formatMessage({
                                                id: 'workShifts.filterByOrgStructure',
                                                defaultMessage: 'Organizational Structure',
                                            })}
                                        </Checkbox>
                                    </Col>
                                    <Col span={3}>
                                        <Checkbox
                                            name="filterByLocation"
                                            className={styles.inOvertimeField}
                                            onChange={(value) => {
                                                setEnableLocationFiltering(value.target.checked);

                                                if (!value.target.checked) {
                                                    setLocationId(null);
                                                }
                                            }}
                                            checked={enableLocationFiltering}
                                        >

                                            {intl.formatMessage({
                                                id: 'workShifts.filterByLocation',
                                                defaultMessage: 'Location',
                                            })}
                                        </Checkbox>
                                    </Col>
                                </Row>

                                {/* <Row>
                                    <Col span={5}>
                                        <ProFormSelect
                                            name="location"
                                            label={intl.formatMessage({
                                                id: 'shiftAssign.SELECT_LOCATION',
                                                defaultMessage: 'Location',
                                            })}
                                            options={locations}

                                            placeholder={intl.formatMessage({
                                                id: 'shiftAssign.location.placeholder',
                                                defaultMessage: '-Select Location-',
                                            })}
                                            onChange={async (value) => {
                                                setLocationId(value);

                                                const {data} = await getEmployeeListForLocation(value);
                                                const employeeWiseLocationArray = data.map((employee) =>{
                                                    return {
                                                        title: employee.employeeName,
                                                        key: employee.id
                                                    }
                                                });
                                                const targetArray = employeesList.filter((employee) =>{
                                                   if (targetKeys.includes(employee.key)) {
                                                    return employee;
                                                   }
                                               });
                                             
                                                const sourceEmployeeList = employeeWiseLocationArray.concat(targetArray);
                                                setEmployeesList(sourceEmployeeList);
                                            }}
                                        />
                                    </Col>
                                    <Col style={{marginLeft: 50}}  span={5}>
                                        <ProFormSelect
                                            name="location"
                                            label={intl.formatMessage({
                                                id: 'shiftAssign.SELECT_LOCATION',
                                                defaultMessage: 'Location',
                                            })}
                                            options={locations}

                                            placeholder={intl.formatMessage({
                                                id: 'shiftAssign.location.placeholder',
                                                defaultMessage: '-Select Location-',
                                            })}
                                            onChange={async (value) => {
                                                setLocationId(value);

                                                const {data} = await getEmployeeListForLocation(value);
                                                const employeeWiseLocationArray = data.map((employee) =>{
                                                    return {
                                                        title: employee.employeeName,
                                                        key: employee.id
                                                    }
                                                });
                                                const targetArray = employeesList.filter((employee) =>{
                                                   if (targetKeys.includes(employee.key)) {
                                                    return employee;
                                                   }
                                               });
                                             
                                                const sourceEmployeeList = employeeWiseLocationArray.concat(targetArray);
                                                setEmployeesList(sourceEmployeeList);
                                            }}
                                        />
                                    </Col> */}
                                    {/* <Col span={5} className={styles.department}>
                                        <ProFormSelect
                                            name="department"
                                            label={intl.formatMessage({
                                                id: 'shiftAssign.SELECT_DEPARTMENT',
                                                defaultMessage: 'Department',
                                            })}
                                            options={departments}

                                            placeholder={intl.formatMessage({
                                                id: 'shiftAssign.department.placeholder',
                                                defaultMessage: '-Select Department-',
                                            })}
                                            onChange={async (value) => {

                                                setDepartmentId(value);
                                                let departmentArray = [] ;

                                                if (locationId && value) {
                                                    const requestData = {
                                                        locationId: locationId,
                                                        departmentId: value
                                                    }
                                                    departmentArray  = await getEmployeeListForDepartmentAndLocation(requestData);
                                              
                                                } else {
                                                    departmentArray  = await getEmployeeListForDepartment(value);
                                                }
                                                const employeeArray = departmentArray.data.map(employee => {
                                                    return {
                                                        title: employee.employeeName,
                                                        key: employee.id
                                                    }
                                                });

                                                const targetArray = employeesList.filter((employee) =>{
                                                    if (targetKeys.includes(employee.key)) {
                                                     return employee;
                                                    }
                                                });
                                                const sourceEmployeeList = employeeArray.concat(targetArray);
                                                setEmployeesList(sourceEmployeeList);
                            
                                            }}
                                        />
                                    </Col> */}
                                {/* </Row> */}
                                {
                                    enableOrgStructureFiltering ? (
                                        <>
                                            <Row style={{marginTop: 25}}><h3>Filter Employees By Organization Structure</h3></Row>
                                            <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                                                <Col span={17}>
                                                    <Row gutter={24}>
                                                    <OrgSelector
                                                        value={orgEntityId}
                                                        setValue={(orgEntityId: number) => {
                                                            setOrgEntityId(orgEntityId);
                                                        
                                                        }}
                                                    />
                                                    </Row>
                                                </Col>
                                            </Row>
                                            <Divider></Divider>
                                        </>
                                    ) : (
                                        <></>
                                    )
                                }
                                {
                                    enableLocationFiltering ? (
                                        <>
                                            <Row style={{marginTop: 25}}><h3>Filter Employees By Location</h3></Row>
                                            <Row>
                                                <Col span={8}>
                                                    <ProFormSelect
                                                        width="lg"
                                                        name="location"
                                                        label={intl.formatMessage({
                                                            id: 'shiftAssign.SELECT_LOCATION',
                                                            defaultMessage: 'Location',
                                                        })}
                                                        options={locations}

                                                        placeholder={intl.formatMessage({
                                                            id: 'shiftAssign.location.placeholder',
                                                            defaultMessage: '-Select Location-',
                                                        })}
                                                        onChange={async (value) => {
                                                            setLocationId(value);
                                                        }}
                                                    />
                                                </Col>
                                            </Row>
                                            <Divider></Divider>
                                        </>
                                    ) : (
                                        <></>
                                    )
                                }
                                <Row style={{marginTop: 25}}>
                                    <Col span={18}>
                                        <Form.Item

                                            label={intl.formatMessage({
                                                id: 'shiftAssign.selectEmployees',
                                                defaultMessage: 'Select Employees',
                                            })}

                                        >
                                            <Transfer
                                                dataSource={employeesList}
                                                showSearch
                                                filterOption={(search, item) => { return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0; }}
                                                targetKeys={targetKeys}
                                                onChange={(newTargetKeys: string[]) => {
                                                    setTargetKeys(newTargetKeys);
                                                }}

                                                render={item => item.title}
                                                listStyle={{
                                                    width: 300,
                                                    height: 300,
                                                    marginBottom: 20
                                                }}
                                                locale={{
                                                    itemUnit: "Employee",
                                                    itemsUnit: "Employees",
                                                }}
                                            />

                                        </Form.Item>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={6}>
                                        <Form.Item
                                            name='effectiveDate'
                                            label={intl.formatMessage({
                                                id: 'effectiveDate',
                                                defaultMessage: ' Effective Date',
                                            })}

                                        >
                                            <DatePicker
                                                format={'DD-MM-YYYY'}
                                                placeholder={intl.formatMessage({
                                                    id: 'shiftAssign.effectiveDate.placeholder',
                                                    defaultMessage: '-Select Date-',
                                                })}
                                            />
                                        </Form.Item>
                                    </Col>
                                </Row>

                                <Row>
                                    <Col span={24} className={styles.footer}>
                                        <Form.Item>
                                            <Space>
                                                <Button
                                                    htmlType="button"
                                                    onClick={() => {
                                                        form.resetFields();
                                                        setTargetKeys([]);
                                                        setEmployeesList([]);

                                                    }}
                                                >
                                                    {intl.formatMessage({
                                                        id: 'shiftAssign.reset',
                                                        defaultMessage: 'Reset',
                                                    })}
                                                </Button>
                                                <Button type="primary" htmlType="submit">
                                                    {intl.formatMessage({
                                                        id: 'shiftAssign.save',
                                                        defaultMessage: 'Save',
                                                    })}
                                                </Button>
                                            </Space>
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Form>
                        </Col>
                    </Spin>
                </Card>
            </PageContainer>
        </Access>
    );
};
