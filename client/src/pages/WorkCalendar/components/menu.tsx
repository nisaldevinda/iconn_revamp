import React from 'react';
import { Menu, Spin } from 'antd';
import _ from 'lodash';

const { SubMenu } = Menu;

type MenuTitleData = {
  title: React.ReactNode;
  icon: React.ReactNode | undefined;
  key: string | number;
};

type MenuItemData = {
  key: string | number;
  menuItemName: string;
  calendarId: string;
  month: string;
  year: string;
};
interface WorkCalanderMenuProps {
  deafultOpenKey: string[] | undefined;
  defaultSelectedKeys: string[] | undefined;
  menuItemData: MenuItemData[];
  menuOnClick?: any;
  title: React.ReactNode | string;
  titleIcon: any;
  titleKey: string | number;
  menuWidth: number;
}

interface NavigationTitleAndItemMenuProps {
  titleData: any;
  itemData: any[];
}

const NavigationTitleAndItemMenu = (props: NavigationTitleAndItemMenuProps) => {
  if (!_.isEmpty(props.itemData) || !_.isEmpty(props.titleData) || !_.isUndefined(props)) {
    return (
      <SubMenu key="sub1" icon={props.titleData.icon} title={props.titleData.title}>
        {_.isUndefined(props.itemData) ? (
          <Spin />
        ) : (
          props.itemData.map((MenuItemData: MenuItemData) => (
            <Menu.Item key={MenuItemData.key}>{MenuItemData.menuItemName}</Menu.Item>
          ))
        )}
      </SubMenu>
    );
  } else {
    return <></>;
  }
};

const WorkCalanderMenu: React.FC<WorkCalanderMenuProps> = (props) => {
  const titleData: MenuTitleData = {
    key: props.titleKey,
    title: props.title,
    icon: props.titleIcon,
  };

  return (
    <Menu
      inlineCollapsed={false}
      theme="light"
      style={{
        width: '90%',
        borderRadius: 10,
      }}
      className="work-calendar-sidemenu"
      defaultOpenKeys={['sub1']}
      defaultSelectedKeys={['0']}
      defaultValue={1}
      mode="inline"
      onClick={props.menuOnClick}
    >
      {/* <NavigationTitleAndItemMenu itemData={props.menuItemData} titleData={titleData} /> */}
      {NavigationTitleAndItemMenu({ itemData: props.menuItemData, titleData: titleData })}
    </Menu>
  );
};

export { WorkCalanderMenu, MenuTitleData, MenuItemData };

// React.FC<NavigationTitleAndItemMenuProps>
