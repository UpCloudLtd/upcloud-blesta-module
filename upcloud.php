<?php

use Blesta\Core\Util\Validate\Server;

class Upcloud extends Module
{
    public function __construct()
    {
        Language::loadLang('upcloud', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Loader::loadComponents($this, ['Input']);
        Configure::load('upcloud', dirname(__FILE__) . DS . 'config' . DS);
    }

    public function manageModule($module, array &$vars)
    {
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('module', $module);
        return $this->view->fetch();
    }

    public function manageAddRow(array &$vars)
    {
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }
        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    public function manageEditRow($module_row, array &$vars)
    {
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }
        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['account_name', 'user_key', 'pass_key'];
        $encrypted_fields = ['user_key', 'pass_key'];
        $this->Input->setRules($this->getRowRules($vars));
        if ($this->Input->validates($vars)) {
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }
            return $meta;
        }
    }

    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['account_name', 'user_key', 'pass_key'];
        $encrypted_fields = ['user_key', 'pass_key'];
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }
        $this->Input->setRules($this->getRowRules($vars));
        if ($this->Input->validates($vars)) {
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    private function getRowRules(&$vars)
    {
        $rules = [
            'account_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Upcloudvps.!error.account_name_valid', true)
                ]
            ],
            'user_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Upcloudvps.!error.user_key_valid', true)
                ]
              ],
            'pass_key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Upcloudvps.!error.pass_key_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        isset($vars['user_key']) ? $vars['user_key'] : '',
                    ],
                    'message' => Language::_('Upcloudvps.!error.pass_key_valid_connection', true)
                ]
            ]
        ];

        return $rules;
    }

    public function validateConnection($pass_key, $user_key)
    {
        try {
            $api = $this->getApi($pass_key, $user_key);
            $result = $api->GetAccountInfo();
            $this->log('upcloud|accountRequest', serialize($result), 'input', true);
            if ($result['response_code'] == '200') {
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    private function getApi($pass_key, $user_key)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'upcloudvps_api.php');
        Loader::loadComponents($this, ['Record']);
        $setting = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])
            ->select(['?' => 'level'], false)
            ->appendValues(['system'])
            ->from('settings')
            ->where('key', '=', 'database_version')
            ->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        $blestaVer = $setting->value;
        $params = [
            'apiuser' => $user_key, //username
            'apipass' => $pass_key, //password
            'blestaVer' => $blestaVer
        ];
        $api = new UpcloudvpsApi($params);
        return $api;
    }

    private function getServerPlans($module_row)
    {
        $api = $this->getApi($module_row->meta->pass_key, $module_row->meta->user_key);
        $result = $api->Getplans()['response']['plans']['plan'];
       //$this->log('upcloud|Getplans', serialize($result), 'input', true);
        $Vmplans = [];
        foreach ($result as $plan) {
            $PlanDesc = Language::_('Upcloudvps.package.cpu', true) . ': ' . $plan['core_number'] . ' ' . Language::_('Upcloudvps.package.cpu', true) . ' / ' . Language::_('Upcloudvps.package.memory', true) . ': '
            . $plan['memory_amount'] . Language::_('Upcloudvps.package.MB', true) . ' / ' . Language::_('Upcloudvps.package.disk', true) . ': ' . $plan['storage_size'] . Language::_('Upcloudvps.package.GB', true);
            $Vmplans[$plan['name']] = $plan['name'] . ' [ ' . $PlanDesc . ' ]';
        }
        return $Vmplans;
    }

    private function getTemplates($module_row, $package = null)
    {
        $api = $this->getApi($module_row->meta->pass_key, $module_row->meta->user_key);
        $result_os = $api->GetTemplate()['response']['storages']['storage'];
       //$this->log('upcloud|GetTemplates', serialize($result_os), 'input', true);
        $templates = [];
        if ($package) {
            foreach ($result_os as $os) {
                if (strpos($os['title'], 'Windows') !== false || strpos($os['title'], 'UpCloud') !== false) {
                    continue;
                }
                $templates[$os['uuid']] =  $os['title'];
            }
        } else {
            foreach ($result_os as $os) {
                $templates[$os['uuid']] =  $os['title'];
            }
        }

        return $templates;
    }

    private function getLocations($module_row)
    {
        $api = $this->getApi($module_row->meta->pass_key, $module_row->meta->user_key);
        $zones = $api->GetZones()['response']['zones']['zone'];
    // $this->log('upcloud|getLocations', serialize($zones), 'input', true);
        $zoneLocation = [];
        foreach ($zones as $zone) {
            $zoneLocation[$zone['id']] = $zone['description'];
        }
        return $zoneLocation;
    }

    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    // Set whether to show or hide the template option
                    $('select[name=\"meta[template]\"]').closest('li').hide();
                    $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();

                    if ($('input[name=\"meta[set_template]\"]:checked').val() == 'admin') {
                        $('select[name=\"meta[template]\"]').closest('li').show();
                        $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();
                    } else {
                        $('select[name=\"meta[template]\"]').closest('li').hide();
                        $('input[name=\"meta[surcharge_templates]\"]').closest('li').show();
                    }

                    $('input[name=\"meta[set_template]\"]').change(function() {
                        if ($(this).val() == 'admin') {
                            $('select[name=\"meta[template]\"]').closest('li').show();
                            $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();
                        } else {
                            $('select[name=\"meta[template]\"]').closest('li').hide();
                            $('input[name=\"meta[surcharge_templates]\"]').closest('li').show();
                        }
                    });
                });
            </script>
        ");

        // Fetch the 1st account from the list of accounts
        $module_row = null;
        $rows = $this->getModuleRows();

        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);

        // Fetch all the plans available for the different server types
        $server_plans = [];
        $server_templates = [];

        if ($module_row) {
            $server_plans = $this->getServerPlans($module_row);
            $server_templates = $this->getTemplates($module_row);
        }

        $server_plan = $fields->label(
            Language::_('Upcloudvps.package_fields.server_plan', true),
            'upcloudvps_instances_plan'
        );
        $server_plan->attach(
            $fields->fieldSelect(
                'meta[server_plan]',
                $server_plans,
                (isset($vars->meta['server_plan']) ? $vars->meta['server_plan'] : null),
                ['id' => 'upcloudvps_instances_plan']
            )
        );
        $fields->setField($server_plan);

        // Set the template options
        $template_options = $fields->label(Language::_('Upcloudvps.package_fields.set_template', true));

        $admin_set_template_label = $fields->label(Language::_('Upcloudvps.package_fields.admin_set_template', true));
        $template_options->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'admin',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : 'admin') == 'admin',
                ['id' => 'upcloudvps_admin_set_template'],
                $admin_set_template_label
            )
        );

        $client_set_template_label = $fields->label(Language::_('Upcloudvps.package_fields.client_set_template', true));
        $template_options->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'client',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : null) == 'client',
                ['id' => 'upcloudvps_client_set_template'],
                $client_set_template_label
            )
        );

        $fields->setField($template_options);

        // Set the server templates as a selectable option
        $template = $fields->label(
            Language::_('Upcloudvps.package_fields.template', true),
            'upcloudvps_template'
        );
        $template->attach(
            $fields->fieldSelect(
                'meta[template]',
                $server_templates,
                (isset($vars->meta['template']) ? $vars->meta['template'] : null),
                ['id' => 'upcloudvps_template']
            )
        );
        $fields->setField($template);

        // Set the surcharge templates permissions
        $surcharge_templates_options = $fields->label(Language::_('Upcloudvps.package_fields.surcharge_templates', true));

        $allow_surcharge_templates_label = $fields->label(
            Language::_('Upcloudvps.package_fields.allow_surcharge_templates', true)
        );
        $surcharge_templates_options->attach(
            $fields->fieldRadio(
                'meta[surcharge_templates]',
                'allow',
                (isset($vars->meta['surcharge_templates']) ? $vars->meta['surcharge_templates'] : 'allow') == 'allow',
                ['id' => 'upcloudvps_allow_surcharge_templates'],
                $allow_surcharge_templates_label
            )
        );

        $disallow_surcharge_templates_label = $fields->label(
            Language::_('Upcloudvps.package_fields.disallow_surcharge_templates', true)
        );
        $surcharge_templates_options->attach(
            $fields->fieldRadio(
                'meta[surcharge_templates]',
                'disallow',
                (isset($vars->meta['surcharge_templates']) ? $vars->meta['surcharge_templates'] : null) == 'disallow',
                ['id' => 'upcloudvps_disallow_surcharge_templates'],
                $disallow_surcharge_templates_label
            )
        );

        $fields->setField($surcharge_templates_options);

        return $fields;
    }

    private function getPackageRules(&$vars)
    {
        $rules = [
            'meta[server_plan]' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Upcloudvps.!error.meta[server_plan].format', true),
                ]
            ]
        ];
        if ($vars['meta']['server_type']) {
            unset($rules['meta[server_plan]']);
        }
        return $rules;
    }

    public function addPackage(array $vars = null)
    {
        $this->Input->setRules($this->getPackageRules($vars));
        $meta = [];
        if ($this->Input->validates($vars)) {
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }
        return $meta;
    }

    public function editPackage($package, array $vars = null)
    {
        return $this->addPackage($vars);
    }

    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'StopServer', 'upcloud|suspend');
    }

    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'StartServer', 'upcloud|unsuspend');
    }

    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'DeleteServernStorage', 'upcloud|cancel', '204');
    }

    private function performServiceAction($service, $actionName, $logTag, $expectedResponseCode = null)
    {
        $module_row = $this->getModuleRow();
        if ($module_row) {
            $api = $this->getApi($module_row->meta->pass_key, $module_row->meta->user_key);
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $vmId = $service_fields->upcloudvps_vmid;
            //$this->log($logTag, serialize(['vm_id' => $vmId]), 'input', true);
            $actionResponse = $api->$actionName($vmId);
            $success = true;
            if ($expectedResponseCode && $actionResponse['response_code'] !== $expectedResponseCode) {
                $this->Input->setErrors(['api' => ['response' => $actionResponse['response']['error']['error_message']]]);
                $success = false;
            }
            if ($actionResponse['error']['error_message']) {
                $this->Input->setErrors(['api' => ['response' => $actionResponse['error']['error_message']]]);
                $success = false;
            }
            $this->log($logTag, serialize($actionResponse), 'output', $success);
        }
        return null;
    }


    private function serviceOptionsToObject($options)
    {
        $object = [];
        if (is_array($options) && !empty($options)) {
            foreach ($options as $option) {
                $object[$option->option_name] = $option->option_value;
            }
        }
        return (object) $object;
    }

    private function getFieldsFromInput(array $vars, $package)
    {
        if ($package->meta->set_template == 'admin') {
            $osid = $package->meta->template;
        } elseif ($package->meta->set_template == 'client') {
            $osid = $vars['upcloudvps_template'] ?? null;
        }
        $fields = [
            'zone' => $vars['upcloudvps_location'] ?? null,
            'plan' => $package->meta->server_plan,
            'template' => $osid,
            'title' => isset($vars['upcloudvps_hostname']) ? strtolower($vars['upcloudvps_hostname']) : null
        ];
        return $fields;
    }

    public function validateService($package, array $vars = null)
    {
        $rules = $this->getServiceRules($vars, $package, false);
        if (isset($package->meta->set_template) && $package->meta->set_template == 'admin') {
            unset($rules['upcloudvps_template']);
        }
        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    private function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        $rules = [
            'upcloudvps_hostname' => [
                'format' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Upcloudvps.!error.upcloudvps_hostname.format', true)
                ]
            ],
            'upcloudvps_location' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateLocation']],
                    'message' => Language::_('Upcloudvps.!error.upcloudvps_location.valid', true)
                ]
            ],
            'upcloudvps_template' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateTemplate']],
                    'message' => Language::_('Upcloudvps.!error.upcloudvps_template.valid', true)
                ]
            ]
        ];

        return $rules;
    }

    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    public function validateLocation($location)
    {
        $module_row = null;
        $rows = $this->getModuleRows();
        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);
        $valid_locations = $this->getLocations($module_row);
        return array_key_exists(trim($location), $valid_locations);
    }

    public function validateTemplate($template)
    {
        $module_row = null;
        $rows = $this->getModuleRows();
        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);
        $valid_templates = $this->getTemplates($module_row);
        return array_key_exists(trim($template), $valid_templates);
    }


    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending')
    {
        // Get the module row
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Upcloudvps.!error.module_row.missing', true)]]
            );
            return;
        }
        $vars['upcloudvps_hostname'] = strtolower($vars['upcloudvps_hostname']);
        $params = $this->getFieldsFromInput((array) $vars, $package);
        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }
        if ($vars['use_module'] == 'true') {
            $this->log('upcloud|create', serialize($params), 'input', true);

            try {
                $api = $this->getApi($row->meta->pass_key, $row->meta->user_key);
                if (empty($vars['upcloudvps_vmid'])) {
                    $server = $api->CreateServer($params);
                    $this->log('upcloud', serialize($server), 'output', true);
                    if ($server['response_code'] != '202') {
                        $this->Input->setErrors(
                            ['api' => ['internal' => $server['response']['error']['error_message']]]
                        );
                    }
                }
            } catch (Exception $e) {
                $this->Input->setErrors(
                    ['api' => ['internal' => $server['response']['error']['error_message']]]
                );
            }

            if ($this->Input->errors()) {
                return;
            }
            foreach ($server['response']['server']['ip_addresses']['ip_address'] as $IPList) {
                if ($IPList['access'] == "public") {
                    if ($IPList['family'] == "IPv4" && ($IPList['part_of_plan'] )) {
                        $IPv4 = $IPList['address'];
                    }
                }
            }
        }
        return [
            [
                'key' => 'upcloudvps_hostname',
                'value' => $vars['upcloudvps_hostname'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'upcloudvps_template',
                'value' => $vars['upcloudvps_template'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'upcloudvps_location',
                'value' => $vars['upcloudvps_location'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'upcloudvps_password',
                'value' => $server['response']['server']['password'] ?? ($server['response']['server']['password'] ?? null),
                'encrypted' => 1
            ],
            [
                'key' => 'upcloudvps_ipaddress',
                'value' => $IPv4 ?? ($IPv4 ?? null),
                'encrypted' => 0
            ],
            [
                'key' => 'upcloudvps_vmid',
                'value' => $server['response']['server']['uuid'] ?? ($server['response']['server']['uuid'] ?? null),
                'encrypted' => 0
            ]
        ];
    }

    public function validateServiceEdit($service, array $vars = null)
    {
        $rules = $this->getServiceRules($vars, null, true);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if (
            !isset($service_fields->upcloudvps_template)
            || !isset($vars['upcloudvps_template'])
            || $service_fields->upcloudvps_template == $vars['upcloudvps_template']
        ) {
            unset($rules['upcloudvps_template']);
        }
        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }


    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Upcloudvps.!error.module_row.missing', true)]]
            );
            return;
        }
        $service_fields = $this->serviceFieldsToObject($service->fields);
        if (isset($vars['upcloudvps_hostname'])) {
            $vars['upcloudvps_hostname'] = strtolower($vars['upcloudvps_hostname']);
        }
        $params = $this->getFieldsFromInput((array) $vars, $package);
        $this->validateServiceEdit($service, $vars);
        if ($this->Input->errors()) {
            return;
        }
        $delta = [];
        foreach ($vars as $key => $value) {
            if (!array_key_exists($key, (array) $service_fields) || $vars[$key] != $service_fields->$key) {
                $delta[$key] = $value;
            }
        }
        if ($vars['use_module'] == 'true' && !isset($delta['upcloudvps_vmid'])) {
            $api = $this->getApi($row->meta->pass_key, $row->meta->user_key);
            if ($this->Input->errors()) {
                return;
            }
        }
        $fields = [
            'upcloudvps_vmid',
            'upcloudvps_template',
        ];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }
        $fields = [];
        $encrypted_fields = [];
        foreach ($service_fields as $key => $value) {
            $fields[] = ['key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0)];
        }
        return $fields;
    }

    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        $module_row = $this->getModuleRow(($package->module_row ?? 0));
        $templates = $this->getTemplates($module_row, $package);
        $locations = $this->getLocations($module_row);

        $fields = new ModuleFields();
        $vmid = $fields->label(Language::_('Upcloudvps.service_field.vmid', true), 'upcloudvps_vmid');
        $vmid->attach(
            $fields->fieldText(
                'upcloudvps_vmid',
                ($vars->upcloudvps_vmid ?? ($vars->vmid ?? null)),
                ['id' => 'upcloudvps_vmid']
            )
        );
        $tooltip = $fields->tooltip(Language::_('Upcloudvps.service_field.tooltip.vmid', true));
        $vmid->attach($tooltip);
        $fields->setField($vmid);

        // Create hostname label
        $hostname = $fields->label(Language::_('Upcloudvps.service_field.hostname', true), 'upcloudvps_hostname');
        $hostname->attach(
            $fields->fieldText(
                'upcloudvps_hostname',
                ($vars->upcloudvps_hostname ?? ($vars->hostname ?? null)),
                ['id' => 'upcloudvps_hostname']
            )
        );
        $fields->setField($hostname);

        // Set the server location as a selectable option
        $location = $fields->label(Language::_('Upcloudvps.service_field.location', true), 'upcloudvps_location');
        $location->attach(
            $fields->fieldSelect(
                'upcloudvps_location',
                $locations,
                ($vars->upcloudvps_location ?? ($vars->location ?? null)),
                ['id' => 'upcloudvps_location']
            )
        );
        $fields->setField($location);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Upcloudvps.service_field.template', true), 'upcloudvps_template');
            $template->attach(
                $fields->fieldSelect(
                    'upcloudvps_template',
                    $templates,
                    ($vars->upcloudvps_template ?? ($vars->template ?? null)),
                    ['id' => 'upcloudvps_template']
                )
            );
            $fields->setField($template);
        }

        return $fields;
    }

    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        $module_row = $this->getModuleRow((isset($package->module_row) ? $package->module_row : 0));
        $templates = $this->getTemplates($module_row, $package);
        $fields = new ModuleFields();
        $subid = $fields->label(Language::_('Upcloudvps.service_field.vmid', true), 'upcloudvps_vmid');
        $subid->attach(
            $fields->fieldText(
                'upcloudvps_vmid',
                ($vars->upcloudvps_vmid ?? ($vars->vmid ?? null)),
                ['id' => 'upcloudvps_vmid']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Upcloudvps.service_field.tooltip.vmid', true));
        $subid->attach($tooltip);
        $fields->setField($subid);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Upcloudvps.service_field.template', true), 'upcloudvps_template');
            $template->attach(
                $fields->fieldSelect(
                    'upcloudvps_template',
                    $templates,
                    ($vars->upcloudvps_template ?? ($vars->template ?? null)),
                    ['id' => 'upcloudvps_template']
                )
            );
            $fields->setField($template);
        }

        return $fields;
    }

    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        $module_row = $this->getModuleRow($package->module_row);
        $templates = $this->getTemplates($module_row, $package);
        $locations = $this->getLocations($module_row);

        $fields = new ModuleFields();
        $hostname = $fields->label(Language::_('Upcloudvps.service_field.hostname', true), 'upcloudvps_hostname');
        $hostname->attach(
            $fields->fieldText(
                'upcloudvps_hostname',
                ($vars->upcloudvps_hostname ?? ($vars->hostname ?? null)),
                ['id' => 'upcloudvps_hostname']
            )
        );
        $fields->setField($hostname);

        // Set the server location as a selectable option
        $location = $fields->label(Language::_('Upcloudvps.service_field.location', true), 'upcloudvps_location');
        $location->attach(
            $fields->fieldSelect(
                'upcloudvps_location',
                $locations,
                ($vars->upcloudvps_location ?? ($vars->location ?? null)),
                ['id' => 'upcloudvps_location']
            )
        );
        $fields->setField($location);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Upcloudvps.service_field.template', true), 'upcloudvps_template');
            $template->attach(
                $fields->fieldSelect(
                    'upcloudvps_template',
                    $templates,
                    ($vars->upcloudvps_template ?? ($vars->template ?? null)),
                    ['id' => 'upcloudvps_template']
                )
            );
            $fields->setField($template);
        }

        return $fields;
    }

    private function getServiceInfo($service, $package, $client = false)
    {
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $api = $this->getApi($row->meta->pass_key, $row->meta->user_key);
        $response = $api->GetServer($service_fields->upcloudvps_vmid)['response']['server'];
        $server_details = $response ?? (object) [];
        $this->view = new View($client ? 'client_service_info' : 'admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        foreach ($server_details['storage_devices']['storage_device'] as $temp) {
            if ($temp['part_of_plan'] == "yes" || $server_details['plan'] == "custom") {
                $server_details['osname'] = $temp['storage_title'];
                break;
            }
        }

        $zones = $api->GetZones()['response']['zones']['zone'];

        foreach ($zones as $zone) {
            if ($zone['id'] == $server_details['zone']) {
                $server_details['zoneDescription'] = $zone['description'];
                break;
            }
        }

        if (!empty($server_details["ip_addresses"])) {
            foreach ($server_details['ip_addresses']['ip_address'] as $ip) {
                if ($ip["family"] == "IPv4" && $ip["access"] == "public" && $ip["part_of_plan"] == "yes") {
                    $server_details['ipaddv4'] = $ip['address'];
                }
                if ($ip["family"] == "IPv6" && $ip["access"] == "public") {
                    $server_details['ipaddv6'] = $ip['address'];
                }
            }
        }


        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('server_details', ($server_details ?? new stdClass()));

        return $this->view->fetch();
    }

    public function getAdminServiceInfo($service, $package)
    {
        return $this->getServiceInfo($service, $package);
    }

    public function getClientServiceInfo($service, $package)
    {
        return $this->getServiceInfo($service, $package, true);
    }


    public function getAdminTabs($package)
    {
        return ['tabActions' => Language::_('Upcloudvps.tab_actions', true),];
    }

    public function getClientTabs($package)
    {
        return ['tabClientActions' => Language::_('Upcloudvps.tab_client_actions', true),];
    }

    public function tabActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getTabActions($package, $service, $post);
    }

    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getTabActions($package, $service, $post, true);
    }

    private function getTabActions($package, $service, array $post = null, $client = false)
    {
        $row = $this->getModuleRow();
        $this->view = new View($client ? 'tab_client_actions' : 'tab_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Services']);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $templates = $this->getTemplates($row, $package);
        $api = $this->getApi($row->meta->pass_key, $row->meta->user_key);
      //  $this->log('upcloud|GetVMInformation', serialize($service_fields), 'input', true);
        $vmId = $service_fields->upcloudvps_vmid;
        $server_details = $api->GetServer($vmId)['response']['server'];

        // Perform actions
        if (!empty($post['action'])) {
            switch ($post['action']) {
                case 'restart':
                    $action = $api->RestartServer($vmId)['response'];
                    break;
                case 'change_ptr':
                    if ($post['ip'] && $post['ptr']) {
                        $action = $api->ModifyIPaddress($vmId, $post['ip'], $post['ptr'])['response'];
                    }
                    break;
                case 'start':
                    $action = $api->StartServer($vmId)['response'];
                    break;
                case 'stop':
                    $action = $api->StopServer($vmId)['response'];
                    break;
                case 'enblvnc':
                    $action = $api->vncEnableDisable($vmId, 'yes')['response'];
                    break;
                case 'disblvnc':
                    $action = $api->vncEnableDisable($vmId, 'no')['response'];
                    break;
                case 'changepassword':
                    $action = $api->vncPasswordUpdate($vmId, $this->generatePassword())['response'];
                    break;
                default:
                    break;
            }
            if ($action['error']['error_message']) {
                $this->Input->setErrors(['api' => ['response' => $action['error']['error_message']]]);
            }
          //      $this->log('upcloud|action', serialize($action), 'output', true);
        }

        foreach ($server_details['storage_devices']['storage_device'] as $temp) {
            if ($temp['part_of_plan'] == "yes" || $server_details['plan'] == "custom") {
                $server_details['osname'] = $temp['storage_title'];
                $server_details['space_gb'] = $temp['storage_size'];
                break;
            }
        }

        $zones = $api->GetZones()['response']['zones']['zone'];

        foreach ($zones as $zone) {
            if ($zone['id'] == $server_details['zone']) {
                $server_details['zoneDescription'] = $zone['description'];
                break;
            }
        }

        $ipaddress = [];
        if (!empty($server_details["ip_addresses"])) {
            foreach ($server_details['ip_addresses']['ip_address'] as $ip) {
                if ($ip["access"] == "public") {
                    $ipaddress[$ip['address']] =  $ip['address'];
                }
                if ($ip["family"] == "IPv4" && $ip["access"] == "public" && $ip["part_of_plan"] == "yes") {
                    $server_details['ipaddv4'] = $ip['address'];
                }
                if ($ip["family"] == "IPv6" && $ip["access"] == "public") {
                    $server_details['ipaddv6'] = $ip['address'];
                }
            }
        }

        if (!empty($server_details['networking']['interfaces']['interface'])) {
            foreach ($server_details['networking']['interfaces']['interface'] as $key => $ip) {
                $ReverseDNSValue = $api->GetIPaddress($ip['ip_addresses']['ip_address'][0]['address'])['response'];
                if (strpos($ReverseDNSValue['ip_address']['ptr_record'], "upcloud") !== false) {
                    $api->ModifyIPaddress($vmId, $ip['ip_addresses']['ip_address'][0]['address'], "client." . $_SERVER['SERVER_NAME'] . ".host");
                }
                $ReverseDNSValue = $api->GetIPaddress($ip['ip_addresses']['ip_address'][0]['address'])['response'];
                $server_details['networking']['interfaces']['interface'][$key]['ptr'] = $ReverseDNSValue['ip_address']['ptr_record'];
            }
        }

        $server_details['remote_access_host'] = gethostbyname($server_details['remote_access_host']);
        $server_details['user'] = (strpos($server_details['osname'], 'Windows') !== false) ? "administrator" : "root";
        $server_details['rootpassword'] = $service_fields->upcloudvps_password;

        if ($server_details['remote_access_enabled'] == "no") {
            unset($server_details['remote_access_password']);
        }

        foreach ($api->Getplans()['response']['plans']['plan'] as $Plan) {
            if ($Plan['name'] == $server_details['plan'] and $Plan['memory_amount'] == $server_details['memory_amount']) {
                $TotalTraffic = $Plan['public_traffic_out'] ;
                $Outgoing = $api->formatSizeBytestoGB($server_details['plan_ipv4_bytes'] + $server_details['plan_ipv6_bytes']);
                $Percentage = round((($Outgoing / $TotalTraffic) * 100), 2) ;
                $progressClass = 'progress-bar-success';
                if ($Percentage >= 49 && $Percentage < 70) {
                           $progressClass = 'progress-bar-info';
                } elseif ($Percentage >= 70 && $Percentage < 86) {
                        $progressClass = 'progress-bar-warning';
                } elseif ($Percentage >= 86) {
                     $progressClass = 'progress-bar-danger';
                }

                $server_details['Bandwidth'] = '<div class="progress">
       <div class="progress-bar ' . $progressClass . '" role="progressbar" aria-valuenow="' . $Percentage . '"
       aria-valuemin="0" aria-valuemax="100" style="width:' . $Percentage . '%">
         ' . $Percentage . '%
       </div>
     </div>';
            }
        }

        $server_details['limit'] = $TotalTraffic . ' ' . Language::_('Upcloudvps.graphs.GB', true);
        $server_details['used'] = $Outgoing . ' ' . Language::_('Upcloudvps.graphs.GB', true);
        $server_details['percentage'] = $Percentage . '%';

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('ipaddress', $ipaddress);
        $this->view->set('server_details', ($server_details ?? new stdClass()));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);

        return $this->view->fetch();
    }

    private function generatePassword()
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pool_size = strlen($pool);
        $password = '';

        for ($i = 0; $i < 8; $i++) {
                $password .= $pool[mt_rand(0, $pool_size - 1)];
        }

                return $password;
    }

    public function changeServicePackage($package_from, $package_to, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->pass_key, $row->meta->user_key);
            if ($package_from->meta->server_plan != $package_to->meta->server_plan) {
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $action = $api->ModifyServer($service_fields->upcloudvps_vmid, $package_to->meta->server_plan);
                if ($action['response']['error']['error_message']) {
                    $this->Input->setErrors(['api' => ['response' => $action['response']['error']['error_message']]]);
                }
            }
        }
        return null;
    }
}
