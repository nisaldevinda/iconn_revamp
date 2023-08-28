import type { MenuDataItem } from '@ant-design/pro-layout';
import { getMenuData, getPageTitle } from '@ant-design/pro-layout';
import type { ConnectProps } from 'umi';
import { Link, SelectLang, useIntl, connect } from 'umi';
import React, { useEffect, useState } from 'react';
import { Spin } from 'antd';
import type { ConnectState } from '@/models/connect';
import logo from '../assets/logo-collapsed.png';
import styles from './UserLayout.less';
import logoBottom from '../assets/logo.svg';
import { getCompanyImages } from '@/services/company';


export type UserLayoutProps = {
  breadcrumbNameMap: Record<string, MenuDataItem>;
} & Partial<ConnectProps>;

const UserLayout: React.FC<UserLayoutProps> = (props) => {
  const {
    route = {
      routes: [],
    },
  } = props;
  const { routes = [] } = route;
  const {
    children,
    location = {
      pathname: '',
    },
  } = props;

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
  }, []);

  const { formatMessage } = useIntl();
  const { breadcrumb } = getMenuData(routes);
  const title = getPageTitle({
    pathname: location.pathname,
    formatMessage,
    breadcrumb,
    ...props,
  });
  return (
    <>
      {loading ? (
        <div
          style={{
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            height: '100%',
          }}
        >
          <Spin size="large" />
        </div>
      ) : (
        <div className={styles.container} style={ coverImg ? { backgroundImage: `url(${coverImg})` } : {} }>
          <div className={styles.lang}>{/* <SelectLang /> */}</div>
          <div className={styles.content}>
            <div className={styles.top}>
              <div className={styles.header}>
                <Link to="/">
                  <img alt="logo" className={styles.logo} src={iconImg ? iconImg : logo} />
                </Link>
              </div>
            </div>
            {children}
            <div className={styles.bottom}>
              <div className={styles.footer}>
                <Link to="/">
                  {/* <img alt="logo" className={styles.logoFooter} src={logoBottom} /> */}
                </Link>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default connect(({ settings }: ConnectState) => ({ ...settings }))(UserLayout);
