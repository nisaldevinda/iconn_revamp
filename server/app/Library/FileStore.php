<?php

namespace App\Library;

use App\Exceptions\FileStoreException;
use App\Exceptions\FileStoreNotFoundException;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;
use Log;

class FileStore
{
    private $connection;

    private $bucket;

    public function __construct(S3Client $s3Client, Session $session)
    {
        try {
            $this->connection = $s3Client;
            $this->bucket = empty($session->getTenantId()) ? config('tenancy.tenancy_name') : $session->getTenantId();
        } catch (\Exception $ex) {
            Log::error("FileStore.__construct: " . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * create MinioBucket
     */
    public function createBucket($bucketName)
    {
        try {
            $insert = $this->connection->createBucket([ 'Bucket' => $bucketName ]);
            return $insert;
        } catch (\Exception $ex) {
            Log::error("FileStore.createBucket: " . $ex->getMessage());            
            throw $ex;
        }
    }

    /**
     * Minio Bucket exist
     */
    public function doesBucketExist($bucketName)
    {
        try {
            $response = $this->connection->doesBucketExist($bucketName);
            return $response;
        } catch (\Exception $ex) {
            Log::error("FileStore.doesBucketExist: " . $ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Minio Object exist
     */
    public function doesObjectExist($objectKey)
    {
        try {
            $response = $this->connection->doesObjectExist($this->getCompanyBucket(), $objectKey);
            return $response;
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            throw $ex;
        }
    } 
    
    /**
     * create connection with minio
     * 
     */
    public function getCompanyBucket()
    {
        try {
            $bucketName = config('fileStorage.bucketPrefix') . "-" . $this->bucket;
            $bucketExist = $this->doesBucketExist($bucketName);
            if (!$bucketExist) {
                $this->createBucket($bucketName);
            }

            return $bucketName;
        } catch (\Exception $ex) {
            Log::error("FileStore.getCompanyBucket: " . $ex->getMessage());
            throw $ex;
        }

    }

    /**
     * put base64 encoded object
     */
    public function putBase64EncodedObject($fileName, $fileSize, $encodedObject)
    {
        try {
            $key = Util::generateUUID();

            list($type, $data) = explode(',', $encodedObject);
            $decodedObject = base64_decode($data, true);

            if (!$decodedObject) {
                throw new FileStoreException('Invalid base64 string');
            }

            $result = $this->connection->putObject([
                'Bucket' => $this->getCompanyBucket(),
                'Key' => $key,
                'Body' => $decodedObject,
                'ACL' =>'public-read',
                'ContentType' => $type . ","
            ]);

            $fileId = DB::table('fileStoreObject')->insertGetId([
                'name' => $fileName,
                'size' => $fileSize,
                'type' => $type,
                'key' => $key
            ]);

            $file = DB::table('fileStoreObject')->where('id', $fileId)->first();
            unset($file->key);
            return $file;
        } catch (\Exception $ex) {
            Log::error("FileStore.upload: " . $ex->getMessage());            
            throw $ex;
        }
    }

    /**
     * put base64 encoded object
     */
    public function getBase64EncodedObject($id)
    {
        try {
            $file = DB::table('fileStoreObject')->where('id', $id)->first();

            if (empty($file)) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $doesObjectExist = $this->doesObjectExist($file->key);

            if (!$doesObjectExist) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $result = $this->connection->getObject([
                'Bucket' => $this->getCompanyBucket(),
                'Key'    => $file->key
            ]);

            $decodedObject = '';
            $result['Body']->rewind();
            while ($data = $result['Body']->read(1024)) {
                $decodedObject .= $data;
            }

            unset($file->key);
            $file->data = $result['ContentType'] . base64_encode($decodedObject);
            return $file;
        } catch (\Exception $ex) {
            Log::error("FileStore.upload: " . $ex->getMessage());            
            throw $ex;
        }
    }

    /**
     * delete a file from file store
     */
    public function deleteObject($id)
    {
        try {
            $file = DB::table('fileStoreObject')->where('id', $id)->first();

            if (empty($file)) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $doesObjectExist = $this->doesObjectExist($file->key);
            if (!$doesObjectExist) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $result = $this->connection->deleteObject([
                'Bucket' => $this->getCompanyBucket(),
                'Key' => $file->key
            ]);

            $file = DB::table('fileStoreObject')->where('id', $id)->delete();
            return $file;
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * get the temporary url of the file
     * 
     * Sample Input: 
     *    $bucketName = 'egn_lms_files'
     *    $objectKey = '84865adsdas545_en.mp3'
     */
    public function getTempUrl($id)
    {
        try {

            $file = DB::table('fileStoreObject')->where('id', $id)->first();

            if (empty($file)) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $doesObjectExist = $this->doesObjectExist($file->key);
            if (!$doesObjectExist) {
                throw new FileStoreNotFoundException('Object not found');
            }

            $expireTime = '+'.config('fileStorage.urlExpireTime').' minutes';

            $command = $this->connection->getCommand('GetObject', [
                        'Bucket' => $this->getCompanyBucket(),
                        'Key'    => $file->key
                    ]);
            $presignedRequest = $this->connection->createPresignedRequest($command, $expireTime);
            $presignedUrl =  (string)  $presignedRequest->getUri();

            return $presignedUrl;
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * function to convert the encoded data to image
     */
    public function getImageForEncodedData($encodedData)
    {
        try {
            $fileResponse = [];
            if(!empty($encodedData)) {
               $fileData  = explode(",", $encodedData->data,2)[1];
               $img = str_replace(' ', '+',  $fileData );
               $data = base64_decode($img);
               $fileExtension = explode(';', $encodedData->type,2)[0];
               $contentType = explode(':',$fileExtension)[1];
               
               $fileResponse['data'] = $data;
               $fileResponse['contentType'] = $contentType;
            }
            return $fileResponse;
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            throw $ex;
        }
    }
}
