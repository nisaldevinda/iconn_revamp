import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import Dash_ShowcaseLayout from './Dash_ShowcaseLayout';
import { updateDashboard } from '@/services/dashboard';
import { APIResponse } from '@/utils/request';
import { Button, Col, Row } from 'antd';
import Title from 'antd/lib/typography/Title';
import { DownloadOutlined } from '@ant-design/icons';
import DrawerView from '@/components/Attendance/DrawerView';
import { Access, useAccess } from 'umi';
import PermissionDeniedPage from './403';
import { getAttendance, getLastLogged } from '@/services/attendance';
import styles from './Dashboard.less';
import _ from 'lodash';
import TimeLogButton from '@/components/Dashboard/TimeLogButton';

import './new_styles.css';
import TodoItem from './TodoItem';

export default (): React.ReactNode => {
  const [layout, setLayoutModel] = useState([]);
  const access = useAccess();
  const { hasPermitted } = access;

  const onLayoutChange = async (layout: any) => {
    setLayoutModel(layout);
    await onSaveChangedLayout(layout);
  };

  const onSaveChangedLayout = async (layoutPassed: any) => {
    // await updateDashboard(layout)
    await updateDashboard(layoutPassed)
      .then((response: APIResponse) => {})
      .catch((error: APIResponse) => {});
  };

  interface BirthdayDataItem {
    id: number;
    userName: string;
    date: string;
    userDescription: string;
    userImageSrc: string;
  }

  interface TodoDataItem {
    id: number;
    type: string;
    name: string;
    status: string;
  }

  const [todoData, setTodoData] = useState<TodoDataItem[]>([]);
  const [birthdayData, setBirthdayData] = useState<BirthdayDataItem[]>([]);
  const [anniversaryData, setAnniversaryData] = useState([]);

  useEffect(() => {
    // fetch('/api/todo')
    //   .then((response) => response.json())
    //   .then((data) => setTodoData(data))
    //   .catch((error) => console.error('Error fetching ToDo data:', error));

    fetch('/api/birthdays')
      .then((response) => response.json())
      .then((data) => setBirthdayData(data))
      .catch((error) => console.error('Error fetching Birthdays data:', error));

    fetch('/api/anniversaries')
      .then((response) => response.json())
      .then((data) => setAnniversaryData(data))
      .catch((error) => console.error('Error fetching Anniversary data:', error));

    // Simulated API call to fetch Todo data
    setTimeout(() => {
      const sampleTodoData: TodoDataItem[] = [
        {
          id: 1,
          type: 'Leave Manager',
          name: 'Pending Profile Change Requests',
          status: 'to be approved',
        },
        {
          id: 2,
          type: 'Profile Manager',
          name: 'Pending Profile Change Requests',
          status: 'to be approved',
        },
      ];
      setTodoData(sampleTodoData);
    }, 1000);

    setTimeout(() => {
      const sampleBirthdayData: BirthdayDataItem[] = [
        {
          id: 1,
          userName: 'Melody Tissera',
          date: '27th of July, 2023',
          userDescription: 'Turns 35 Today',
          userImageSrc: '/users/user-1.png',
        },
        {
          id: 2,
          userName: 'Madhawa Bandara',
          date: '30th of July, 2023',
          userDescription: 'Turns 32',
          userImageSrc: '/users/user-2.png',
        },
        {
          id: 3,
          userName: 'John Doe',
          date: '15th of August, 2023',
          userDescription: 'Turns 40 Today',
          userImageSrc: '/users/user-4.png',
        },
        {
          id: 4,
          userName: 'Jane Smith',
          date: '22nd of September, 2023',
          userDescription: 'Turns 28 Today',
          userImageSrc: '/users/user-5.png',
        },
        {
          id: 5,
          userName: 'Alice Johnson',
          date: '5th of October, 2023',
          userDescription: 'Turns 25 Today',
          userImageSrc: '/users/user-6.png',
        },
        // Add more birthday data items here
      ];
      setBirthdayData(sampleBirthdayData);
    }, 1000);
  }, []);

  return (
    <>
      <div className="dashboard">
        <div className="column">
          {/* Todo */}
          <div className="section section-height-100 overflow-scroll">
            <div className="card-header-wrap">
              <div className="card-header">
                <div className="card-title">To Do</div>
                <select name="to-do-type" id="to-do-type" className="card-dropdown">
                  <option value="all">All</option>
                  <option value="leave-manager">Leave Manager</option>
                  <option value="attendance-manager">Attendance Manager</option>
                  <option value="profile-manager">Profile Manager</option>
                  <option value="document-manager">Document Manager</option>
                  <option value="expense-mamager">Expense Manager</option>
                </select>
                <div className="card-link">View All</div>
              </div>
            </div>
            <div className="card-body-wrap">
              <div className="todo-items">
                {todoData.map((todoItem) => (
                  // Use the TodoItem component to render dynamic content
                  <TodoItem
                    key={todoItem.id}
                    type={todoItem.type}
                    name={todoItem.name}
                    status={todoItem.status}
                  />
                ))}
              </div>
            </div>
          </div>
        </div>

        <div className="column">
          <div className="row">
            {/* Birthdays */}
            <div className="section section-height-50 overflow-scroll">
              <div className="card-header-wrap">
                <div className="card-header">
                  <div className="card-title">Birthdays</div>
                  <div className="card-search">
                    <input type="text" className="search-input" placeholder="Search" />
                    <button type="submit" className="search-btn">
                      &#128269;
                    </button>
                  </div>
                </div>
              </div>
              <div className="card-body-wrap">
                <div className="card-sub-header">
                  <span className="card-sub-heading">Birthdays Today</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  {birthdayData.map((birthdayItem) => (
                    <div className="user-profile" key={birthdayItem.id}>
                      <div className="user-img-wrap">
                        <img src="/users/user-1.png" alt="" className="user-img" />
                      </div>
                      <div className="user-info">
                        <span className="user-name">{birthdayItem.userName}</span>
                        <span className="date">{birthdayItem.date}</span>
                        <span className="user-description">{birthdayItem.userDescription}</span>
                        <div className="user-actions">
                          <a href="" className="card-link">
                            View
                          </a>
                          <button className="msg-btn">Message</button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="card-sub-header">
                  <span className="card-sub-heading">Upcoming Birthdays</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  {birthdayData.map((birthdayItem) => (
                    <div className="user-profile" key={birthdayItem.id}>
                      <div className="user-img-wrap">
                        <img src="/users/user-1.png" alt="" className="user-img" />
                      </div>
                      <div className="user-info">
                        <span className="user-name">{birthdayItem.userName}</span>
                        <span className="date">{birthdayItem.date}</span>
                        <span className="user-description">{birthdayItem.userDescription}</span>
                        <div className="user-actions">
                          <a href="" className="card-link">
                            View
                          </a>
                          <button className="msg-btn">Message</button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Anniversaries */}
            <div className="section section-height-50 overflow-scroll">
              <div className="card-header-wrap">
                <div className="card-header">
                  <div className="card-title">Anniversaries</div>
                  <div className="card-search">
                    <input type="text" className="search-input" placeholder="Search" />
                    <button type="submit" className="search-btn">
                      &#128269;
                    </button>
                  </div>
                </div>
              </div>
              <div className="card-body-wrap">
                <div className="card-sub-header">
                  <span className="card-sub-heading">Anniversaries Today</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  {birthdayData.map((birthdayItem) => (
                    <div className="user-profile" key={birthdayItem.id}>
                      <div className="user-img-wrap">
                        <img src="/users/user-1.png" alt="" className="user-img" />
                      </div>
                      <div className="user-info">
                        <span className="user-name">{birthdayItem.userName}</span>
                        <span className="date">{birthdayItem.date}</span>
                        <span className="user-description">{birthdayItem.userDescription}</span>
                        <div className="user-actions">
                          <a href="" className="card-link">
                            View
                          </a>
                          <button className="msg-btn">Message</button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <div className="card-sub-header">
                  <span className="card-sub-heading">Upcoming Anniversaries</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  {birthdayData.map((birthdayItem) => (
                    <div className="user-profile" key={birthdayItem.id}>
                      <div className="user-img-wrap">
                        <img src="/users/user-1.png" alt="" className="user-img" />
                      </div>
                      <div className="user-info">
                        <span className="user-name">{birthdayItem.userName}</span>
                        <span className="date">{birthdayItem.date}</span>
                        <span className="user-description">{birthdayItem.userDescription}</span>
                        <div className="user-actions">
                          <a href="" className="card-link">
                            View
                          </a>
                          <button className="msg-btn">Message</button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="column">
          <div className="row">
            {/* Notices */}
            <div className="section section-height-50 overflow-scroll">
              <div className="card-header-wrap">
                <div className="card-header">
                  <div className="card-title">Notices</div>
                  <div className="card-search">
                    <input type="text" className="search-input" placeholder="Search" />
                    <button type="submit" className="search-btn">
                      &#128269;
                    </button>
                  </div>
                </div>
              </div>
            </div>
            {/* Shortcuts */}
            <div className="section section-height-50 overflow-scroll">
              <div className="card-header-wrap">
                <div className="card-header">
                  <div className="card-title">Shortcuts</div>
                  <div className="card-link">Add New</div>
                </div>
              </div>
              <div className="card-body-wrap">
                <div className="card-body">
                  <div className="shortcut-btns">
                    <button className="shortcut-btn">Employee Leaves</button>
                    <button className="shortcut-btn">Attendance Reports</button>
                    <button className="shortcut-btn">Organizational Structure</button>
                    <button className="shortcut-btn">Rehire Process</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};
