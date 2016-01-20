<?php

global $whmcs;

$configModuleVersion = $whmcs->get_config( 'OnAppElasticUsersVersion' );
if( empty( $configModuleVersion ) || ( OnAppElasticUsersModule::MODULE_VERSION > $configModuleVersion ) ) {
	require __DIR__ . '/includes/php/setup/setup.php';
}

if( ! defined( 'ONAPP_WRAPPER_INIT' ) ) {
	define( 'ONAPP_WRAPPER_INIT', ROOTDIR . '/includes/wrapper/OnAppInit.php' );
}

if( file_exists( ONAPP_WRAPPER_INIT ) ) {
	require_once ONAPP_WRAPPER_INIT;
}

function OnAppElasticUsers_ConfigOptions() {
	$data         = new stdClass;
	$data->errors = $data->warnings = new stdClass;
	$module       = new OnAppElasticUsersModule;
	$data->lang   = $module->loadLang()->Admin;

	if( ! file_exists( ONAPP_WRAPPER_INIT ) ) {
		$data->error = $data->lang->WrapperNotFound . ' ' . ROOTDIR . '/includes/wrapper';
		goto end;
	}

	$serverGroup = isset( $_GET[ 'servergroup' ] ) ? $_GET[ 'servergroup' ] : (int)$GLOBALS[ 'servergroup' ];
	$sql = 'SELECT
                srv.`id`,
                srv.`name`,
                srv.`ipaddress`,
                srv.`secure`,
                srv.`hostname`,
                srv.`username`,
                srv.`password`
            FROM
                `tblservers` AS srv
            LEFT JOIN
                `tblservergroupsrel` AS rel ON srv.`id` = rel.`serverid`
            LEFT JOIN
                `tblservergroups` AS grp ON grp.`id` = rel.`groupid`
            WHERE
                grp.`id` = :servergroup
                AND srv.`type` = ":moduleName"
                AND srv.`disabled` = 0';
	$sql = str_replace( ':servergroup', $serverGroup, $sql );
	$sql = str_replace( ':moduleName', OnAppElasticUsersModule::MODULE_NAME, $sql );

	$res = full_query( $sql );
	if( mysql_num_rows( $res ) ) {
		$data->servers = new stdClass;
		while( $serverConfig = mysql_fetch_object( $res ) ) {
			# error if server IP or hostname are not set
			if( empty( $serverConfig->ipaddress ) && empty( $serverConfig->hostname ) ) {
				$data->error .= $serverConfig->name . ': ' . $data->lang->HostAddressNotFound . PHP_EOL;
				continue;
			}
			$serverConfig->password = decrypt( $serverConfig->password );

			$module                                   = new OnAppElasticUsersModule( $serverConfig );
			$data->servers->{$serverConfig->id}       = null;
			$data->servers->{$serverConfig->id}       = $module->getData();
			$data->servers->{$serverConfig->id}->Name = $serverConfig->name;
		}

		if( $data->servers ) {
			# get additional data
			# timezones
			$data->TimeZones = file_get_contents( __DIR__ . '/includes/php/tzs.json' );
			$data->TimeZones = json_decode( $data->TimeZones );
			$data->productOptions  = $GLOBALS[ 'packageconfigoption' ] ? : [ ];
			if( ! empty( $data->productOptions[ 1 ] ) ) {
				$data->productSettings     = json_decode( $data->productOptions[ 24 ] )->{$data->productOptions[ 1 ]};
				$data->productSettingsJSON = htmlspecialchars( $GLOBALS[ 'packageconfigoption' ][ 24 ] );
			}

		}
	}
	else {
		$data->error = $data->lang->ServersNone;
	}

	end:
	return [
		'' => [
			'Description' => $module->buildHTML( $data )
		]
	];
}

