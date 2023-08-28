import { getEmployee, getEmployeeCurrentDetails } from '@/services/employee';
import { EnvironmentOutlined } from '@ant-design/icons';
import _ from 'lodash';
import { Avatar, Col, Popover, Row } from 'antd';
import React, { useEffect, useState } from 'react';
import styles from './index.less';

export type businessCardProps = {
  employeeId?: string;
  employeeData?: any;
  text: string;
};

export type employeeData = {
  firstName: string;
  lastName: string;
  departmentName: string;
  divisionName: string;
  locationName: string;
  jobTitleName: string;
  imageUrl: string;
};

const BusinessCard: React.FC<businessCardProps> = (props) => {
  const [employeeFirstName, setFirstName] = useState('');
  const [employeeFullName, setFullName] = useState('');
  const [jobTitle, setJobtitle] = useState('');
  // const [department, setDepartment] = useState('');
  // const [division, setDivision] = useState('');
  const [location, setLocation] = useState('');
  const [image, setImage] = useState('');
  const { employeeId, employeeData, text } = props;

  const getEmployeeJobData = async (employeeId: string) => {
    const employeeData = await getEmployee(employeeId);
    const job = await getEmployeeCurrentDetails(employeeData.data.currentJobsId);
    await setFirstName(_.get(employeeData, 'data.firstName', '---').charAt(0).toUpperCase());
    await setFullName(_.get(employeeData, 'data.employeeName', '---'));
    // await setDepartment(_.get(job, 'data.departmentName', '---'));
    // await setDivision(_.get(job, 'data.divisionName', '---'));
    await setLocation(_.get(job, 'data.locationName', '---'));
    await setJobtitle(_.get(job, 'data.jobTitle', '---'));
    await setImage(_.get(employeeData, 'data.imageUrl', ''));
  };

  const setEmployeeData = (employeeData: employeeData) => {
    // const { firstName, lastName, departmentName, divisionName, locationName, jobTitleName, imageUrl } = employeeData;
    const { firstName, lastName, locationName, jobTitleName, imageUrl } = employeeData;
    const fullName = `${firstName} ${lastName}`;
    setFirstName(firstName);
    setFullName(fullName);
    // setDepartment(departmentName);
    // setDivision(divisionName);
    setLocation(locationName);
    setJobtitle(jobTitleName);
    setImage(imageUrl);
  };

  useEffect(() => {
    if (employeeId != undefined) {
      getEmployeeJobData(employeeId);
    }
    if (employeeData != undefined) {
      setEmployeeData(employeeData);
    }
  }, [employeeId, employeeData]);
  const colors = ['#00AA55', '#009FD4', '#B381B3', '#939393', '#E3BC00', '#D47500', '#DC2A2A'];
  const popoverContent = () => {
    return (
      <div>
        <Row>
          <Col flex={2} className={styles.popoverAvatar}>
            {image ? (
              <Avatar className={styles.popoverAvatarContent} src={image} size={96}>
                {employeeFirstName}
              </Avatar>
            ) : (
              <Avatar
                className={styles.popoverAvatarContent}
                style={{ backgroundColor: colors[Math.floor(Math.random() * 7)] }}
                size={96}
              >
                {employeeFullName.split(' ').map(x => x[0]).join('')}
              </Avatar>
            )}
          </Col>
          <Col flex={3} className={styles.popoverInfo}>
            <div className={styles.popoverName}>{employeeFullName}</div>
            <div className={styles.popoverPosition}>{jobTitle}</div>
            {/* <div className={styles.popoverDivision}>{division}</div> */}
            {/* <div className={styles.popOverDepartment}>{department}</div> */}
          </Col>
        </Row>
        <div className={styles.location}>
          <EnvironmentOutlined className={styles.icon} />
          {location}
        </div>
      </div>
    );
  };
  return <Popover content={popoverContent()}>{text}</Popover>;
};

export default BusinessCard;
