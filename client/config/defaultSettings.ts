import { Settings as ProSettings } from '@ant-design/pro-layout';

type DefaultSettings = Partial<ProSettings> & {
  pwa: boolean;
};

const proSettings: DefaultSettings = {
  navTheme: 'light',
  primaryColor: '#2A85FF', //'#1890ff' - The default color //DEFAULT GREEN COLOR 88a838
  layout: 'side',
  contentWidth: 'Fluid',
  fixedHeader: true,
  fixSiderbar: true,
  colorWeak: false,
  pwa: false,
  title: 'iConnHRM 2.0'
};

export type { DefaultSettings };

export default proSettings;