function OnAppElasticUsers_CreateAccount( $params ) {
	$module = new OnAppElasticUsersModule( $params );
	$lang   = $module->loadLang()->Admin;
	if( ! file_exists( ONAPP_WRAPPER_INIT ) ) {
		return $lang->Error_WrapperNotFound . realpath( ROOTDIR ) . '/includes';
	}

	$clientsDetails = $params[ 'clientsdetails' ];
	$serviceID      = $params[ 'serviceid' ];
	$serverID       = $params[ 'serverid' ];
	$userName       = $params[ 'username' ] ? $params[ 'username' ] : $clientsDetails[ 'email' ];
	$password       = OnAppElasticUsersModule::generatePassword();
	$productSettings = json_decode( $params[ 'configoption24' ] )->$serverID;

	if( ! $password ) {
		return $lang->Error_CreateUser . ': ' . $lang->PasswordNotSet;
	}

	$module                      = new OnAppElasticUsersModule( $params );
	$OnAppUser                   = $module->getObject( 'OnApp_User' );
	$OnAppUser->_email           = $clientsDetails[ 'email' ];
	$OnAppUser->_password        = $OnAppUser->_password_confirmation = $password;
	$OnAppUser->_login           = $userName;
	$OnAppUser->_first_name      = $clientsDetails[ 'firstname' ];
	$OnAppUser->_last_name       = $clientsDetails[ 'lastname' ];
	$OnAppUser->_billing_plan_id = $productSettings->BillingPlanDefault;
	$OnAppUser->_role_ids        = $productSettings->Roles;
	$OnAppUser->_time_zone       = $productSettings->TimeZone;
	$OnAppUser->_user_group_id   = $productSettings->UserGroups;
	$OnAppUser->_locale          = $productSettings->Locale;

	# trial user
	$isTrial = false;
	echo '<pre style="text-align: left;">';
	print_r( $productSettings );
	exit( PHP_EOL . ' die at ' . __LINE__ . ' in ' . __FILE__ );
	if( $productSettings->TrialDays > 0 ) {
		if( $params[ 'status' ] == 'Active' ) {
			$OnAppUser->_billing_plan_id = $productSettings->BillingPlanTrial;
			$isTrial                     = true;
		}
	}

	$OnAppUser->save();
	if( ! is_null( $OnAppUser->getErrorsAsArray() ) ) {
		$errorMsg = $lang->Error_CreateUser . ': ';
		$errorMsg .= $OnAppUser->getErrorsAsString( ', ' );

		return $errorMsg;
	}

	if( ! is_null( $OnAppUser->_obj->getErrorsAsArray() ) ) {
		$errorMsg = $lang->Error_CreateUser . ': ';
		$errorMsg .= $OnAppUser->_obj->getErrorsAsString( ', ' );

		return $errorMsg;
	}

	if( is_null( $OnAppUser->_obj->_id ) ) {
		return $lang->Error_CreateUser;
	}

	// Save user link in whmcs db
	insert_query( 'OnAppElasticUsers', array(
		'serviceID'     => $params[ 'serviceid' ],
		'WHMCSUserID'   => $params[ 'userid' ],
		'OnAppUserID'   => $OnAppUser->_obj->_id,
		'serverID'      => $params[ 'serverid' ],
		'billingPlanID' => $OnAppUser->_billing_plan_id,
		'billingType'   => $productSettings->BillingType,
		'isTrial'       => $isTrial,
	) );

	// Save OnApp login and password
	full_query(
		"UPDATE
				tblhosting
			SET
				password = '" . encrypt( $password ) . "',
				username = '$userName'
			WHERE
				id = '$serviceID'"
	);
	// todo use placeholders

	sendmessage( 'OnApp account has been created', $serviceID );

	return 'success';
}

