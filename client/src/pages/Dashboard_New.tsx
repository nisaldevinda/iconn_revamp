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
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
                <div className="todo-item">
                  <div className="todo-check">
                    <input type="checkbox" name="" id="" className="todo-checkbox" />
                  </div>
                  <div className="todo-content">
                    <div className="todo-details">
                      <span className="todo-type">Profile Manager</span>
                      <span className="todo-name">Pending Profile Change Requests</span>
                      <span className="todo-status">to be approved</span>
                    </div>
                    <div>
                      <button className="view-more-btn">View</button>
                    </div>
                  </div>
                </div>
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
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-1.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Melody Tissera</span>
                      <span className="date">27th of July, 2023</span>
                      <span className="user-description">Turns 35 Today</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card-sub-header">
                  <span className="card-sub-heading">Upcoming Birthdays</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-3.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Madhawa Bandara</span>
                      <span className="date">30th of July, 2023</span>
                      <span className="user-description">Turns 32</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-2.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Adisha Gammanpila</span>
                      <span className="date">29th of July, 2023</span>
                      <span className="user-description">Turns 29</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-1.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Melody Tissera</span>
                      <span className="date">27th of July, 2023</span>
                      <span className="user-description">Turns 35 Today</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
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
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-6.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Chamarie Ukwatte</span>
                      <span className="date">27th of July, 2023</span>
                      <span className="user-description">4th Anniversary</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card-sub-header">
                  <span className="card-sub-heading">Upcoming Anniversaries</span>
                  <div className="card-link">View All</div>
                </div>
                <div className="user-profiles">
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-5.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Kasun Meediniya</span>
                      <span className="date">30th of July, 2023</span>
                      <span className="user-description">6th Anniversary</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-4.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Praveen Ranula</span>
                      <span className="date">29th of July, 2023</span>
                      <span className="user-description">3rd Anniversary</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
                  <div className="user-profile">
                    <div className="user-img-wrap">
                      <img src="/users/user-1.png" alt="" className="user-img" />
                    </div>
                    <div className="user-info">
                      <span className="user-name">Melody Tissera</span>
                      <span className="date">27th of July, 2023</span>
                      <span className="user-description">Turns 35 Today</span>
                      <div className="user-actions">
                        <a href="" className="card-link">
                          View
                        </a>
                        <button className="msg-btn">Message</button>
                      </div>
                    </div>
                  </div>
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
