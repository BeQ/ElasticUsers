<?php

return [
	'Admin'   => [
		'HostAddressNotFound'         => 'server address (IP or hostname) has not been set for this server',
		'CommonSettings'              => 'common product settings',
		'ServersNone'                 => 'There are no servers associated with module',
		'Server'                      => 'Server',
		'ServerDescription'           => 'make this server the active default for new signups',
		'BillingPlan'                 => 'Billing plan',
		'BillingPlanDescription'      => [
			'Default'   => 'billing plan for newly created users',
			'Suspended' => 'billing plan for suspended users',
			'Trial'     => 'billing plan for trial users',
		],
		'TimeZone'                    => 'Timezone',
		'TimeZoneDescription'         => 'timezone for newly created users',
		'Locale'                      => 'Locale',
		'LocaleDescription'           => 'locale for newly created users',
		'Roles'                       => 'Roles',
		'RolesDescription'            => 'user roles for newly created users',
		'RoleNone'                    => 'There are no configured roles on this server',
		'UserGroups'                  => 'Groups',
		'UserGroupsDescription'       => 'user groups for newly created users',
		'UserGroupsNone'              => 'There are no configured user group on this server',
		'SuspendDays'                 => 'Suspend days',
		'SuspendDaysDescription'      => 'the number of days after the due payment date you want to wait before suspending the account (for postpaid billing type)',
		'TerminateDays'               => 'Terminate days',
		'TerminateDaysDescription'    => 'the number of days after the due payment date you want to wait before terminating the account',
		'DueDays'                     => 'Due date days',
		'DueDaysDescription'          => 'set invoice due payment date to the number of days after invoice creation, 0 is immediate',
		'TrialDays'                   => 'Trial period',
		'TrialDaysDescription'        => 'the number of days you want to wait before terminating the trial account, 0 is trial disabled',
		'BillingType'                 => 'Billing type',
		'BillingTypeDescription'      => 'how to bill users',
		'BillingTypeVariants'         => [
			0 => 'Postpaid',
			1 => 'Prepaid',
		],
		'ShowEmptyRecords'            => 'show empty records',
		'ShowEmptyRecordsDescription' => 'show empty statistic records in client area',
		'YesNo'                       => [
			0 => 'Yes',
			1 => 'No',
		],
		'RefreshServerData'           => 'Reset server data cache',
		'Account_Created'             => 'OnApp account has been created',
		'Account_Suspended'           => 'OnApp account has been suspended',
		'Account_Unsuspended'         => 'OnApp account has been unsuspended',
		'Account_Terminated'          => 'OnApp account has been unsuspended',
		'LoginToCP'                   => 'Open Control Panel',
		'ErrorTitle'                  => 'Something Went Wrong!',
		'Error_WrapperNotFound'       => 'OnApp PHP wrapper not found. Please download it from https://github.com/OnApp/OnApp-PHP-Wrapper-External/releases and put into',
		'Error_CreateUser'            => 'Cannot create OnApp user',
		'Error_SuspendUser'           => 'Cannot suspend OnApp user',
		'Error_TerminateUser'         => 'Cannot terminate OnApp user',
		'Error_UnsuspendUser'         => 'Cannot unsuspend OnApp user',
		'Error_ChangeBillingPlan'     => 'Cannot change billingß plan',
		'Error_UserNotFound'          => 'Cannot find OnApp user for client #%s on server #%s',
		'PasswordNotSet'              => 'Password not set',
	],
	'Client'  => [
		'GenerateNewPassword'  => 'Generate new service password',
		'GeneralIssue'         => 'Something went wrong',
		'ManageMyCloud'        => 'Manage My Cloud',
		'OpenCP'               => 'Open control panel',
		'ConvertTrial'         => 'Convert trial to regular account',
		'OutstandingDetails'   => 'Outstanding Details',
		'StartDate'            => 'Start date',
		'EndDate'              => 'End date',
		'Apply'                => 'Apply',
		'Loading'              => 'Loading...',
		'Processing'           => 'Processing...',
		'AJAXError'            => 'There is no data for given period or something went wrong.',
		'PasswordNotSet'       => 'Password not set',
		'PasswordSet'          => 'OnApp account password has been generated',
		'TrialConverted'       => 'Trial converted',
		'Error_ConvertTrial'   => 'Cannot convert trial plan',
		'VMCost'               => 'Virtual Servers Cost',
		'BackupsCost'          => 'Backups Cost',
		'MonitCost'            => 'Autoscaling monitor Fee',
		'StorageCost'          => 'Storage Disks Size Cost',
		'TemplatesCost'        => 'Templates Cost',
		'BackupCountCost'      => 'Backup Zones Backups Cost',
		'BackupDiskCost'       => 'Backup Disk Size Cost',
		'TemplateCountCost'    => 'Backup Zones Templates Cost',
		'TemplateDiskSizeCost' => 'Backup Zones Template Disk Cost',
		'CustomerNetworkCost'  => 'Customer Network Cost',
		'EdgeGroupCost'        => 'CDN Edge Group Cost',
		'TotalCost'            => 'Total Cost',
	],
	'Invoice' => [
		'DateFormat'              => 'Y-m-d H:i',
		'Product'                 => 'Product: ',
		'Period'                  => 'Period: ',
		'vm_cost'                 => 'Virtual Servers Cost',
		'backup_cost'             => 'Backups Cost',
		'monit_cost'              => 'Autoscaling monitor Fee',
		'storage_disk_size_cost'  => 'Storage Disks Size Cost',
		'template_cost'           => 'Templates Cost',
		'backup_count_cost'       => 'Backup Zones Backups Cost',
		'backup_disk_size_cost'   => 'Backup Disk Size Cost',
		'template_count_cost'     => 'Backup Zones Templates Cost',
		'template_disk_size_cost' => 'Backup Zones Template Disk Cost',
		'customer_network_cost'   => 'Customer Network Cost',
		'edge_group_cost'         => 'CDN Edge Group Cost',
		'total_cost'              => 'Total Cost',
	],
];