function OnAppElasticUsers_TerminateAccount( $params ) {
	$module = new OnAppElasticUsersModule( $params );
	$lang   = $module->loadLang()->Admin;
	if( ! file_exists( ONAPP_WRAPPER_INIT ) ) {
		return $lang->Error_WrapperNotFound . realpath( ROOTDIR ) . '/includes';
	}

	$serviceID = $params[ 'serviceid' ];
	$clientID  = $params[ 'clientsdetails' ][ 'userid' ];
	$serverID  = $params[ 'serverid' ];

	$query = "SELECT
					`OnAppUserID`
				FROM
					`OnAppElasticUsers`
				WHERE
					serverID = $serverID
					-- AND client_id = $clientID
					AND serviceID = $serviceID";
	// todo use placeholders

	$result = full_query( $query );
	if( $result ) {
		$OnAppUserID = mysql_result( $result, 0 );
	}
	if( ! $OnAppUserID ) {
		return sprintf( $lang->Error_UserNotFound, $clientID, $serverID );
	}

	$module    = new OnAppElasticUsersModule( $params );
	$OnAppUser = $module->getObject( 'OnApp_User' );
	$vms       = $module->getObject( 'OnApp_VirtualMachine' );
	if( $vms->getList( $OnAppUserID ) ) {
		return $lang->Error_TerminateUser;
	}

	$OnAppUser->_id = $OnAppUserID;
	$OnAppUser->delete( true );

	if( ! empty( $OnAppUser->error ) ) {
		$errorMsg = $lang->Error_TerminateUser . ': ';
		$errorMsg .= $OnAppUser->getErrorsAsString( ', ' );

		return $errorMsg;
	}
	else {
		$query = 'DELETE FROM
						`OnAppElasticUsers`
					WHERE
						serviceID = ' . (int)$serviceID . '
						-- AND client_id = ' . (int)$clientID . '
						AND serverID = ' . (int)$serverID;
		full_query( $query );
	}

	sendmessage( 'OnApp account has been terminated', $serviceID );

	return 'success';
}

function OnAppElasticUsers_SuspendAccount( $params ) {
	$module = new OnAppElasticUsersModule( $params );
	$lang   = $module->loadLang()->Admin;
	if( ! file_exists( ONAPP_WRAPPER_INIT ) ) {
		return $lang->Error_WrapperNotFound . realpath( ROOTDIR ) . '/includes';
	}

	$serverID       = $params[ 'serverid' ];
	$clientID       = $params[ 'clientsdetails' ][ 'userid' ];
	$serviceID      = $params[ 'serviceid' ];
	$productSettings = json_decode( $params[ 'configoption24' ] )->$serverID;

	$query = "SELECT
					`OnAppUserID`
				FROM
					`OnAppElasticUsers`
				WHERE
					serverID = $serverID
					-- AND client_id = $clientID
					AND serviceID = $serviceID";
	// todo use placeholders

	$result = full_query( $query );
	if( $result ) {
		$OnAppUserID = mysql_result( $result, 0 );
	}
	if( ! $OnAppUserID ) {
		return sprintf( $lang->Error_UserNotFound, $clientID, $serverID );
	}

	$OnAppUser = $module->getObject( 'OnApp_User' );
	$OnAppUser->_id = $OnAppUserID;

	# change billing plan
	$unset = array( 'time_zone', 'user_group_id', 'locale' );
	$OnAppUser->unsetFields( $unset );
	$OnAppUser->_billing_plan_id = $productSettings->BillingPlanSuspended;
	$OnAppUser->save();

	$OnAppUser->suspend();
	if( ! is_null( $OnAppUser->error ) ) {
		$errorMsg = $lang->Error_SuspendUser . ':<br/>';
		$errorMsg .= $OnAppUser->getErrorsAsString( '<br/>' );

		return $errorMsg;
	}

	sendmessage( 'OnApp account has been suspended', $serviceID );

	return 'success';
}

