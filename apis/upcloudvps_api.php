<?php

use Blesta\Core\Util\Common\Traits\Container;

class UpcloudvpsApi
{
    use Container;

    private $curl;
    private $baseurl;
    private $httpHeader = [];
    private $apiuser;
    private $apipass;
    private $blestaVer;
    private $logger;
    public function __construct(array $params)
    {
        $this->baseurl = "https://api.upcloud.com/1.3/";
        $this->blestaVer = $params['blestaVer'];
        $this->apiuser = $params['apiuser'];
        $this->apipass = $params['apipass'];
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    protected function setHttpHeader($name, $value)
    {
        $this->httpHeader[$name] = $value;
    }

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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->apiuser . ':' . $this->apipass,
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

    public function get($url)
    {
        return $this->executeRequest('GET', $url);
    }

    public function post($url, $data = null)
    {
        return $this->executeRequest('POST', $url, $data);
    }

    public function put($url, $data = null)
    {
        return $this->executeRequest('PUT', $url, $data);
    }

    public function delete($url, $data = null)
    {
        return $this->executeRequest('DELETE', $url, $data);
    }

//VPS Stuffs
    public function GetAccountInfo()
    {
        return $this->get('account');
    }

    public function GetPrices()
    {
        return $this->get('price');
    }

    public function GetZones()
    {
        return $this->get('zone');
    }

    public function GetTimezones()
    {
        return $this->get('timezone');
    }

    public function GetPlans()
    {
        return $this->get('plan');
    }

    public function GetServerConfigurations()
    {
        return $this->get('server_size');
    }

    public function GetAllServers()
    {
        return $this->get('server');
    }

    public function GetServer($ServerUUID)
    {
        return $this->get('server/' . $ServerUUID);
    }

    public function GetTemplate()
    {
        return $this->get('storage/template');
    }

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

    public function serverOperation($action, $ServerUUID, $stop_type = null)
    {
        $data = [];
        if ($stop_type !== null) {
            $data[$action . '_server']['stop_type'] = $stop_type;
            $data[$action . '_server']['timeout'] = "60";
        }
        return $this->post('server/' . $ServerUUID . '/' . $action, $data);
    }

    public function StartServer($ServerUUID)
    {
        return $this->serverOperation('start', $ServerUUID);
    }

    public function StopServer($ServerUUID)
    {
        return $this->serverOperation('stop', $ServerUUID, 'hard');
    }

    public function RestartServer($ServerUUID)
    {
        return $this->serverOperation('restart', $ServerUUID, 'hard');
    }

    public function CancelServer($ServerUUID)
    {
        return $this->serverOperation('cancel', $ServerUUID);
    }

    public function DeleteServer($ServerUUID)
    {
        return $this->delete('server/' . $ServerUUID);
    }

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


    public function modifyStorage($storageId, $planSize)
    {
        $body = [
            'storage' => [
                'size' => $planSize
            ]
        ];
        return $this->put('storage/' . $storageId, $body);
    }

    public function DeleteServerAndStorage($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete('server/' . $ServerUUID . '?storages=1');
    }

    public function DeleteServerAndStorageAndBackups($ServerUUID)
    {
        $this->stopServerAndWait($ServerUUID);
        return $this->delete('server/' . $ServerUUID . '?storages=1&backups=delete');
    }

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

    public function GetIPaddress($IPAddress)
    {
        return $this->get('ip_address/' . $IPAddress);
    }

    public function ModifyIPaddress($instanceId, $IP, $ptr_record)
    {
        if (!($this->GetIPaddress($IP)['response']['ip_address']['server'] == $instanceId)) {
            $this->logger->error('IP does not belong to your server');
        }
        return $this->put('ip_address/' . $IP, ['ip_address' => ['ptr_record' => $ptr_record]]);
    }


    public function vncPasswordUpdate($instanceId, $vncPass)
    {
        return $this->put('server/' . $instanceId, ['server' => ['remote_access_password' => $vncPass]]);
    }

    public function vncEnableDisable($instanceId, $vncType)
    {
        return $this->put('server/' . $instanceId, ['server' => ['remote_access_enabled' => $vncType]]);
    }

    public function modifyVPS($instanceId, $serverConfig)
    {
        return $this->put('server/' . $instanceId, $serverConfig);
    }

    public function formatSizeBytestoTB($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024 / 1024, 2);
    }

    public function formatSizeBytestoMB($bytes)
    {
        return round($bytes / 1024 / 1024, 2);
    }

    public function formatSizeBytestoGB($bytes)
    {
        return round($bytes / 1024 / 1024 / 1024, 2);
    }

    public function formatSizeMBtoGB($MB)
    {
        return round($MB / 1024);
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $unit = ["B", "KB", "MB", "GB", "TB"];
        $exp = floor(log($bytes, 1024)) | 0;
        return round($bytes / (pow(1024, $exp)), $precision) . ' ' . $unit[$exp];
    }

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
