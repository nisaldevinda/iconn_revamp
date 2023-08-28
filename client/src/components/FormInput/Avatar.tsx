import _ from "lodash";
import { Col, Upload, message, Image, Space } from "antd";
import React, { useEffect, useState } from "react";
import ImgCrop from 'antd-img-crop';
import { LoadingOutlined, PlusOutlined } from "@ant-design/icons";
import request from "@/utils/request";
import { FormattedMessage, useIntl } from "umi";
import { getBase64 } from "@/utils/fileStore";

export type AvatarProps = {
  modelName: string,
  fieldName: string,
  fieldNamePrefix?: string;
  fieldDefinition: {
    labelKey: string,
    defaultLabel: string,
    type: string,
    isEditable: string,
    isSystemValue: string,
    actionRoute: string,
    validations: {
      isRequired: boolean,
      min: number,
      max: number
    },
    placeholderKey: string,
    defaultPlaceholder: string,
    defaultValue: string,
  },
  values: {},
  setValues: (values: any) => void,
  recentlyChangedValue: any
};

const Avatar: React.FC<AvatarProps> = (props) => {
  const intl = useIntl();

  const [fileList, setFileList] = useState([]);
  const [loading, setLoading] = useState(false);
  const [previewVisible, setPreviewVisible] = useState(false);
  const [imageUrl, setImageUrl] = useState<String>();
  const [actionRoute, setActionRoute] = useState<string>();

  useEffect(() => {
    const _actionRoute = props.fieldDefinition.actionRoute
      .split('/')
      .map(routeSegment => {
        if (/^[{*}]/.test(routeSegment)) {
          let key = routeSegment.substring(1, routeSegment.length - 1);
          routeSegment = props.values[key];
        }

        return routeSegment;
      })
      .join('/');
    setActionRoute(_actionRoute);

    request(_actionRoute, { method: 'GET' }, true)
      .then(response => {
        if (response.data) {
          setImageUrl(response.data.data);

          setFileList([{
            url: response.data.data,
          }]);
        }
      });
  }, [props.fieldDefinition.actionRoute]);

  const uploadButton = (
    <div>
      {loading ? <LoadingOutlined /> : <PlusOutlined />}
      <div style={{ marginTop: 8 }}>Upload</div>
    </div>
  );

  const beforeUpload = (file) => {
    const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png';
    if (!isJpgOrPng) {
      message.error('You can only upload JPG/PNG file!');
    }
    const isLt2M = file.size / 1024 / 1024 < 2;
    if (!isLt2M) {
      message.error('Image must smaller than 2MB!');
    }
    return isJpgOrPng && isLt2M;
  }

  const onChange = async (info: any) => {
    let status = info?.file?.status;
    if (actionRoute && (status == 'done' || status == 'error')) {
      try {
        const imageUrl = await getBase64(info.file.originFileObj);
        setImageUrl(imageUrl);
        setLoading(false);
        request(
          actionRoute,
          {
            method: 'POST',
            data: {
              fileName: info.file.name,
              fileSize: info.file.size,
              data: imageUrl,
            },
          },
          true,
        ).then(() => {
          if (status === 'error') {
            const { fileList, file } = info;
            const { uid } = file;
            const index = fileList.findIndex((file: any) => file.uid == uid);
            const newFile = { ...file };
            if (index > -1) {
              newFile.status = 'done';
              newFile.percent = 100;
              delete newFile.error;
              fileList[index] = newFile;
              setFileList(fileList);
            }
          } else {
            setFileList(info.fileList);
          }
        });
      } catch (error) {
        console.log(error);
        setFileList(info.fileList);
      }
    } else if (actionRoute && status == 'removed') {
      request(actionRoute, { method: 'DELETE' }, true);
      setFileList(info.fileList);
    } else {
      setFileList(info.fileList);
    }
  };

  return (
    <Col data-key={props.fieldName} span={24} style={{ paddingBottom: 10 }}>
      <div className="ant-col ant-form-item-label">
        <label>
          <FormattedMessage
            id={`model.${props.modelName}.${props.fieldDefinition.labelKey}`}
            defaultMessage={props.fieldDefinition.defaultLabel}
          />
        </label>
      </div>

      <ImgCrop rotate>
        <Upload
          listType="picture-card"
          fileList={fileList}
          className="avatar-uploader"
          onChange={onChange}
          beforeUpload={beforeUpload}
          onPreview={() => setPreviewVisible(true)}
          customRequest={({ onSuccess }) => onSuccess('ok')}
          disabled={props.readOnly}
        >
          {fileList.length < 1 && uploadButton}
        </Upload>
      </ImgCrop>

      <Image
        src={imageUrl}
        preview={{
          visible: previewVisible,
          onVisibleChange: (visibleState) => setPreviewVisible(visibleState),
        }}
        style={{
          display: 'none',
        }}
      />
    </Col>
  );
};

export default Avatar;