function OnAppElasticUsers_UnsuspendAccount( $params ) {
	$module = new OnAppElasticUsersModule( $params );
	$lang   = $module->loadLang()->Admin;
	if( ! file_exists( ONAPP_WRAPPER_INIT ) ) {
		return $lang->Error_WrapperNotFound . realpath( ROOTDIR ) . '/includes';
	}

	$serverID       = $params[ 'serverid' ];
	$clientID       = $params[ 'clientsdetails' ][ 'userid' ];
	$serviceID      = $params[ 'serviceid' ];
	$productSettings = json_decode( $params[ 'configoption24' ] )->$serverID;

	$query = "SELECT
					`OnAppUserID`
				FROM
					`OnAppElasticUsers`
				WHERE
					serverID = $serverID
					-- AND client_id = $clientID
					AND serviceID = $serviceID";
	// todo use placeholders

	$result = full_query( $query );
	if( $result ) {
		$OnAppUserID = mysql_result( $result, 0 );
	}
	if( ! $OnAppUserID ) {
		return sprintf( $lang->Error_UserNotFound, $clientID, $serverID );
	}

	//$module = new OnAppElasticUsersModule( $params );
	$OnAppUser = $module->getObject( 'OnApp_User' );
	$unset     = array( 'time_zone', 'user_group_id', 'locale' );
	$OnAppUser->unsetFields( $unset );
	$OnAppUser->_id              = $OnAppUserID;
	$OnAppUser->_billing_plan_id = $productSettings->BillingPlanDefault;
	$OnAppUser->save();
	$OnAppUser->activate_user();

	if( ! is_null( $OnAppUser->error ) ) {
		$errorMsg = $lang->Error_UnsuspendUser . ':<br/>';
		$errorMsg .= $OnAppUser->getErrorsAsString( '<br/>' );

		return $errorMsg;
	}

	sendmessage( 'OnApp account has been unsuspended', $serviceID );

	return 'success';
}

function OnAppElasticUsers_ClientArea( $params = '' ) {
	if( isset( $_GET[ 'modop' ] ) && ( $_GET[ 'modop' ] == 'custom' ) ) {
		if( isset( $_GET[ 'a' ] ) ) {
			$functionName = 'OnAppElastic_Custom_' . $_GET[ 'a' ];
			if( function_exists( $functionName ) ) {
				$functionName( $params );
			}
			else {
				echo $functionName;
				exit( PHP_EOL . ' die at ' . __LINE__ . ' in ' . __FILE__ );
			}
		}
	}
	if( isset( $_GET[ 'getstat' ] ) ) {
		OnAppElasticUsers_OutstandingDetails( $params );
	}

	// todo fix lang loading
	$data       = new stdClass;
	$module     = new OnAppElasticUsersModule( $params );
	$data->lang = $module->loadLang()->Client;
	//$data->lang   = json_decode( OnAppElasticUsersModule::$tmpModuleLang )->Client;
	$data->jsLang = json_encode( $data->lang );
	$data->params = json_decode( json_encode( $params ) );

	# server form
	$server = $params[ 'serverhttpprefix' ] . '://';
	$server .= ! empty( $params[ 'serverip' ] ) ? $params[ 'serverip' ] : $params[ 'serverhostname' ];
	$tmp = [
		'login'    => $params[ 'username' ],
		'password' => $params[ 'password' ],
		'server'   => $server,
	];
	$tmp = json_encode( $tmp ) . '%%%';

	$iv_size           = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
	$iv                = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
	$key               = substr( md5( uniqid( rand( 1, 999999 ), true ) ), 0, 32 );
	$crypttext         = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $tmp, MCRYPT_MODE_ECB, $iv );
	$_SESSION[ 'utk' ] = [
		$key . substr( md5( uniqid( rand( 1, 999999 ), true ) ), rand( 0, 26 ), 5 ),
		base64_encode( base64_encode( $crypttext ) )
	];
	$data->token       = md5( uniqid( rand( 1, 999999 ), true ) );
	$data->serverURL   = $server;

	$result           = select_query(
		'OnAppElasticUsers',
		'',
		[ 'serviceID' => $params[ 'serviceid' ] ]
	);
	$data->additional = mysql_fetch_object( $result );

	return $module->buildHTML( $data, 'clientArea.tpl' );
}

