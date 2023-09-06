import React, { useEffect, useState } from 'react';
import { Button, Col, List, message, Modal, Row, Skeleton } from 'antd';
import { getEmployeeTransferSupportData, downloadEmployeeTransferTemplate, uploadEmployeeTransferSheet, completeEmployeeTransferProcess, getEmployeeTransferUploadHistory, rollbackEmployeeTransferUpload } from '@/services/bulkUpload';
import { useIntl, FormattedMessage } from 'react-intl';
import { APIResponse } from '@/utils/request';
import { downloadBase64File } from '@/utils/utils';
import moment from 'moment';
import _ from 'lodash';
import Validator from './validator';
import { PageContainer } from '@ant-design/pro-layout';

const EmployeeTransferBulkUpload: React.FC = () => {
  const intl = useIntl();

  const [loading, setLoading] = useState(false);
  const [supportData, setSupportData] = useState({});
  const [isHistoryModalVisible, setIsHistoryModalVisible] = useState(false);
  const [historyModalLoading, setHistoryModalLoading] = useState(false);
  const [historyData, setHistoryData] = useState([]);

  useEffect(() => {
    setLoading(true);
    getEmployeeTransferSupportData()
      .then((response: any) => {
        if (response.error) {
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToFetchSupportData',
                defaultMessage: 'Failed to fetch support data',
              })
          });

          setLoading(false);
          return;
        }

        setSupportData(response.data);
        setLoading(false);
      })
      .catch((error: any) => {
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'failedToFetchSupportData',
              defaultMessage: 'Failed to fetch support data',
            })
        });

        setLoading(false);
      })
  }, [])

  const downloadTemplate = async () => {
    const key = 'download-template';
    downloadEmployeeTransferTemplate().then((response: APIResponse) => {
      if (response.error) {
        message.error({
          content:
            response.message ??
            intl.formatMessage({
              id: 'failedToDownload',
              defaultMessage: 'Failed to download',
            }),
          key,
        });
        return;
      }

      if (!_.isUndefined(response.data) || !_.isEmpty(response.data)) {
        downloadBase64File(
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          response.data,
          'Employee Transfer - '
            .concat(moment().format('YYYY-MM-DD'))
            .concat('.xlsx'),
        );
      }

      message.success({
        content:
          response.message ??
          intl.formatMessage({
            id: 'successfullyDownloaded',
            defaultMessage: 'Successfully downloaded',
          }),
        key,
      });
    })
      .catch((error: APIResponse) => {
        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'failedToDownload',
              defaultMessage: 'Failed to download',
            }),
          key,
        });
      });
  }

  return (
    <div
      style={{
        backgroundColor: 'white',
        borderTopLeftRadius: '30px',
        paddingLeft: '50px',
        paddingTop: '50px',
        paddingBottom: '50px',
        width: '100%',
        paddingRight: '0px',
      }}
    >
      <PageContainer loading={loading}>
        <Row>
          <Col span={24}>
            {!loading && (
              <Validator
                cardTitleRender={[
                  <Row
                    style={{
                      marginTop: 8,
                      float: 'right',
                      display: 'flex',
                      marginRight: '2vh',
                    }}
                  >
                    <Col span={15}>
                      <Button
                        type="primary"
                        key="bulk-upload-download-template"
                        onClick={downloadTemplate}
                      >
                        <FormattedMessage id="bulk" defaultMessage="Download Template" />
                      </Button>
                    </Col>
                    <Col span={9}>
                      <Button
                        type="primary"
                        key="bulk-upload-history"
                        onClick={async () => {
                          setIsHistoryModalVisible(true);
                          setHistoryModalLoading(true);
                          const response = await getEmployeeTransferUploadHistory();
                          const data = !response.error ? response.data ?? [] : [];
                          setHistoryData(data);
                          setHistoryModalLoading(false);
                        }}
                      >
                        <FormattedMessage
                          id="bulk-upload-view-history"
                          defaultMessage="View History"
                        />
                      </Button>
                    </Col>
                  </Row>,
                ]}
                onFileUpload={uploadEmployeeTransferSheet}
                onFinish={completeEmployeeTransferProcess}
                intl={intl}
                supportData={supportData}
              />
            )}
          </Col>
        </Row>
        <Modal
          title={intl.formatMessage({
            id: 'salary_increment_upload_history',
            defaultMessage: 'Employee Transfer Upload History',
          })}
          centered
          visible={isHistoryModalVisible}
          onOk={() => setIsHistoryModalVisible(false)}
          onCancel={() => setIsHistoryModalVisible(false)}
          footer={null}
          width="80vw"
        >
          {historyModalLoading ? (
            <Skeleton active />
          ) : (
            <List
              itemLayout="horizontal"
              dataSource={historyData}
              renderItem={(item, index) => (
                <List.Item
                  actions={[
                    <a
                      key="history-rollback"
                      onClick={async () => {
                        const key = 'rollbacking';
                        message.loading({
                          content: intl.formatMessage({
                            id: 'rollbacking',
                            defaultMessage: 'Rollbacking...',
                          }),
                          key,
                        });

                        await rollbackEmployeeTransferUpload(item.id)
                          .then((response: APIResponse) => {
                            if (response.error) {
                              message.error({
                                content:
                                  response.message ??
                                  intl.formatMessage({
                                    id: 'failedToRollback',
                                    defaultMessage: 'Failed to rollback',
                                  }),
                                key,
                              });
                              return;
                            }

                            setIsHistoryModalVisible(false);

                            message.success({
                              content:
                                response.message ??
                                intl.formatMessage({
                                  id: 'successfullyRollbacked',
                                  defaultMessage: 'Successfully rollbacked',
                                }),
                              key,
                            });
                          })
                          .catch((error: APIResponse) => {
                            message.error({
                              content:
                                error.message ??
                                intl.formatMessage({
                                  id: 'failedToRollback',
                                  defaultMessage: 'Failed to rollback',
                                }),
                              key,
                            });
                          });
                      }}
                    >
                      <FormattedMessage
                        id="salary-increment-upload-history-rollback"
                        defaultMessage="Rollback"
                      />
                    </a>,
                  ]}
                >
                  <List.Item.Meta
                    title={item.createdAt}
                    description={`${
                      JSON.parse(item.affectedIds).length
                    } number of records has been created`}
                  />
                </List.Item>
              )}
            />
          )}
        </Modal>
      </PageContainer>
    </div>
  );
};

export default EmployeeTransferBulkUpload;
