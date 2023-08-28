import React, { useRef, useState, useEffect } from 'react';
import { useParams } from 'umi';
import { Card, Col, Typography, Button, Space } from 'antd';
import { PageContainer } from '@ant-design/pro-layout';
import { Editor } from '@tinymce/tinymce-react';
import { getEmployeeDocument, downloadPdf, downloadDocx } from '@/services/documentTemplate';
import { apiKey, defaultEditorObj } from './editorHelper';
import { downloadBase64File } from '@/utils/utils';

const EmployeeDocument: React.ReactNode = () => {
  const { Title, Text } = Typography;
  const editorRef = useRef(null);
  const [editorInit, setEditorInit] = useState<EditorProps>(defaultEditorObj);
  const [content, setContent] = useState<string>('');
  const [templateName, setTemplateName] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const { templateId, employeeId } = useParams<any>();

  const setPageProperties = (pageSettings: any) => {
    const { marginLeft, marginRight, marginTop, marginBottom } = pageSettings;
    const contentStyle = `body { font-family:Arial; font-size:10pt; margin-left: ${marginLeft}mm; margin-right: ${marginRight}mm; margin-top: ${marginTop}mm; margin-bottom: ${marginBottom}mm; }`;
    setEditorInit({ ...editorInit, content_style: contentStyle });
  };

  const downloadAsPdf = async () => {
    try {
      setLoading(true);
      const content = editorRef.current?.getContent();
      const result = await downloadPdf(employeeId, templateId, content);
      const { data } = result;
      downloadBase64File('application/pdf', data, 'document.pdf');
      setContent(content);
      setLoading(false);
    } catch (error) {
      console.log('error', error);
      setLoading(false);
    }
  };

  const downloadAsDocx = async () => {
    try {
      setLoading(true);
      const content = editorRef.current?.getContent();
      const result = await downloadDocx(employeeId, templateId, content);
      const { data } = result;
      downloadBase64File(
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        data,
        'document.docx',
      );
      setContent(content);
      setLoading(false);
    } catch (error) {
      console.log('error', error);
      setLoading(false);
    }
  };

  useEffect(() => {
    const featchData = async (templateId: string, employeeId: string) => {
      try {
        setLoading(true);
        const { data } = await getEmployeeDocument(employeeId, templateId);
        const { name, content, pageSettings } = data;
        setTemplateName(name);
        setContent(content);
        setPageProperties(pageSettings);
        setLoading(false);
      } catch (error) {
        console.log('error:', error);
        setLoading(false);
      }
    };

    featchData(templateId, employeeId);
  }, [templateId, employeeId]);

  return (
    <PageContainer loading={loading}>
      <Card>
        <Col offset={1} span={20}>
          <Title level={5} style={{ marginBottom: '3%' }}>
            {templateName}
          </Title>
          <Editor
            apiKey={TINY_API_KEY}
            onInit={(evt, editor) => (editorRef.current = editor)}
            initialValue={content}
            init={editorInit}
          />
        </Col>
        <Col offset={1} span={20} style={{ marginTop: '2%' }}>
          <Space>
            <Text>Export To : </Text>
            <Button htmlType="button" onClick={downloadAsPdf}>
              Download PDF
            </Button>
            <Button htmlType="button" onClick={downloadAsDocx}>
              Download Doc
            </Button>
          </Space>
        </Col>
      </Card>
    </PageContainer>
  );
};

export default EmployeeDocument;