function OnAppElasticUsers_AdminLink( $params ) {
	$data       = new stdClass;
	$module     = new OnAppElasticUsersModule;
	$data->lang = $module->loadLang()->Admin;

	$form = '<form target="_blank" action="http' . ( $params[ 'serversecure' ] == 'on' ? 's' : '' ) . '://' . ( empty( $params[ 'serverhostname' ] ) ? $params[ 'serverip' ] : $params[ 'serverhostname' ] ) . '/users/sign_in" method="post">
	  <input type="hidden" name="user[login]" value="' . $params[ 'serverusername' ] . '" />
	  <input type="hidden" name="user[password]" value="' . $params[ 'serverpassword' ] . '" />
	  <input type="hidden" name="commit" value="Sign In" />
	  <input type="submit" value="' . $data->lang->LoginToCP . '" class="btn btn-default" />
   </form>';

	return $form;
}

function OnAppElasticUsers_AdminServicesTabFields( $params ) {
	// todo make complex check
	$result = select_query(
		'OnAppElasticUsers',
		'',
		[ 'serviceID' => $params[ 'serviceid' ] ]
	);
	$data   = mysql_fetch_object( $result );

	// todo localize
	# get data
	$result       = select_query(
		'OnAppElasticUsers_Cache',
		'data',
		[
			'itemID' => $params[ 'serverid' ],
			'type'   => 'serverData'
		]
	);
	$billingPlans = mysql_fetch_object( $result )->data;
	$billingPlans = json_decode( $billingPlans )->BillingPlans;

	$fields = [ ];
	$field  = '';
	foreach( $billingPlans as $id => $name ) {
		if( $data->billingPlanID == $id ) {
			$selected = 'selected';
		}
		else {
			$selected = '';
		}
		$field .= '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
	}
	$fields[ 'Billing Plan' ] = '<select name="OnAppElasticUsers[billingPlanID]" class="form-control select-inline">' . $field . '</select>';
	$fields[ 'Billing Plan' ] .= '<input type="hidden" name="OnAppElasticUsers_Prev" value="' . htmlentities( json_encode( $data ) ) . '">';
	$fields[ 'OnApp user ID' ] = '<input type="text" value="' . $data->OnAppUserID . '" name="OnAppElasticUsers[OnAppUserID]">';
	$fields[ 'Billing Type' ]  = ucfirst( $data->billingType );
	$fields[ 'Trial' ]         = $data->isTrial ? 'Yes' : 'No';

	return $fields;
}

function OnAppElasticUsers_AdminServicesTabFieldsSave( $params ) {
	$prev = json_decode( html_entity_decode( $_POST[ 'OnAppElasticUsers_Prev' ] ) );

	# check server change
	if( $prev->serverID != $_POST[ 'server' ] ) {
		$_POST[ 'OnAppElasticUsers' ][ 'serverID' ] = $_POST[ 'server' ];
	}

	# check billing plan change
	if( $prev->billingPlanID != $_POST[ 'billingPlanID' ] ) {
		$module    = new OnAppElasticUsersModule( $params );
		$OnAppUser = $module->getObject( 'OnApp_User' );
		$unset     = [ 'time_zone', 'user_group_id', 'locale' ];
		$OnAppUser->unsetFields( $unset );
		$OnAppUser->_id              = $_POST[ 'OnAppElasticUsers' ][ 'OnAppUserID' ];
		$OnAppUser->_billing_plan_id = $_POST[ 'OnAppElasticUsers' ][ 'billingPlanID' ];
		$OnAppUser->save();

		if( ! is_null( $OnAppUser->error ) ) {
			$lang = $module->loadLang()->Admin;
			unset( $_POST[ 'OnAppElasticUsers' ][ 'billingPlanID' ] );
			$errorMsg = $lang->Error_ChangeBillingPlan . ":\\n";
			$errorMsg .= $OnAppUser->getErrorsAsString( "\\n" );
			echo '<script>alert("' . $errorMsg . '");</script><meta http-equiv="refresh" content="0">';
			exit;
		}
	}

	update_query(
		'OnAppElasticUsers',
		$_POST[ 'OnAppElasticUsers' ],
		[ 'id' => $prev->id ]
	);
}

