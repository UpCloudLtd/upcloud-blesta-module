<?php

use Blesta\Core\Util\Validate\Server;

class Upcloud extends Module
{
    /**
     * Initializes the module. Loads language files, configuration, and components.
     */
    public function __construct()
    {
        Language::loadLang('upcloud', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
        Loader::loadComponents($this, ['Input']);
        Configure::load('upcloud', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Renders the manage module page.
     * This page is used to display module information and settings.
     *
     * @param stdClass $module The module instance
     * @param array &$vars An array of post data submitted to the manage module page (when applicable)
     * @return string The rendered view
     */
    public function manageModule($module, array &$vars)
    {
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('module', $module);
        return $this->view->fetch();
    }

    /**
     * Renders the view for adding a module row (server account).
     *
     * @param array &$vars An array of post data submitted (if any)
     * @return string The rendered view
     */
    public function manageAddRow(array &$vars)
    {
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    /**
     * Renders the view for editing a module row (server account).
     *
     * @param stdClass $module_row The module row to edit
     * @param array &$vars An array of post data submitted (if any)
     * @return string The rendered view
     */
    public function manageEditRow($module_row, array &$vars)
    {
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'upcloud' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        if (empty($vars)) {
            $vars = $module_row->meta;
        }
        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    /**
     * Adds a new module row (server account). Validates input and returns meta data.
     *
     * @param array &$vars An array of input data including:
     *  - account_name (string) The account name
     *  - api_token (string) The API token
     * @return array An array of meta fields to be stored for the module row
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['account_name', 'api_token', 'api_base_url'];
        $encrypted_fields = ['api_token'];
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

    /**
     * Edits an existing module row (server account). Validates input and returns meta data.
     *
     * @param stdClass $module_row The module row to edit
     * @param array &$vars An array of input data including:
     *  - account_name (string) The account name
     *  - api_token (string) The API token
     * @return array An array of meta fields to be stored for the module row
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['account_name', 'api_token', 'api_base_url'];
        $encrypted_fields = ['api_token'];
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

    /**
     * Returns the validation rules for adding/editing a module row.
     *
     * @param array &$vars An array of input data
     * @return array An array of input validation rules
     */
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
            'api_token' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Upcloudvps.!error.api_token_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['api_base_url'],
                    ],
                    'message' => Language::_('Upcloudvps.!error.api_token_valid_connection', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates the connection to the UpCloud API using provided credentials.
     *
     * @param string $api_token The UpCloud API token
     * @param string $api_base_url The UpCloud API base URL
     * @return bool True if the connection is successful, false otherwise
     */
    public function validateConnection($api_token, $api_base_url)
    {
        try {
            $api = $this->getApi($api_token, $api_base_url);
            $result = $api->GetAccountInfo();
            $this->log('upcloud|accountRequest', serialize($result), 'input', true);
            if ($result['response_code'] == '200') {
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * Initializes and returns an instance of the UpcloudvpsApi.
     *
     * @param string $api_token The UpCloud API token
     * @param string $api_base_url The UpCloud API base URL (optional)
     * @return UpcloudvpsApi An instance of the UpCloud API wrapper
     */
    private function getApi($api_token, $api_base_url = null)
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
            'baseUrl' => empty($api_base_url) ? 'https://api.upcloud.com/1.3/' : $api_base_url,
            'apiToken' => $api_token,
            'blestaVer' => $blestaVer,
            'moduleVer' => $this->config->version,
        ];
        $api = new UpcloudvpsApi($params);
        return $api;
    }

    /**
     * Fetches available server plans from the UpCloud API for a given module row.
     *
     * @param stdClass $module_row The module row containing API credentials
     * @return array An array of server plans [plan_name => display_name]
     */
    private function getServerPlans($module_row)
    {
        $api = $this->getApi($module_row->meta->api_token, $module_row->meta->api_base_url);
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

    /**
     * Fetches available OS templates from the UpCloud API for a given module row.
     * Optionally filters templates based on package settings.
     *
     * @param stdClass $module_row The module row containing API credentials
     * @param stdClass|null $package The package object (optional)
     * @return array An array of templates [template_uuid => template_title]
     */
    private function getTemplates($module_row, $package = null)
    {
        $api = $this->getApi($module_row->meta->api_token, $module_row->meta->api_base_url);
        $result_os = $api->GetTemplate()['response']['storages']['storage'];
        //$this->log('upcloud|GetTemplates', serialize($result_os), 'input', true);
        $templates = [];
        if ($package) {
            foreach ($result_os as $os) {
                if (strpos($os['title'], 'Windows') !== false || strpos($os['title'], 'UpCloud') !== false) {
                    continue;
                }
                $templates[$os['uuid']] = $os['title'];
            }
        } else {
            foreach ($result_os as $os) {
                $templates[$os['uuid']] = $os['title'];
            }
        }

        return $templates;
    }

    /**
     * Fetches available server locations (zones) from the UpCloud API for a given module row.
     *
     * @param stdClass $module_row The module row containing API credentials
     * @return array An array of locations [zone_id => zone_description]
     */
    private function getLocations($module_row)
    {
        $api = $this->getApi($module_row->meta->api_token, $module_row->meta->api_base_url);
        $zones = $api->GetZones()['response']['zones']['zone'];
        // $this->log('upcloud|getLocations', serialize($zones), 'input', true);
        $zoneLocation = [];
        foreach ($zones as $zone) {
            $zoneLocation[$zone['id']] = $zone['description'];
        }
        return $zoneLocation;
    }

    /**
     * Returns the fields used for configuring a package. Populates options from the API.
     *
     * @param stdClass|null $vars An object containing package meta data (optional)
     * @return ModuleFields A ModuleFields object containing the package fields
     */
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

    /**
     * Returns the validation rules for adding/editing a package.
     *
     * @param array &$vars An array of package meta data
     * @return array An array of input validation rules
     */
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

    /**
     * Processes and formats package meta data for saving.
     * Called when adding a package.
     *
     * @param array|null $vars An array of package meta data
     * @return array An array of formatted meta data for storage
     */
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

    /**
     * Processes and formats package meta data for saving.
     * Called when editing a package. Reuses addPackage logic.
     *
     * @param stdClass $package The package being edited
     * @param array|null $vars An array of package meta data
     * @return array An array of formatted meta data for storage
     */
    public function editPackage($package, array $vars = null)
    {
        return $this->addPackage($vars);
    }

    /**
     * Suspends a service by stopping the server via the API.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service to suspend
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @return null|string A language string describing success/failure, or null on error
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'StopServer', 'upcloud|suspend');
    }

    /**
     * Unsuspends a service by starting the server via the API.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service to unsuspend
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @return null|string A language string describing success/failure, or null on error
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'StartServer', 'upcloud|unsuspend');
    }

    /**
     * Cancels a service by deleting the server and its storage via the API.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service to cancel
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @return null|string A language string describing success/failure, or null on error
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->performServiceAction($service, 'DeleteServerAndStorage', 'upcloud|cancel', '204');
    }

    /**
     * Performs a generic service action (start, stop, delete) via the API.
     * Logs the action and sets errors if the API call fails.
     *
     * @param stdClass $service The service object
     * @param string $actionName The API method name to call (e.g., 'StopServer')
     * @param string $logTag The tag to use for logging
     * @param string|null $expectedResponseCode The expected HTTP response code for success (optional)
     * @return null Returns null on completion (errors are set via Input component)
     */
    private function performServiceAction($service, $actionName, $logTag, $expectedResponseCode = null)
    {
        $module_row = $this->getModuleRow();
        if ($module_row) {
            $api = $this->getApi($module_row->meta->api_token, $module_row->meta->api_base_url);
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


    /**
     * Converts an array of service field objects into a single stdClass object.
     *
     * @param array $options An array of service field objects (usually from $service->fields)
     * @return stdClass An object with service field names as keys and values
     */
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

    /**
     * Extracts and formats service fields from input variables based on package settings.
     *
     * @param array $vars An array of input data (typically from service add/edit form)
     * @param stdClass $package The package associated with the service
     * @return array An array of formatted service parameters for API calls
     */
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

    /**
     * Validates service data before adding a service.
     *
     * @param stdClass $package The package being added to
     * @param array|null $vars An array of input service data
     * @return bool True if validation passes, false otherwise (errors set via Input component)
     */
    public function validateService($package, array $vars = null)
    {
        $rules = $this->getServiceRules($vars, $package, false);
        if (isset($package->meta->set_template) && $package->meta->set_template == 'admin') {
            unset($rules['upcloudvps_template']);
        }
        $this->Input->setRules($rules);
        return $this->Input->validates($vars);
    }

    /**
     * Returns the validation rules for adding or editing a service.
     *
     * @param array|null $vars An array of input service data
     * @param stdClass|null $package The package associated with the service
     * @param bool $edit True if editing an existing service, false otherwise
     * @return array An array of input validation rules
     */
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

    /**
     * Validates a hostname using Blesta's Server validator.
     *
     * @param string $host_name The hostname to validate
     * @return bool True if the hostname is a valid domain or IP, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Validates if a given location ID exists in the available locations from the API.
     *
     * @param string $location The location ID to validate
     * @return bool True if the location is valid, false otherwise
     */
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

    /**
     * Validates if a given template UUID exists in the available templates from the API.
     *
     * @param string $template The template UUID to validate
     * @return bool True if the template is valid, false otherwise
     */
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


    /**
     * Adds a new service. Creates the server via the API if 'use_module' is true.
     *
     * @param stdClass $package The package to add the service to
     * @param array|null $vars An array of input service data
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @param string $status The initial status of the service (default: 'pending')
     * @return array|null An array of service fields to save, or null on error
     */
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
                $api = $this->getApi($row->meta->api_token, $row->meta->api_base_url);
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
                    if ($IPList['family'] == "IPv4" && ($IPList['part_of_plan'])) {
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

    /**
     * Validates service data before editing a service.
     * Skips validation for fields that haven't changed.
     *
     * @param stdClass $service The service being edited
     * @param array|null $vars An array of input service data
     * @return bool True if validation passes, false otherwise (errors set via Input component)
     */
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


    /**
     * Edits an existing service. Updates fields locally, API interaction might be needed for some changes (currently minimal).
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service being edited
     * @param array|null $vars An array of input service data
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @return array|null An array of service fields to save, or null on error
     */
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
            $api = $this->getApi($row->meta->api_token, $row->meta->api_base_url);
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

    /**
     * Returns the fields for adding a service in the admin area.
     * Populates options (locations, templates) from the API.
     *
     * @param stdClass $package The package the service will belong to
     * @param stdClass|null $vars An object containing pre-filled data (optional)
     * @return ModuleFields A ModuleFields object containing the service fields
     */
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

    /**
     * Returns the fields for editing a service in the admin area.
     * Populates template options from the API if applicable.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass|null $vars An object containing the current service data (optional)
     * @return ModuleFields A ModuleFields object containing the service fields
     */
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

    /**
     * Returns the fields for adding a service in the client area.
     * Populates options (locations, templates) from the API.
     *
     * @param stdClass $package The package the service will belong to
     * @param stdClass|null $vars An object containing pre-filled data (optional)
     * @return ModuleFields A ModuleFields object containing the service fields
     */
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

    /**
     * Fetches and renders service information for display in admin or client area.
     * Retrieves server details from the UpCloud API.
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package the service belongs to
     * @param bool $client True if rendering for the client area, false for admin
     * @return string The rendered service information view
     */
    private function getServiceInfo($service, $package, $client = false)
    {
        $row = $this->getModuleRow();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $api = $this->getApi($row->meta->api_token, $row->meta->api_base_url);
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

    /**
     * Returns the rendered service information view for the admin area.
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package the service belongs to
     * @return string The rendered view
     */
    public function getAdminServiceInfo($service, $package)
    {
        return $this->getServiceInfo($service, $package);
    }

    /**
     * Returns the rendered service information view for the client area.
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package the service belongs to
     * @return string The rendered view
     */
    public function getClientServiceInfo($service, $package)
    {
        return $this->getServiceInfo($service, $package, true);
    }


    /**
     * Returns the tabs to display on the admin service management page.
     *
     * @param stdClass $package The package the service belongs to
     * @return array An array of tabs [tab_key => tab_name]
     */
    public function getAdminTabs($package)
    {
        return ['tabActions' => Language::_('Upcloudvps.tab_actions', true),];
    }

    /**
     * Returns the tabs to display on the client service management page.
     *
     * @param stdClass $package The package the service belongs to
     * @return array An array of tabs [tab_key => tab_name]
     */
    public function getClientTabs($package)
    {
        return ['tabClientActions' => Language::_('Upcloudvps.tab_client_actions', true),];
    }

    /**
     * Renders the content for the 'Actions' tab in the admin service management area.
     * Handles POST requests for actions like restart, stop, etc.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service object
     * @param array|null $get GET request data
     * @param array|null $post POST request data
     * @param array|null $files FILES request data
     * @return string The rendered tab content
     */
    public function tabActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getTabActions($package, $service, $post);
    }

    /**
     * Renders the content for the 'Actions' tab in the client service management area.
     * Handles POST requests for actions like restart, stop, etc.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service object
     * @param array|null $get GET request data
     * @param array|null $post POST request data
     * @param array|null $files FILES request data
     * @return string The rendered tab content
     */
    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        return $this->getTabActions($package, $service, $post, true);
    }

    /**
     * Fetches data and renders the content for the service actions tab (admin or client).
     * Handles POST actions by calling the API.
     *
     * @param stdClass $package The package the service belongs to
     * @param stdClass $service The service object
     * @param array|null $post POST request data containing the action to perform
     * @param bool $client True if rendering for the client area, false for admin
     * @return string The rendered tab view
     */
    private function getTabActions($package, $service, array $post = null, $client = false)
    {
        $row = $this->getModuleRow();
        $this->view = new View($client ? 'tab_client_actions' : 'tab_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Services']);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $templates = $this->getTemplates($row, $package);
        $api = $this->getApi($row->meta->api_token, $row->meta->api_base_url);
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
                    $ipaddress[$ip['address']] = $ip['address'];
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
                $TotalTraffic = $Plan['public_traffic_out'];
                $Outgoing = $api->formatSizeBytestoGB($server_details['plan_ipv4_bytes'] + $server_details['plan_ipv6_bytes']);
                $Percentage = round((($Outgoing / $TotalTraffic) * 100), 2);
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

    /**
     * Generates a random password string.
     *
     * @return string The generated password
     */
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

    /**
     * Handles changing a service's package.
     * If the server plan differs, it calls the API to modify the server.
     *
     * @param stdClass $package_from The package the service is currently assigned to
     * @param stdClass $package_to The package the service is being changed to
     * @param stdClass $service The service being changed
     * @param stdClass|null $parent_package The parent package (if applicable)
     * @param stdClass|null $parent_service The parent service (if applicable)
     * @return null Returns null on completion (errors set via Input component if API call fails)
     */
    public function changeServicePackage($package_from, $package_to, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->api_token, $row->meta->api_base_url);
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
