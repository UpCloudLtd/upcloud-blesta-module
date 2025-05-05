<?php

use Blesta\Core\Util\Common\Traits\Container;

class UpcloudvpsApi
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
     * @var \Psr\Log\LoggerInterface The logger instance
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param array $params API connection parameters including:
     *  - apiToken (string) The API token
     *  - blestaVer (string) The current Blesta version
     */
    public function __construct(array $params)
    {
        $this->baseurl = "https://api.upcloud.com/1.3/";
        $this->blestaVer = $params['blestaVer'];
        $this->setHttpHeader('Authorization', 'Bearer ' . $params['apiToken']);
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

        curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
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
    public function GetAccountInfo()
    {
        return $this->get('account');
    }

    /**
     * Gets pricing information.
     *
     * @return array The API response
     */
    public function GetPrices()
    {
        return $this->get('price');
    }

    /**
     * Gets available zones.
     *
     * @return array The API response
     */
    public function GetZones()
    {
        return $this->get('zone');
    }

    /**
     * Gets available timezones.
     *
     * @return array The API response
     */
    public function GetTimezones()
    {
        return $this->get('timezone');
    }

    /**
     * Gets available server plans.
     *
     * @return array The API response
     */
    public function GetPlans()
    {
        return $this->get('plan');
    }

    /**
     * Gets server size configurations.
     *
     * @return array The API response
     */
    public function GetServerConfigurations()
    {
        return $this->get('server_size');
    }

    /**
     * Gets a list of all servers associated with the account.
     *
     * @return array The API response
     */
    public function GetAllServers()
    {
        return $this->get('server');
    }

    /**
     * Gets details for a specific server.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function GetServer($ServerUUID)
    {
        return $this->get('server/' . $ServerUUID);
    }

    /**
     * Gets available storage templates (OS images).
     *
     * @return array The API response
     */
    public function GetTemplate()
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
    public function CreateServer($params)
    {
        $Templates = $this->GetTemplate()['response']['storages']['storage'];
        foreach ($Templates as $Template) {
            if ($Template['uuid'] == $params['template']) {
                $TemplateTitle = $Template['title'];
                $TemplateUUID = $Template['uuid'];
                break;
            }
        }
        $AllPlans = $this->Getplans()['response']['plans']['plan'];
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
                'plan' => $PlanName, // Getplans()
                //  "simple_backup" => "0430,weeklies",
                'remote_access_enabled' => 'yes',
                'storage_devices' => [
                    'storage_device' => [
                        [
                            'action' => 'clone',
                            'storage' => $TemplateUUID, // GetTemplates()
                            'size' => $PlanSize, // storage_size from Getplans()
                            'tier' => $PlanTier, // storage_tier from Getplans()
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
        return $this->post('server/' . $ServerUUID . '/' . $action, $data);
    }

    /**
     * Starts a server.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function StartServer($ServerUUID)
    {
        return $this->serverOperation('start', $ServerUUID);
    }

    /**
     * Stops a server (hard stop).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function StopServer($ServerUUID)
    {
        return $this->serverOperation('stop', $ServerUUID, 'hard');
    }

    /**
     * Restarts a server (hard restart).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function RestartServer($ServerUUID)
    {
        return $this->serverOperation('restart', $ServerUUID, 'hard');
    }

    /**
     * Cancels a server operation (e.g., ongoing creation).
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function CancelServer($ServerUUID)
    {
        return $this->serverOperation('cancel', $ServerUUID);
    }

    /**
     * Deletes a server. Does not delete attached storage by default.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response
     */
    public function DeleteServer($ServerUUID)
    {
        return $this->delete('server/' . $ServerUUID);
    }

    /**
     * Modifies a server's plan (upgrades/downgrades).
     * Stops the server, changes the plan, resizes the primary storage device if needed, and restarts.
     *
     * @param string $uuid The UUID of the server
     * @param string $Plan The name of the target plan
     * @return array The API response from the final operation (storage resize or plan change)
     */
    public function ModifyServer($uuid, $Plan)
    {
        $this->stopServerAndWait($uuid);
        $allPlans = $this->Getplans()['response']['plans']['plan'];
        foreach ($allPlans as $plan) {
            if ($plan['name'] == $Plan) {
                $planSize = $plan['storage_size'];
                break;
            }
        }

        $upgradePlan = $this->put('server/' . $uuid, ['server' => ['plan' => $Plan]]);
        if ($upgradePlan['response']['error']['error_message']) {
            return $upgradePlan;
        } else {
            $storages = $this->GetServer($uuid)['response']['server']['storage_devices']['storage_device'];
            foreach ($storages as $storage) {
                if ($storage['part_of_plan'] == "yes") {
                    $storageId = $storage['storage'];
                    $existingStorageSize = $storage['storage_size'];
                    break;
                }
            }
            if ($storageId && $planSize > $existingStorageSize) {
                $modeyStorage = $this->modifyStorage($storageId, $planSize);
                $this->StartServer($uuid);
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
        return $this->put('storage/' . $storageId, $body);
    }

    /**
     * Deletes a server and its associated storage devices.
     * Stops the server first.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response from the delete operation
     */
    public function DeleteServerAndStorage($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete('server/' . $ServerUUID . '?storages=1');
    }

    /**
     * Deletes a server and its associated storage devices and backups.
     * Stops the server first.
     *
     * @param string $ServerUUID The UUID of the server
     * @return array The API response from the delete operation
     */
    public function DeleteServerAndStorageAndBackups($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete('server/' . $ServerUUID . '?storages=1&backups=delete');
    }

    /**
     * Stops a server and waits until it reaches the 'stopped' state or a timeout occurs.
     *
     * @param string $ServerUUID The UUID of the server
     */
    public function stopServerAndWait($ServerUUID)
    {
        $result = $this->GetServer($ServerUUID);
        $state = $result['response']['server']['state'];
        if ($state == 'started') {
            $this->StopServer($ServerUUID);
        }

        $times = 0;
        // If VM delete takes time please increase it but when tested working within 45 second
        while ($state != 'stopped' && $times < 45) {
            sleep(2);
            $result = $this->GetServer($ServerUUID);
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
    public function GetIPaddress($IPAddress)
    {
        return $this->get('ip_address/' . $IPAddress);
    }

    /**
     * Modifies the PTR record (reverse DNS) for an IP address.
     *
     * @param string $instanceId The UUID of the server
     * @param string $IP The IP address to modify
     * @param string $ptr_record The new PTR record value
     * @return array The API response
     */
    public function ModifyIPaddress($instanceId, $IP, $ptr_record)
    {
        if (!($this->GetIPaddress($IP)['response']['ip_address']['server'] == $instanceId)) {
            $this->logger->error('IP does not belong to your server');
        }
        return $this->put('ip_address/' . $IP, ['ip_address' => ['ptr_record' => $ptr_record]]);
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
        return $this->put('server/' . $instanceId, ['server' => ['remote_access_password' => $vncPass]]);
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
        return $this->put('server/' . $instanceId, ['server' => ['remote_access_enabled' => $vncType]]);
    }

    /**
     * Modifies general server configuration details.
     *
     * @param string $instanceId The UUID of the server
     * @param array $serverConfig An array containing server settings to modify (e.g., ['title' => 'new_title'])
     * @return array The API response
     */
    public function modifyVPS($instanceId, $serverConfig)
    {
        return $this->put('server/' . $instanceId, $serverConfig);
    }

    /**
     * Formats bytes to Terabytes (TB) with 2 decimal places.
     *
     * @param int $bytes The number of bytes
     * @return float The value in TB
     */
    public function formatSizeBytestoTB($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024 / 1024, 2);
    }

    /**
     * Formats bytes to Megabytes (MB) with 2 decimal places.
     *
     * @param int $bytes The number of bytes
     * @return float The value in MB
     */
    public function formatSizeBytestoMB($bytes)
    {
        return round($bytes / 1024 / 1024, 2);
    }

    /**
     * Formats bytes to Gigabytes (GB) with 2 decimal places.
     *
     * @param int $bytes The number of bytes
     * @return float The value in GB
     */
    public function formatSizeBytestoGB($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024, 2);
    }

    /**
     * Formats Megabytes (MB) to Gigabytes (GB) with 2 decimal places.
     *
     * @param int $MB The number of Megabytes
     * @return float The value in GB
     */
    public function formatSizeMBtoGB($MB)
    {
        return round($MB / 1024);
    }

    /**
     * Formats bytes into a human-readable string with appropriate units (B, KB, MB, GB, TB).
     *
     * @param int $bytes The number of bytes
     * @param int $precision The number of decimal places (default: 2)
     * @return string The formatted size string (e.g., "1.23 GB")
     */
    public function formatBytes($bytes, $precision = 2)
    {
        $unit = ["B", "KB", "MB", "GB", "TB"];
        $exp = floor(log($bytes, 1024)) | 0;
        return round($bytes / (pow(1024, $exp)), $precision) . ' ' . $unit[$exp];
    }

    /**
     * Attempts to get the client's real IP address by checking various $_SERVER variables.
     * Handles proxy headers and comma-separated values.
     *
     * @return string The client's IP address or 'UNKNOWN' if not found
     */
    public function getClientIp()
    {
        $ip_address = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ip_address = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip_address = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ip_address = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip_address = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ip_address = getenv('HTTP_FORWARDED');
        } else {
            $ip_address = getenv('REMOTE_ADDR');
        }
        return $ip_address;
    }
}