class OnAppElasticUsersModule {
	const MODULE_VERSION = '3.6';
	const MODULE_NAME    = 'OnAppElasticUsers';

	private $server;

	public function __construct( $params = null ) {
		if( $params != null ) {
			if( is_array( $params ) ) {
				$this->server          = new stdClass;
				$this->server->ID      = $params[ 'configoption1' ];
				$this->server->user    = $params[ 'serverusername' ];
				$this->server->pass    = $params[ 'serverpassword' ];
				$this->server->address = $params[ 'serverhttpprefix' ] . '://';
				$this->server->address .= $params[ 'serverip' ] ? : $params[ 'serverhostname' ];
			}
			else {
				$this->server       = new stdClass;
				$this->server->ID   = $params->id;
				$this->server->user = $params->username;
				$this->server->pass = $params->password;
				if( $params->secure == 'on' ) {
					$this->server->address = 'https://';
				}
				else {
					$this->server->address = 'http://';
				}
				$this->server->address .= $params->ipaddress ? : $params->hostname;
			}
		}
	}

	public function getUserGroups() {
		$data = $this->getObject( 'OnApp_UserGroup' )->getList();

		return $this->buildArray( $data );
	}

	public function getRoles() {
		$data = $this->getObject( 'OnApp_Role' )->getList();

		return $this->buildArray( $data );
	}

	public function getBillingPlans() {
		$data = $this->getObject( 'OnApp_BillingPlan' )->getList();

		return $this->buildArray( $data );
	}

	public function getLocales() {
		$tmp = [ ];
		foreach( $this->getObject( 'OnApp_Locale' )->getList() as $locale ) {
			if( empty( $locale->name ) ) {
				continue;
			}
			$tmp[ $locale->code ] = $locale->name;
		}

		return $tmp;
	}

	public function getData() {
		$table  = 'OnAppElasticUsers_Cache';
		$where  = [
			'type'   => 'serverData',
			'itemID' => $this->server->ID,
		];
		$result = select_query( $table, 'data', $where );

		if( $data = mysql_fetch_object( $result ) ) {
			# get data from local DB
			$data = json_decode( $data->data );
		}
		else {
			# get data from OnApp CP
			$data               = new stdClass;
			$data->BillingPlans = $this->getBillingPlans();
			$data->Roles        = $this->getRoles();
			$data->UserGroups   = $this->getUserGroups();
			$data->Locales      = $this->getLocales();

			# store data to DB
			$values = [
				'type'   => 'serverData',
				'itemID' => $this->server->ID,
				'data'   => json_encode( $data ),
			];
			insert_query( $table, $values );
		}

		return $data;
	}

	public function getObject( $class ) {
		$obj = new $class;
		$obj->auth( $this->server->address, $this->server->user, $this->server->pass );
		return $obj;
	}

	public function buildHTML( stdClass &$data, $tpl = 'productSettings.tpl' ) {
		require_once ROOTDIR . '/vendor/smarty/smarty/libs/Smarty.class.php';
		$templatesDir         = __DIR__ . '/includes/html/';
		$templatesCacheDir    = $GLOBALS[ 'templates_compiledir' ];
		$smarty               = new Smarty();
		$compile_dir          = file_exists( $templatesCacheDir ) ? $templatesCacheDir : ROOTDIR . '/' . $templatesCacheDir;
		$smarty->compile_dir  = $compile_dir;
		$smarty->template_dir = __DIR__ . '/includes/html';
		$smarty->assign( (array)$data );

		return $smarty->fetch( $templatesDir . $tpl );
	}

