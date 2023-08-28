import { Button, Result } from 'antd';
import React from 'react';
import { history } from 'umi';

const PermissionDeniedPage: React.FC = () => (
  <Result
    status="403"
    title="403"
    subTitle="Permission denied."
    extra={
      <Button type="primary" onClick={() => history.push('/')}>
        Back Home
      </Button>
    }
  />
);

export default PermissionDeniedPage;
