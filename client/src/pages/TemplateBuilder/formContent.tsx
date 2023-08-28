import React from 'react';
import { Col, Row, Card, Divider } from 'antd';
import { useIntl } from 'umi';
import ReactHtmlParser from 'react-html-parser';
import TemplateFormInput from '@/components/TemplateFormInput';

interface FormContentProps {
  content: any;
  formReference: any;
  currentRecord: any;
  setCurrentRecord: any;
}

const FormContent: React.FC<FormContentProps> = ({
  content,
  formReference,
  currentRecord,
  setCurrentRecord,
}) => {
  const intl = useIntl();

  return (
    <>
      {content.map((section) => (
        <Row style={{ width: '100%', marginRight: 40 }}>
          <Col span={24}>
            <Card
              key={section.key}
              title={
                <Row style={{ fontSize: 22, fontWeight: 'bold', color: '#ffffff' }}>
                  {intl.formatMessage({
                    id: section.labelKey,
                    defaultMessage: section.defaultLabel,
                  })}
                </Row>
              }
              style={{ marginBottom: 40 }}
              headStyle={{
                background: '#86C129',
                borderTopLeftRadius: 6,
                borderTopRightRadius: 6,
              }}
            >
              {section.questions.length > 0 ? (
                <div>
                  {section.questions.map((question) => (
                    <>
                      <Row>
                        <Row
                          style={{
                            width: '100%',
                            marginTop: 15,
                            marginLeft: 15,
                          }}
                        >
                          <Col span={24}>
                            <div style={{ fontSize: 16, display: 'flex' }}>
                              <span style={{ marginRight: 15 }}>{question.questionKey + ':'}</span>
                              {ReactHtmlParser(question.questionString)}
                              {question.answerDetails.isRequired ? (
                                <span style={{ color: 'red', marginLeft: 5 }}>{'*'}</span>
                              ) : (
                                <></>
                              )}
                            </div>
                          </Col>
                        </Row>
                      </Row>

                      <Row>
                        <Row
                          style={{
                            width: '100%',
                            marginLeft: 50,
                          }}
                        >
                          <Col span={24}>
                            <Row>
                              <TemplateFormInput
                                key={question.questionKey}
                                fieldName={question.name}
                                answerDetails={question.answerDetails}
                                answerType={question.answerType}
                                form={formReference}
                                values={currentRecord}
                                setValues={setCurrentRecord}
                              />
                            </Row>
                          </Col>
                        </Row>
                      </Row>
                      <Divider></Divider>
                    </>
                  ))}
                </div>
              ) : (
                <></>
              )}
            </Card>
          </Col>
        </Row>
      ))}
    </>
  );
};

export default FormContent;