	private function buildArray( $data ) {
		$tmp = array();
		foreach( $data as $item ) {
			$tmp[ $item->_id ] = $item->_label;
		}

		return $tmp;
	}

	public function loadLang( $languageFile = null ) {
		global $CONFIG;
		$languageFileDir = __DIR__ . '/lang/';

		if( is_null( $languageFile ) ) {
			$languageFile = isset( $_SESSION[ 'Language' ] ) ? $_SESSION[ 'Language' ] : $CONFIG[ 'Language' ];
		}
		$languageFile = $languageFileDir . strtolower( $languageFile ) . '.php';

		if( ! file_exists( $languageFile ) ) {
			$languageFile = $languageFileDir . 'english.php';
		}

		$lang = require $languageFile;
		$lang = json_encode( $lang );
		$lang = json_decode( $lang );

		return $lang;
	}

	public static function getAmount( array $params ) {
		if( $_GET[ 'tz_offset' ] != 0 ) {
			$dateFrom = date( 'Y-m-d H:i', strtotime( $_GET[ 'start' ] ) + ( $_GET[ 'tz_offset' ] * 60 ) );
			$dateTill = date( 'Y-m-d H:i', strtotime( $_GET[ 'end' ] ) + ( $_GET[ 'tz_offset' ] * 60 ) );
		}
		else {
			$dateFrom = $_GET[ 'start' ];
			$dateTill = $_GET[ 'end' ];
		}
		$date = array(
			'period[startdate]' => $dateFrom,
			'period[enddate]'   => $dateTill,
		);

		$data = self::getResourcesData( $params, $date );

		if( ! $data ) {
			return false;
		}

		$sql  = 'SELECT
					`code`,
					`rate`
				FROM
					`tblcurrencies`
				WHERE
					`id` = ' . $params[ 'clientsdetails' ][ 'currency' ];
		$rate = mysql_fetch_assoc( full_query( $sql ) );

		$data  = $data->user_stat;
		$unset = array(
			'vm_stats',
			'stat_time',
			'user_resources_cost',
			'user_id',
		);
		foreach( $data as $key => &$value ) {
			if( in_array( $key, $unset ) ) {
				unset( $data->$key );
			}
			else {
				$data->$key *= $rate[ 'rate' ];
			}
		}
		$data->currency_code = $rate[ 'code' ];

		return $data;
	}

	private static function getResourcesData( $params, $date ) {
		$sql  = 'SELECT
					`serverID`,
					`WHMCSUserID`,
					`OnAppUserID`
				FROM
					`OnAppElasticUsers`
				WHERE
					`serviceID` = ' . $params[ 'serviceid' ] . '
				LIMIT 1';
		$user = mysql_fetch_assoc( full_query( $sql ) );

		$serverAddr = $params[ 'serverhttpprefix' ] . '://';
		$serverAddr .= ! empty( $params[ 'serverip' ] ) ? $params[ 'serverip' ] : $params[ 'serverhostname' ];

		$date = http_build_query( $date );

		$url  = $serverAddr . '/users/' . $user[ 'OnAppUserID' ] . '/user_statistics.json?' . $date;
		$data = self::sendRequest( $url, $params[ 'serverusername' ], $params[ 'serverpassword' ] );

		if( $data ) {
			return json_decode( $data );
		}
		else {
			return false;
		}
	}

	private static function sendRequest( $url, $user, $password ) {
		require_once __DIR__ . '/includes/php/CURL.php';

		$curl = new CURL();
		$curl->addOption( CURLOPT_USERPWD, $user . ':' . $password );
		$curl->addOption( CURLOPT_HTTPHEADER, array( 'Accept: application/json', 'Content-type: application/json' ) );
		$curl->addOption( CURLOPT_HEADER, true );
		$data = $curl->get( $url );

		if( $curl->getRequestInfo( 'http_code' ) != 200 ) {
			return false;
		}
		else {
			return $data;
		}
	}

