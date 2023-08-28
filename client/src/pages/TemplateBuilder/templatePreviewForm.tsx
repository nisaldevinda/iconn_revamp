import React, { useState } from 'react';
import { Col, Row, Card, Divider, Form } from 'antd';
import { useIntl } from 'umi';
import FormContent from './formContent';

interface TemplatePreviewFormProps {
  form: any;
  content: any;
  currentRecord: any;
  setCurrentRecord: any;
}

const TemplatePreviewForm: React.FC<TemplatePreviewFormProps> = ({
  form,
  content,
  currentRecord,
  setCurrentRecord,
}) => {
  return (
    <>
      <FormContent
        content={content}
        formReference={form}
        currentRecord={currentRecord}
        setCurrentRecord={setCurrentRecord}
      />
    </>
  );
};

export default TemplatePreviewForm;
