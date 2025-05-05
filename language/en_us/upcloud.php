<?php

$lang['Upcloudvps.name'] = 'UpCloud VPS';
$lang['Upcloudvps.tab_actions'] = 'Actions';
$lang['Upcloudvps.tab_stats'] = 'Statistics';
$lang['Upcloudvps.tab_graphs'] = 'Bandwidth Graphs';
$lang['Upcloudvps.module_row'] = 'Account';
$lang['Upcloudvps.module_row_plural'] = 'Accounts';
$lang['Upcloudvps.tab_actions'] = 'Actions';
$lang['Upcloudvps.tab_stats'] = 'Statistics';
$lang['Upcloudvps.tab_snapshots'] = 'Snapshots';
$lang['Upcloudvps.tab_backups'] = 'Backups';
$lang['Upcloudvps.tab_client_actions'] = 'Actions';
$lang['Upcloudvps.tab_client_stats'] = 'Statistics';

// Add row
$lang['Upcloudvps.add_row.box_title'] = 'Add UpCloud Server';
$lang['Upcloudvps.add_row.basic_title'] = 'Basic Settings';
$lang['Upcloudvps.add_row.add_btn'] = 'Add Server';
$lang['Upcloudvps.edit_row.box_title'] = 'Edit UpCloud Server';
$lang['Upcloudvps.edit_row.basic_title'] = 'Basic Settings';
$lang['Upcloudvps.edit_row.edit_btn'] = 'Edit Server';
$lang['Upcloudvps.row_meta.account_name'] = 'Account Name';
$lang['Upcloudvps.row_meta.api_token'] = 'API Token';
$lang['Upcloudvps.module_row'] = 'Account';
$lang['Upcloudvps.module_row_plural'] = 'Accounts';
$lang['Upcloudvps.description'] = 'The UpCloud module make UpCloud VPS management simple and intuitive. Common tasks such as a ordering VPS, Start, Stop, Reboot, Change Password, View Data Usage are only a few clicks away.';
$lang['Upcloudvps.row_meta.use_log'] = "Enable module debugging in case module does not work";