	public static function generatePassword() {
		return substr( str_shuffle( '~!@$%^&*(){}|0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ), 0, 20 );
	}
}

function OnAppElastic_Custom_GeneratePassword( $params ) {
	$serviceID = $params[ 'serviceid' ];
	$clientID  = $params[ 'clientsdetails' ][ 'userid' ];
	$serverID  = $params[ 'serverid' ];
	$password  = OnAppElasticUsersModule::generatePassword();

	$query = "SELECT
					`OnAppUserID`
				FROM
					`OnAppElasticUsers`
				WHERE
					serverID = '$serverID'
					AND WHMCSUserID = '$clientID'
					AND serviceID = '$serviceID'";
	// todo use placeholders

	$result      = full_query( $query );
	$OnAppUserID = mysql_result( $result, 0 );

	$module               = new OnAppElasticUsersModule( $params );
	$OnAppUser            = $module->getObject( 'OnApp_User' );
	$OnAppUser->_id       = $OnAppUserID;
	$OnAppUser->_password = $password;
	$OnAppUser->save();

	$lang = $module->loadLang()->Client;
	$data = new stdClass;

	if( ! is_null( $OnAppUser->error ) ) {
		$data->status  = false;
		$data->message = $lang->PasswordNotSet . ':<br/>';
		$data->message .= $OnAppUser->getErrorsAsString( '<br/>' );
	}
	else {
		// Save OnApp login and password
		full_query(
			"UPDATE
				tblhosting
			SET
				password = '" . encrypt( $password ) . "'
			WHERE
				id = '$serviceID'"
		);

		sendmessage( 'OnApp account password has been generated', $serviceID );

		$data->status  = true;
		$data->message = $lang->PasswordSet;
	}

	echo json_encode( $data );
	exit;
}

function OnAppElastic_Custom_ConvertTrial( $params ) {
	//echo '<pre style="text-align: left;">';
	//print_r( $params );
	//exit( PHP_EOL . ' die at ' . __LINE__ . ' in ' . __FILE__ );

	$result      = select_query(
		'OnAppElasticUsers',
		'',
		[
			'serviceID'   => $params[ 'serviceid' ],
			'WHMCSUserID' => $params[ 'userid' ],
			'serverID'    => $params[ 'serverid' ],
		]
	);
	$OnAppUserID = mysql_fetch_object( $result )->OnAppUserID;

	$module    = new OnAppElasticUsersModule( $params );
	$OnAppUser = $module->getObject( 'OnApp_User' );
	$unset     = [ 'time_zone', 'user_group_id', 'locale' ];
	$OnAppUser->unsetFields( $unset );
	$OnAppUser->_id              = $OnAppUserID;
	$OnAppUser->_billing_plan_id = json_decode( $params[ 'configoption24' ] )->{2};
	$OnAppUser->save();

	$data = new stdClass;
	$lang = $module->loadLang()->Client;
	if( ! is_null( $OnAppUser->error ) ) {
		$data->status  = false;
		$data->message = $lang->Error_ConvertTrial . ':<br>';
		$data->message .= $OnAppUser->getErrorsAsString( '<br>' );
	}
	else {
		$update = [
			'isTrial'       => false,
			'billingPlanID' => $OnAppUser->_billing_plan_id,
		];
		$data->status = true;
		$data->message = $lang->TrialConverted;

		update_query(
			'OnAppElasticUsers',
			$update,
			[
				'serviceID'   => $params[ 'serviceid' ],
				'WHMCSUserID' => $params[ 'userid' ],
				'serverID'    => $params[ 'serverid' ],
				'OnAppUserID' => $OnAppUserID,
			]
		);
	}

	exit( json_encode( $data ) );
}

function OnAppElastic_Custom_OutstandingDetails( $params = '' ) {
	$data = json_encode( OnAppElasticUsersModule::getAmount( $params ) );
	exit( $data );
}