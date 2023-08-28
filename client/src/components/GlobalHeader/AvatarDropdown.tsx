import { LogoutOutlined, SettingOutlined, UserOutlined, ToolOutlined } from '@ant-design/icons';
import { Avatar, Divider, Menu, Row, Spin, Space } from 'antd';
import React from 'react';
import type { ConnectProps } from 'umi';
import { history, connect, Link, useAccess, Access } from 'umi';
import type { ConnectState } from '@/models/connect';
import type { CurrentUser } from '@/models/user';
import HeaderDropdown from '../HeaderDropdown';
import styles from './index.less';
import Settings from '../../assets/SideBar/settings.svg';
import access from '@/access';
export type GlobalHeaderRightProps = {
  currentUser?: CurrentUser;
  menu?: boolean;
} & Partial<ConnectProps>;

const DEFAULT_AVATAR =
  'https://t3.ftcdn.net/jpg/03/46/83/96/360_F_346839683_6nAPzbhpSkIpb8pmAwufkC7c5eD7wYws.jpg';

class AvatarDropdown extends React.Component<GlobalHeaderRightProps> {
  onMenuClick = (event: { key: React.Key; keyPath: React.Key[]; item: React.ReactInstance }) => {
    const { key } = event;

    if (key === 'logout') {
      const { dispatch } = this.props;

      if (dispatch) {
        dispatch({
          type: 'login/logout',
        });
      }

      return;
    }
    if (key === 'changePassword') {
      const { currentUser } = this.props;
      history.push(`/changePassword/${currentUser?.id}`);
      return;
    }
    history.push(`/account/${key}`);
  };

  render(): React.ReactNode {
    const { hasAnyPermission } = access();
    const hasUserHasAccessToSettingsPage = hasAnyPermission([
      'user-read-write',
      'access-levels-read-write',
      'work-shifts-read-write',
      'work-calendar-read-write',
      'work-pattern-read-write',
      'work-calendar-day-type-read-write',
      'master-data-write',
      'leave-type-config',
      'bulk-upload-read-write',
      'company-info-read-write',
      'document-template-read-write',
      'manual-process',
      'workflow-management-read-write',
      'scheduled-jobs-log',
    ]);
    const {
      currentUser = {
        avatar: '',
        firstName: '',
        lastName: '',
        fullName: '',
        email: '',
      },
      menu,
    } = this.props;
    const menuHeaderDropdown = (
      <Menu className={styles.userDropdownMenu} selectedKeys={[]} onClick={this.onMenuClick}>
        <Menu.Item className={styles.userDropdownMenuItem}>
          <Row style={{ marginBottom: '4px' }}>{currentUser.fullName}</Row>
          <Row className={styles.userDropDownEmail} style={{ marginBottom: '6px' }}>
            <span className={`${styles.fullName} anticon`}>
              {currentUser.firstName}&nbsp;
              {currentUser.lastName}
            </span>
            {currentUser.email}
          </Row>
        </Menu.Item>
        <Menu.Divider className={styles.menuDivider} />
        {menu && (
          <Menu.Item key="center">
            <UserOutlined />
            Center
          </Menu.Item>
        )}
        {menu && (
          <Menu.Item key="settings" className={styles.userDropdownMenuOptions}>
            <SettingOutlined />
            Settings
          </Menu.Item>
        )}
        {menu && <Menu.Divider />}
        <Menu.Item key="changePassword" className={styles.userDropdownMenuOptions}>
          <ToolOutlined />
          Change Password
        </Menu.Item>
        <Menu.Item key="logout" className={styles.userDropdownMenuOptions}>
          <LogoutOutlined />
          Logout
        </Menu.Item>
      </Menu>
    );

    return currentUser && currentUser.firstName && currentUser.lastName ? (
      <Space>
        <div>
          {hasUserHasAccessToSettingsPage && (
            <Link data-key="settings" to="/settings">
              <Avatar src={Settings} size={25} />
            </Link>
          )}
        </div>
        <HeaderDropdown overlay={menuHeaderDropdown} placement="bottomRight" arrow>
          <Avatar
            size="large"
            className={styles.avatar}
            src={currentUser.avatar ?? DEFAULT_AVATAR}
            alt="avatar"
          />
          {/* <span className={`${styles.fullName} anticon`}>
              {currentUser.firstName} {currentUser.lastName}
            </span> */}
        </HeaderDropdown>
      </Space>
    ) : (
      <span className={`${styles.action} ${styles.account}`}>
        <Spin
          size="large"
          style={{
            marginLeft: 8,
            marginRight: 8,
          }}
        />
      </span>
    );
  }
}

export default connect(({ user }: ConnectState) => ({
  currentUser: user.currentUser,
}))(AvatarDropdown);
