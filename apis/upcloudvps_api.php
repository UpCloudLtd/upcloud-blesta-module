<?php

use Blesta\Core\Util\Common\Traits\Container;

class UpcloudVpsApi
{
    use Container;

    /**
     * @var resource The cURL handle
     */
    private $curl;
    /**
     * @var string The base URL for API requests
     */
    private $baseurl;
    /**
     * @var array HTTP headers for requests
     */
    private $httpHeader = [];
    /**
     * @var string The Blesta version
     */
    private $blestaVer;
    /**
     * @var string The module version
     */
    private $moduleVer;
    /**
     * @var \Psr\Log\LoggerInterface The logger instance
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param array $params API connection parameters including:
     *  - baseUrl (string) The API base URL
     *  - apiToken (string) The API token
     *  - moduleVer (string) The current module version
     *  - blestaVer (string) The current Blesta version
     */
    public function __construct(array $params)
    {
        $this->baseurl = $params['baseUrl'];
        $this->blestaVer = $params['blestaVer'];
        $this->setHttpHeader('Authorization', 'Bearer ' . $params['apiToken']);
        $this->moduleVer = $params['moduleVer'] ?? 'dev';
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Sets an HTTP header for the request.
     *
     * @param string $name The header name
     * @param string $value The header value
     */
    protected function setHttpHeader($name, $value)
    {
        $this->httpHeader[$name] = $value;
    }

    /**
     * Executes an API request.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE)
     * @param string $url The API endpoint URL (relative to base URL)
     * @param array|null $data Optional data to send with the request
     * @return array An array containing 'response_code' and 'response' (decoded JSON)
     */
    protected function executeRequest($method, $url, $data = null)
    {
        $call = curl_init();
        curl_setopt($call, CURLOPT_URL, $this->baseurl . $url);
        curl_setopt($call, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($call, CURLOPT_POSTFIELDS, json_encode($data));
            $this->setHttpHeader('Content-Type', 'application/json');
        }

        curl_setopt_array($call, [
            CURLOPT_USERAGENT => 'upcloud-blesta-module/' . $this->moduleVer,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        // Set HTTP headers
        curl_setopt($call, CURLOPT_HTTPHEADER, array_map(function ($key, $value) {
            return "$key: $value";
        }, array_keys($this->httpHeader), $this->httpHeader));
        $response = curl_exec($call);
        $statusCode = curl_getinfo($call, CURLINFO_HTTP_CODE);
        if ($response === false) {
            $this->logger->error('Curl error: ' . curl_error($call));
        }

        curl_close($call);
        return ['response_code' => $statusCode, 'response' => json_decode($response, true)];
    }

    /**
     * Sends a GET request.
     *
     * @param string $url The API endpoint URL
     * @return array The API response
     */
    public function get($url)
    {
        return $this->executeRequest('GET', $url);
    }

    /**
     * Sends a POST request.
     *
     * @param string $url The API endpoint URL
     * @param array|null $data Optional data to send
     * @return array The API response
     */
    public function post($url, $data = null)
    {
        return $this->executeRequest('POST', $url, $data);
    }

    /**
     * Sends a PUT request.
     *
     * @param string $url The API endpoint URL
     * @param array|null $data Optional data to send
     * @return array The API response
     */
    public function put($url, $data = null)
    {
        return $this->executeRequest('PUT', $url, $data);
    }

    /**
     * Sends a DELETE request.
     *
     * @param string $url The API endpoint URL
     * @param array|null $data Optional data to send
     * @return array The API response
     */
    public function delete($url, $data = null)
    {
        return $this->executeRequest('DELETE', $url, $data);
    }

    /**
     * Gets account information.
     *
     * @return array The API response
     */
    public function getAccountInfo()
    {
        return $this->get('account');
    }

    /**
     * Gets pricing information.
     *
     * @return array The API response
     */
    public function getPrices()
    {
        return $this->get('price');
    }

    /**
     * Gets available zones.
     *
     * @return array The API response
     */
    public function getZones()
    {
        return $this->get('zone');
    }

    /**
     * Gets available timezones.
     *
     * @return array The API response
     */
    public function getTimezones()
    {
        return $this->get('timezone');
    }

    /**
     * Gets available server plans.
     *
     * @return array The API response
     */
    public function getPlans()
    {
        return $this->get('plan');
    }

    /**
     * Gets server size configurations.
     *
     * @return array The API response
     */
    public function getServerConfigurations()
    {
        return $this->get('server_size');
    }

    /**
     * Gets a list of all servers associated with the account.
     *
     * @return array The API response
     */
    public function getAllServers()
    {
        return $this->get('server');
    }

    /**
     * Gets details for a specific server.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function getServer($ServerUUID)
    {
        return $this->get('server/' . rawurlencode($ServerUUID));
    }

    /**
     * Gets available storage templates (OS images).
     *
     * @return array The API response
     */
    public function getTemplate()
    {
        return $this->get('storage/template');
    }

    /**
     * Creates a new server.
     *
     * @param array $params Server creation parameters including:
     *  - zone (string) The zone ID
     *  - title (string) The server title (hostname)
     *  - plan (string) The plan name
     *  - template (string) The template UUID
     * @return array The API response
     */
    public function createServer($params)
    {
        $Templates = $this->getTemplate()['response']['storages']['storage'];
        foreach ($Templates as $Template) {
            if ($Template['uuid'] == $params['template']) {
                $TemplateTitle = $Template['title'];
                $TemplateUUID = $Template['uuid'];
                break;
            }
        }
        $AllPlans = $this->getPlans()['response']['plans']['plan'];
        foreach ($AllPlans as $Plans) {
            if ($Plans['name'] == $params['plan']) {
                $PlanName = $Plans['name'];
                $PlanSize = $Plans['storage_size'];
                $PlanTier = $Plans['storage_tier'];
                break;
            }
        }

        $postData = [
            'server' => [
                'metadata' => 'yes',
                'zone' => $params['zone'], // GetZones()
                'title' => $params['title'], // hostname
                'hostname' => $params['title'], // hostname
                'plan' => $PlanName, // getPlans()
                //  "simple_backup" => "0430,weeklies",
                'remote_access_enabled' => 'yes',
                'storage_devices' => [
                    'storage_device' => [
                        [
                            'action' => 'clone',
                            'storage' => $TemplateUUID, // GetTemplates()
                            'size' => $PlanSize, // storage_size from getPlans()
                            'tier' => $PlanTier, // storage_tier from getPlans()
                            'title' => $TemplateTitle, // OS Name
                        ]
                    ]
                ]
            ]
        ];
        return $this->post('server', $postData);
    }

    /**
     * Performs an operation (start, stop, restart) on a server.
     * Internal helper method.
     *
     * @param string $action The action to perform ('start', 'stop', 'restart')
     * @param string $ServerUUID The UUID of the server
     * @param string|null $stop_type The type of stop ('soft' or 'hard'), only used for 'stop' and 'restart'
     * @return array The API response
     */
    public function serverOperation($action, $ServerUUID, $stop_type = null)
    {
        $data = [];
        if ($stop_type !== null) {
            $data[$action . '_server']['stop_type'] = $stop_type;
            $data[$action . '_server']['timeout'] = "60";
        }
        return $this->post(sprintf('server/%s/%s', rawurlencode($ServerUUID), rawurlencode($action)), $data);
    }

    /**
     * Starts a server.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function startServer($ServerUUID)
    {
        return $this->serverOperation('start', $ServerUUID);
    }

    /**
     * Stops a server (hard stop).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function stopServer($ServerUUID)
    {
        return $this->serverOperation('stop', $ServerUUID, 'hard');
    }

    /**
     * Restarts a server (hard restart).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function restartServer($ServerUUID)
    {
        return $this->serverOperation('restart', $ServerUUID, 'hard');
    }

    /**
     * Cancels a server operation (e.g., ongoing creation).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function cancelServer($ServerUUID)
    {
        return $this->serverOperation('cancel', $ServerUUID);
    }

    /**
     * Deletes a server. Does not delete attached storage by default.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function deleteServer($ServerUUID)
    {
        return $this->delete('server/' . rawurlencode($ServerUUID));
    }

    /**
     * Modifies a server's plan (upgrades/downgrades).
     * Stops the server, changes the plan, resizes the primary storage device if needed, and restarts.
     *
     * @param string $uuid The UUID of the server
     * @param string $Plan The name of the target plan
     * @return array The API response from the final operation (storage resize or plan change)
     */
    public function modifyServer($uuid, $Plan)
    {
        $this->stopServerAndWait($uuid);
        $allPlans = $this->getPlans()['response']['plans']['plan'];
        foreach ($allPlans as $plan) {
            if ($plan['name'] == $Plan) {
                $planSize = $plan['storage_size'];
                break;
            }
        }

        $upgradePlan = $this->put('server/' . rawurlencode($uuid), ['server' => ['plan' => $Plan]]);
        if ($upgradePlan['response']['error']['error_message']) {
            return $upgradePlan;
        } else {
            $storages = $this->getServer($uuid)['response']['server']['storage_devices']['storage_device'];
            foreach ($storages as $storage) {
                if ($storage['part_of_plan'] == "yes") {
                    $storageId = $storage['storage'];
                    $existingStorageSize = $storage['storage_size'];
                    break;
                }
            }
            if ($storageId && $planSize > $existingStorageSize) {
                $modeyStorage = $this->modifyStorage($storageId, $planSize);
                $this->startServer($uuid);
                return $modeyStorage;
            }
        }
    }


    /**
     * Modifies the size of a specific storage device.
     *
     * @param string $storageId The UUID of the storage device
     * @param int $planSize The target size in GB
     * @return array The API response
     */
    public function modifyStorage($storageId, $planSize)
    {
        $body = [
            'storage' => [
                'size' => $planSize
            ]
        ];
        return $this->put('storage/' . rawurlencode($storageId), $body);
    }

    /**
     * Deletes a server and its associated storage devices.
     * Stops the server first.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response from the delete operation
     */
    public function deleteServerAndStorage($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete(sprintf('server/%s?storages=1', rawurlencode($ServerUUID)));
    }

    /**
     * Deletes a server and its associated storage devices and backups.
     * Stops the server first.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response from the delete operation
     */
    public function deleteServerAndStorageAndBackups($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete(sprintf('server/%s?storages=1&backups=delete', rawurlencode($ServerUUID)));
    }

    /**
     * Stops a server and waits until it reaches the 'stopped' state or a timeout occurs.
     *
     * @param string $ServerUUID The UUID of the server
     */
    public function stopServerAndWait($ServerUUID)
    {
        $result = $this->getServer($ServerUUID);
        $state = $result['response']['server']['state'];
        if ($state == 'started') {
            $this->stopServer($ServerUUID);
        }

        $times = 0;
        // If VM delete takes time please increase it but when tested working within 45 second
        while ($state != 'stopped' && $times < 45) {
            sleep(2);
            $result = $this->getServer($ServerUUID);
            $state = $result['response']['server']['state'];
            ++$times;
        }

        if ($state != 'stopped') {
            $this->logger->error('Can not stop server, taking time to response');
        }
    }

    /**
     * Gets details for a specific IP address.
     *
     * @param string $IPAddress The IP address
     * @return array The API response
     */
    public function getIpAddress($IPAddress)
    {
        return $this->get('ip_address/' . rawurlencode($IPAddress));
    }

    /**
     * Modifies the PTR record (reverse DNS) for an IP address.
     *
     * @param string $instanceId The UUID of the server
     * @param string $IP The IP address to modify
     * @param string $ptr_record The new PTR record value
     * @return array The API response
     */
    public function modifyIpAddress($instanceId, $IP, $ptr_record)
    {
        if (!($this->getIpAddress($IP)['response']['ip_address']['server'] == $instanceId)) {
            $this->logger->error('IP does not belong to your server');
        }
        return $this->put('ip_address/' . rawurlencode($IP), ['ip_address' => ['ptr_record' => $ptr_record]]);
    }


    /**
     * Updates the VNC password for a server by calling ModifyServer.
     *
     * @param string $instanceId The UUID of the server
     * @param string $vncPass The new VNC password
     * @return array The API response from ModifyServer
     */
    public function vncPasswordUpdate($instanceId, $vncPass)
    {
        return $this->put('server/' . rawurlencode($instanceId), ['server' => ['remote_access_password' => $vncPass]]);
    }

    /**
     * Enables or disables VNC access for a server by calling ModifyServer.
     *
     * @param string $instanceId The UUID of the server
     * @param string $vncType 'yes' to enable, 'no' to disable
     * @return array The API response from ModifyServer
     */
    public function vncEnableDisable($instanceId, $vncType)
    {
        return $this->put('server/' . rawurlencode($instanceId), ['server' => ['remote_access_enabled' => $vncType]]);
    }

    /**
     * Modifies general server configuration details.
     *
     * @param string $instanceId The UUID of the server
     * @param array $serverConfig An array containing server settings to modify (e.g., ['title' => 'new_title'])
     * @return array The API response
     */
    public function modifyVps($instanceId, $serverConfig)
    {
        return $this->put('server/' . rawurlencode($instanceId), $serverConfig);
    }

    /**
     * Formats bytes to Gigabytes (GB) with 2 decimal places.
     *
     * @param int $bytes The number of bytes
     * @return float The value in GB
     */
    public function formatSizeBytestoGb($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024, 2);
    }
}