// Module management
$lang['Upcloudvps.add_module_row'] = 'Add Account';
$lang['Upcloudvps.manage.module_rows_title'] = 'Accounts';
$lang['Upcloudvps.manage.module_rows_heading.name'] = 'Account';
$lang['Upcloudvps.manage.module_rows_heading.options'] = 'Options';
$lang['Upcloudvps.manage.module_rows.edit'] = 'Edit';
$lang['Upcloudvps.manage.module_rows.delete'] = 'Delete';
$lang['Upcloudvps.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this account?';
$lang['Upcloudvps.manage.module_rows_no_results'] = 'There are no accounts.';

//package
$lang['Upcloudvps.package.cpu'] = 'CPU';
$lang['Upcloudvps.package.core'] = 'Core';
$lang['Upcloudvps.package.memory'] = 'Memory';
$lang['Upcloudvps.package.MB'] = 'MB';
$lang['Upcloudvps.package.GB'] = 'GB';
$lang['Upcloudvps.package.disk'] = 'Disk';

// Errors
$lang['Upcloudvps.!error.account_name_valid'] = 'Please enter a valid Account Name.';
$lang['Upcloudvps.!error.api_token_valid'] = 'The API Token appears to be invalid.';
$lang['Upcloudvps.!error.license_key_valid'] = 'The License Key appears to be invalid.';
$lang['Upcloudvps.!error.api_token_valid_connection'] = 'A connection to the server could not be established. Please check to ensure that the API details are correct.';
$lang['Upcloudvps.!error.meta[server_plan].format'] = 'Please select a valid VPS Plan.';
$lang['Upcloudvps.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';
$lang['Upcloudvps.!error.upcloudvps_hostname.format'] = 'Please enter a valid hostname, e.g. domain.com.';
$lang['Upcloudvps.!error.upcloudvps_location.valid'] = 'Please select a valid location.';
$lang['Upcloudvps.!error.upcloudvps_template.valid'] = 'Please select a valid template.';
$lang['Upcloudvps.!error.upcloudvps_vmid.valid'] = 'Please provide a valid vm id.';
$lang['Upcloudvps.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request.';
$lang['Upcloudvps.!error.api.server_locked'] = 'Unable to complete action.  Server is currently locked.';

// Package fields
$lang['Upcloudvps.package_fields.server_type'] = 'Server Type';
$lang['Upcloudvps.package_fields.server_type.baremetal'] = 'Bare Metal';
$lang['Upcloudvps.package_fields.server_type.server'] = 'Virtual Machine';
$lang['Upcloudvps.package_fields.baremetal_plan'] = 'Bare Metal Plan';
$lang['Upcloudvps.package_fields.server_plan'] = 'Server Plan';
$lang['Upcloudvps.package_fields.set_template'] = 'Set Template';
$lang['Upcloudvps.package_fields.admin_set_template'] = 'Select a template';
$lang['Upcloudvps.package_fields.client_set_template'] = 'Let client set template';
$lang['Upcloudvps.package_fields.template'] = 'Template';
$lang['Upcloudvps.package_fields.surcharge_templates'] = 'Surcharge Templates';
$lang['Upcloudvps.package_fields.allow_surcharge_templates'] = 'Allow Surcharge Templates';
$lang['Upcloudvps.package_fields.disallow_surcharge_templates'] = 'Disallow Surcharge Templates';


// Service fields
$lang['Upcloudvps.service_field.vmid'] = 'Upcloudvps VmId';
$lang['Upcloudvps.service_field.hostname'] = 'Hostname';
$lang['Upcloudvps.service_field.location'] = 'Location';
$lang['Upcloudvps.service_field.template'] = 'Template';
$lang['Upcloudvps.service_field.ipv6'] = 'IPv6 Networking';
$lang['Upcloudvps.service_field.enable_vnc'] = 'Enable VNC';
$lang['Upcloudvps.service_field.disable_vnc'] = 'Disable VNC';

// Tooltips
$lang['Upcloudvps.service_field.tooltip.vmid'] = 'The unique identifier for this subscription.';

// Service info
$lang['Upcloudvps.service_info.hostname'] = 'Hostname';
$lang['Upcloudvps.service_info.os'] = 'Operating System';
$lang['Upcloudvps.service_info.location'] = 'Location';
$lang['Upcloudvps.service_info.main_ip'] = 'Main IP';
$lang['Upcloudvps.service_info.main_ip6'] = 'IPv6 Address';
$lang['Upcloudvps.service_info.default_password'] = 'Password';
$lang['Upcloudvps.service_info.state'] = 'State';


// Service management
$lang['Upcloudvps.tab_actions.server_locked'] = 'An Error occurred, please stay connected.';
$lang['Upcloudvps.tab_actions.status_title'] = 'Server Status';
$lang['Upcloudvps.tab_actions.server_title'] = 'Server Actions';
$lang['Upcloudvps.tab_actions.action_restart'] = 'Restart';
$lang['Upcloudvps.tab_actions.action_stop'] = 'Stop';
$lang['Upcloudvps.tab_actions.action_start'] = 'Start';
$lang['Upcloudvps.tab_actions.action_reinstall_template'] = 'Reinstall VPS';
$lang['Upcloudvps.tab_actions.heading_change_template'] = 'Change Operating System';
$lang['Upcloudvps.tab_actions.action_enable_ipv6'] = 'Enable IPv6';
$lang['Upcloudvps.tab_actions.field_template'] = 'Template';
$lang['Upcloudvps.tab_actions.field_change_template_submit'] = 'Change &amp; Rebuild VPS';
$lang['Upcloudvps.tab_actions.action_changepassword'] = 'Change VNC Password';
$lang['Upcloudvps.tab_actions.action_change_ptr'] = 'Change PTR';
$lang['Upcloudvps.tab_actions.heading_change_ptr'] = 'Change IP PTR Value';
$lang['Upcloudvps.tab_actions.field_ptr'] = 'PTR Value';
$lang['Upcloudvps.tab_actions.rebootMsg'] = 'Once you have changed your hostname, reboot the VPS to make it effective';

// Client actions
$lang['Upcloudvps.tab_client_actions.heading_status'] = 'Server Status';
$lang['Upcloudvps.tab_client_actions.status_online'] = 'Online';
$lang['Upcloudvps.tab_client_actions.status_offline'] = 'Offline';
$lang['Upcloudvps.tab_client_actions.status_locked'] = 'Locked';
$lang['Upcloudvps.tab_client_actions.heading_actions'] = 'Actions';
$lang['Upcloudvps.tab_client_actions.action_restart'] = 'Restart';
$lang['Upcloudvps.tab_client_actions.action_stop'] = 'Stop';
$lang['Upcloudvps.tab_client_actions.action_start'] = 'Start';
$lang['Upcloudvps.tab_client_actions.action_reinstall_template'] = 'Reinstall VPS';
$lang['Upcloudvps.tab_client_actions.action_kvm_console'] = 'KVM Console';
$lang['Upcloudvps.tab_client_actions.heading_change_template'] = 'Change Operating System';
$lang['Upcloudvps.tab_client_actions.field_template'] = 'Template';
$lang['Upcloudvps.tab_client_actions.field_change_template_submit'] = 'Change &amp; Rebuild VPS';

$lang['Upcloudvps.tab_client_actions.action_change_ptr'] = 'Change PTR';
$lang['Upcloudvps.tab_client_actions.heading_change_ptr'] = 'Change IP PTR Value';
$lang['Upcloudvps.tab_client_actions.field_ptr'] = 'PTR Value';

// Client statistics
$lang['Upcloudvps.stats.server_information'] = 'Server Information';
$lang['Upcloudvps.stats.info_heading.field'] = 'Field';
$lang['Upcloudvps.stats.info_heading.value'] = 'Value';
$lang['Upcloudvps.stats.info.ipaddv4'] = 'IPv4 Address';
$lang['Upcloudvps.stats.info.hostname'] = 'Hostname';
$lang['Upcloudvps.stats.info.osname'] = 'Operating System';
$lang['Upcloudvps.stats.info.memory_amount'] = 'Server RAM (MB)';
$lang['Upcloudvps.stats.info.space_gb'] = 'Server Disk (GB)';
$lang['Upcloudvps.stats.info.bandwidth_gb'] = 'Allowed Bandwidth (in GB)';
$lang['Upcloudvps.stats.info.bandwidth_speed'] = 'Uplink (Mbps)';
$lang['Upcloudvps.stats.info.core_number'] = 'Virtual CPUs (Cores)';
$lang['Upcloudvps.stats.info.user'] = 'Username';
$lang['Upcloudvps.stats.info.zoneDescription'] = 'Location';
$lang['Upcloudvps.stats.info.rootpassword'] = 'Default Password';
$lang['Upcloudvps.stats.info.remote_access_host'] = 'VNC IP Address';
$lang['Upcloudvps.stats.info.remote_access_port'] = 'VNC Port';
$lang['Upcloudvps.stats.info.remote_access_password'] = 'VNC Password';
$lang['Upcloudvps.stats.interface_information'] = 'Interface Information';
$lang['Upcloudvps.stats.mac'] = 'MAC Address';
$lang['Upcloudvps.stats.gateway'] = 'Gateway';
$lang['Upcloudvps.stats.type'] = 'Type';
$lang['Upcloudvps.stats.ip'] = 'IP Address';
$lang['Upcloudvps.stats.ptr'] = 'PTR';
$lang['Upcloudvps.stats.info.ipaddv6'] = 'IPv6 Address';
$lang['Upcloudvps.stats.timeInfo'] = 'These are all the actions made for this VPS before your last page load and time is based on GMT +1 timezone';

//Graphs
$lang['Upcloudvps.stats.info.Bandwidth'] = 'Bandwidth Usage';
$lang['Upcloudvps.stats.info.percentage'] = 'Bandwidth Percentage';
$lang['Upcloudvps.graphs.download'] = 'Download';
$lang['Upcloudvps.graphs.upload'] = 'Upload';
$lang['Upcloudvps.stats.info.limit'] = 'Bandwidth Limit';
$lang['Upcloudvps.graphs.GB'] = 'GB';
$lang['Upcloudvps.stats.info.used'] = 'Bandwidth Used';
$lang['Upcloudvps.stats.info.Left'] = 'Left';
