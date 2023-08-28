import _ from "lodash";
import { Upload, message } from "antd";
import React, { useEffect, useState } from "react";
import ImgCrop from 'antd-img-crop';
import { CameraOutlined } from "@ant-design/icons";
import request from "@/utils/request";
import { getBase64 } from "@/utils/fileStore";
import "./index.css"

export type AvatarProps = {
    id: number;
    onUploadFinish: (values: any) => void;
    scope: string
};

const Avatar: React.FC<AvatarProps> = ({ onUploadFinish, ...props }) => {
    const [fileList, setFileList] = useState([]);
    const [loading, setLoading] = useState(false);
    const [imageUrl, setImageUrl] = useState<String>();

    const actionRoute = `/api/employees/${props.id}/profilePicture?scope=${props.scope}`

    useEffect(() => {
        onUploadFinish(false)

        request(actionRoute, { method: 'GET' }, true)
            .then(response => {
                if (response.data) {
                    setImageUrl(response.data.data);

                    setFileList([{
                        url: response.data.data,
                    }]);
                }
            });
    }, [props.id]);
    const uploadButton = (
        <div className="avatar-upload-btn" >
            <CameraOutlined className="camera-icon-upload" style={{ color: "white", textAlign: "center" }} />
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
        onUploadFinish(false)
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
                            onUploadFinish(true);
                        }
                    } else {
                        setFileList(info.fileList);
                        onUploadFinish(true);
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
        <ImgCrop rotate>
            <Upload
                onChange={onChange}
                beforeUpload={beforeUpload}
                customRequest={({ onSuccess }) => onSuccess('ok')}
                showUploadList={false}
            >
                {uploadButton}
            </Upload>
        </ImgCrop>
    );
};

export default Avatar;
