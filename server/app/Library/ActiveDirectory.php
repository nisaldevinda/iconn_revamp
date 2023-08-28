<?php

namespace App\Library;

use App\Exceptions\ActiveDirectoryException;
use Exception;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Log;

class ActiveDirectory
{
    private $httpClient;
    private $azureClient;

    private const SCOPE = 'https://graph.microsoft.com/.default';
    private const GRANT_TYPE = 'client_credentials'; 

    function __construct(Client $client, Graph $graph)
    {
        $this->httpClient = $client;
        $this->azureClient = $graph;
    }

    /**
     * initialize  azure client
     *
     * @return null
     */
    public function initClient($tenantId, $clientId, $clientSecret)
    {
        try {
            $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
            $authResponse = json_decode($this->httpClient->post($url, [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => self::SCOPE,
                    'grant_type' => self::GRANT_TYPE,
                ],
            ])->getBody()->getContents());

            $accessToken = $authResponse->access_token;
            $this->azureClient->setAccessToken($accessToken);
        } catch (RequestException $ex) {
            Log::error("ActiveDirectory.initClient.RequestException: " . $ex->getMessage());
            throw new ActiveDirectoryException('Authentication operation failed.');
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.initClient: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to initializing azure client.');
        }
    }

    /**
     * Get azure client
     *
     * @return object
     */
    public function getClient()
    {
        return $this->azureClient;
    }

    /**
     * Get active directory users
     *
     * @return array
     */
    public function getUsers($select = null)
    {
        try {
            $select = empty($select) ? '*' : implode(',', $select);
            $users = $this->azureClient
                // ->setApiVersion("beta")
                ->createRequest('GET', '/users?$select=' . $select)
                ->setReturnType(Model\User::class)
                ->execute();

            return $users;
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.getUsers: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to fetch azure users.');
        }
    }

    /**
     * Get active directory user collection
     *
     * @return array
     */
    public function getUserCollection($limit = 10, $select = null)
    {
        try {
            $select = empty($select) ? '*' : implode(',', $select);
            $usersBatch = $this->azureClient
                ->createCollectionRequest('GET', '/users?$expand=manager&$select=' . $select)
                ->setReturnType(Model\User::class)
                ->setPageSize($limit);

            return $usersBatch;
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.getUserCollection: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to fetch azure user collection.');
        }
    }

    /**
     * get active directory user
     *
     * @return array
     */
    public function getUser($id)
    {
        try {
            $user = $this->azureClient
                ->createRequest('GET', '/users/' . $id)
                ->setReturnType(Model\User::class)
                ->execute();

            return $user;
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.getUser: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to fetch azure user.');
        }
    }

    /**
     * create active directory user
     *
     * @return array
     */
    public function createUser($azureUserObj)
    {
        try {
            $user = $this->azureClient
                ->createRequest('POST', '/users')
                ->attachBody(json_encode($azureUserObj))
                ->setReturnType(Model\User::class)
                ->execute();

            return $user;
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.createUser: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to create azure user.');
        }
    }

    /**
     * update active directory user
     *
     * @return array
     */
    public function updateUser($azureUserId, $azureUserObj)
    {
        try {
            $user = $this->azureClient
                ->createCollectionRequest('PATCH', '/users/' . $azureUserId)
                ->attachBody(json_encode($azureUserObj))
                ->execute();

            return $user;
        } catch (Exception $ex) {
            Log::error("ActiveDirectory.updateUser: " . $ex->getMessage());
            throw new ActiveDirectoryException('Failed to update azure user.');
        }
    }
}
