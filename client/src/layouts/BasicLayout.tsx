/**
 * Ant Design Pro v4 use `@ant-design/pro-layout` to handle Layout.
 *
 * @see You can view component api by: https://github.com/ant-design/ant-design-pro-layout
 */
import type {
  MenuDataItem,
  BasicLayoutProps as ProLayoutProps,
  Settings,
} from '@ant-design/pro-layout';
import ProLayout, { DefaultFooter } from '@ant-design/pro-layout';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import type { Dispatch } from 'umi';
import { Link, useIntl, connect, history } from 'umi';
import { Result, Button, ConfigProvider, Row } from 'antd';
import Authorized from '@/utils/Authorized';
import RightContent from '@/components/GlobalHeader/RightContent';
import type { ConnectState } from '@/models/connect';
import { getMatchMenu } from '@umijs/route-utils';
import logo from '../assets/logo.svg';
import logoCollapsed from '../assets/logoCollapsed.svg';
import SidebarIcons from '../components/SidebarIcons';
import en_US from 'antd/lib/locale-provider/en_US';
import { getCompanyImages } from '@/services/company';

const noMatch = (
  <Result
    status={403}
    title="403"
    subTitle="Sorry, you are not authorized to access this page."
    extra={
      <Button type="primary">
        <Link to="/auth/login">Go Login</Link>
      </Button>
    }
  />
);
export type BasicLayoutProps = {
  breadcrumbNameMap: Record<string, MenuDataItem>;
  route: ProLayoutProps['route'] & {
    authority: string[];
  };
  settings: Settings;
  collapsed: boolean;
  dispatch: Dispatch;
} & ProLayoutProps;
export type BasicLayoutContext = { [K in 'location']: BasicLayoutProps[K] } & {
  breadcrumbNameMap: Record<string, MenuDataItem>;
};
/** Use Authorized check all menu item */

const menuDataRender = (menuList: MenuDataItem[]): MenuDataItem[] =>
  menuList.map((item) => {
    const localItem = {
      ...item,
      icon: <SidebarIcons icon={item.svgIcon} />,
      children: item.children ? menuDataRender(item.children) : undefined,
    };
    return Authorized.check(item.authority, localItem, null) as MenuDataItem;
  });
const version = VERSION ? ' | '.concat(VERSION) : '';

// const defaultFooterDom = (
//   <DefaultFooter
//     copyright={`${new Date().getFullYear()} ICONN Labs Pvt Ltd.All rights reserved. `.concat(
//       `${version}`,
//     )}
//     links={
//       [
//         // {
//         //   key: 'Ant Design Pro',
//         //   title: 'Ant Design Pro',
//         //   href: 'https://pro.ant.design',
//         //   blankTarget: true,
//         // },
//         // {
//         //   key: 'github',
//         //   title: <GithubOutlined />,
//         //   href: 'https://github.com/ant-design/ant-design-pro',
//         //   blankTarget: true,
//         // },
//         // {
//         //   key: 'Ant Design',
//         //   title: 'Ant Design',
//         //   href: 'https://ant.design',
//         //   blankTarget: true,
//         // },
//       ]
//     }
//   />
// );

const BasicLayout: React.FC<BasicLayoutProps> = (props) => {
  const {
    dispatch,
    children,
    settings,
    collapsed,
    location = {
      pathname: '/',
    },
  } = props;

  const menuDataRef = useRef<MenuDataItem[]>([]);
  const [iconImg, setIconImg] = useState('');
  const [coverImg, setCoverImg] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const getImages = async () => {
      try {
        setLoading(true);
        const { data } = await getCompanyImages(null);
        const { icon, cover } = data;
        console.log(icon);
        if (icon && icon.data !== undefined) {
          setIconImg(icon.data);
        }
        if (cover && cover.data !== undefined) {
          setCoverImg(cover.data);
        }
        setLoading(false);
      } catch (error) {
        console.error(error);
      }
    };
    getImages();
    if (dispatch) {
      dispatch({
        type: 'user/fetchCurrent',
      });
    }
  }, []);
  /** Init variables */

  const handleMenuCollapse = (payload: boolean): void => {
    if (dispatch) {
      dispatch({
        type: 'global/changeLayoutCollapsed',
        payload,
      });
    }
  };
  const getClassName = () => {
    if (collapsed) {
      if (iconImg) {
        return 'upload-collaps-menu-header-logo';
      } else {
        return 'collaps-menu-header-logo';
      }
    } else {
      if (iconImg) {
        return 'upload-non-collaps-menu-header-logo';
      } else {
        return 'non-collaps-menu-header-logo';
      }
    }
  };
  // get children authority
  const authorized = useMemo(
    () =>
      getMatchMenu(location.pathname || '/', menuDataRef.current).pop() || {
        authority: undefined,
      },
    [location.pathname],
  );

  const { formatMessage } = useIntl();

  return (
    <ProLayout
      siderWidth={260}
      // logo={collapsed ? logoCollapsed : logo}
      logo={
        collapsed && iconImg
          ? iconImg
          : !collapsed && iconImg
          ? iconImg
          : !collapsed && !iconImg
          ? logo
          : logoCollapsed
      }
      // logo={iconImg}
      formatMessage={formatMessage}
      {...props}
      {...settings}
      onCollapse={handleMenuCollapse}
      breakpoint={false}
      onMenuHeaderClick={() => history.push('/')}
      menuItemRender={(menuItemProps, defaultDom) => {
        if (
          menuItemProps.isUrl ||
          !menuItemProps.path ||
          location.pathname === menuItemProps.path
        ) {
          return defaultDom;
        }
        return (
          <Link data-key={`nav-${menuItemProps.dataKey}`} to={menuItemProps.path}>
            {' '}
            {defaultDom}
          </Link>
        );
      }}
      // breadcrumbRender={(routers = []) => [
      //   {
      //     path: '/',
      //     breadcrumbName: formatMessage({ id: 'menu.home' }),
      //   },
      //   ...routers,
      // ]}
      itemRender={(route, params, routes, paths) => {
        const first = routes.indexOf(route) === 0;
        const last = paths[paths.length - 1];
        // if (first) {
        //   return <Link to={paths.join('/')}>{route.breadcrumbName}</Link>;
        // }
        // if (!route.component) {
        //   return <span> {route.breadcrumbName}</span>;
        // }
        // return <Link to={`/${last}`}>{route.breadcrumbName}</Link>;
      }}
      // footerRender={() => {
      //   if (settings.footerRender || settings.footerRender === undefined) {
      //     return defaultFooterDom;
      //   }
      //   return null;
      // }}
      menuDataRender={menuDataRender}
      rightContentRender={() => <RightContent />}
      // postMenuData={(menuData) => {
      //   menuDataRef.current = menuData || [];
      //   return menuData || [];
      // }}
      menuHeaderRender={(_logo, _title) => (
        <Row justify="center" className={getClassName()}>
          {_logo}
        </Row>
      )}
    >
      <Authorized authority={authorized!.authority} noMatch={noMatch}>
        <ConfigProvider locale={en_US}>{children}</ConfigProvider>
      </Authorized>
    </ProLayout>
  );
};

export default connect(({ global, settings }: ConnectState) => ({
  collapsed: global.collapsed,
  settings,
}))(BasicLayout);
