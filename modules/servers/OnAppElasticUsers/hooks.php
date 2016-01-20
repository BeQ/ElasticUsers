<?php

if( ! defined( 'WHMCS' ) ) {
	exit( 'This file cannot be accessed directly' );
}

# config options
# 1 - serverID
# 2 - BillingType
# 3 - SuspendDays
# 4 - TrialDays
# 5 - DueDays

function InvoicePaidHook_OnAppElasticUsers( $vars ) {
	$invoiceID = $vars[ 'invoiceid' ];
	$qry = 'SELECT
				OnAppElasticUsers.`WHMCSUserID`,
				OnAppElasticUsers.`serverID`,
				OnAppElasticUsers.`OnAppUserID`,
				tblhosting.`id` AS service_id,
				tblinvoices.`subtotal` AS subtotal,
				tblinvoices.`total` AS total,
				tblproducts.`configoption1` AS settings,
				tblhosting.`domainstatus` AS status
			FROM
				tblinvoices
			LEFT JOIN OnAppElasticUsers ON
				tblinvoices.`userid` = OnAppElasticUsers.`WHMCSUserID`
			LEFT JOIN tblhosting ON
				tblhosting.`userid` = OnAppElasticUsers.`WHMCSUserID`
				AND tblhosting.`server` = OnAppElasticUsers.`serverID`
			RIGHT JOIN tblinvoiceitems ON
				tblinvoiceitems.`invoiceid` = tblinvoices.`id`
				AND tblinvoiceitems.`relid` = tblhosting.`id`
			LEFT JOIN tblproducts ON
				tblproducts.`id` = tblhosting.`packageid`
			WHERE
				tblinvoices.`id` = @invoiceID
				AND tblinvoices.`status` = "Paid"
				AND tblproducts.`servertype` = "OnAppElasticUsers"
				AND tblinvoiceitems.`type` = "OnAppElasticUsers"
			GROUP BY
				tblinvoices.`id`';
	$qry = str_replace( '@invoiceID', $invoiceID, $qry );
	$result = full_query( $qry );

	if( mysql_num_rows( $result ) == 0 ) {
		return;
	}

	$data = mysql_fetch_assoc( $result );
	if( $data[ 'status' ] == 'Suspended' ) {
		# check for other unpaid invoices for this service
		$qry = 'SELECT
					tblinvoices.`id`
				FROM
					tblinvoices
				RIGHT JOIN tblinvoiceitems ON
					tblinvoiceitems.`invoiceid` = tblinvoices.`id`
					AND tblinvoiceitems.`relid` = :serviceID
				WHERE
					tblinvoices.`status` = "Unpaid"
				GROUP BY
					tblinvoices.`id`';
		$qry = str_replace( ':serviceID', $data[ 'service_id' ], $qry );
		$result = full_query( $qry );

		if( mysql_num_rows( $result ) == 0 ) {
			if( ! function_exists( 'serverunsuspendaccount' ) ) {
				$path = dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/';
				require_once $path . 'modulefunctions.php';
			}
			serverunsuspendaccount( $data[ 'service_id' ] );
		}
	}

	if( ! defined( 'ONAPP_WRAPPER_INIT' ) ) {
		define( 'ONAPP_WRAPPER_INIT', $path . 'wrapper/OnAppInit.php' );
		require_once ONAPP_WRAPPER_INIT;
	}

	$qry = 'SELECT
				`secure`,
				`username`,
				`hostname`,
				`password`,
				`ipaddress`
			FROM
				tblservers
			WHERE
				`type` = "OnAppElasticUsers"
				AND `id` = :serverID';
	$qry = str_replace( ':serverID', $data[ 'server_id' ], $qry );
	$result = full_query( $qry );
	$server = mysql_fetch_assoc( $result );
	$server[ 'password' ] = decrypt( $server[ 'password' ] );
	if( $server[ 'secure' ] ) {
		$server[ 'address' ] = 'https://';
	}
	else {
		$server[ 'address' ] = 'http://';
	}
	if( empty( $server[ 'ipaddress' ] ) ) {
		$server[ 'address' ] .= $server[ 'hostname' ];
	}
	else {
		$server[ 'address' ] .= $server[ 'ipaddress' ];
	}
	unset( $server[ 'ipaddress' ], $server[ 'hostname' ], $server[ 'secure' ] );

	# get OnApp amount
	$result = select_query(
		'OnAppElasticUsers_Cache',
		'data',
		[
			'itemID' => 123,
			'type'   => 'invoiceData',
		]
	);
	$amount   = mysql_fetch_object( $result )->data;

	if( $amount ) {
		$payment = new OnApp_Payment;
		$payment->auth( $server[ 'address' ], $server[ 'username' ], $server[ 'password' ] );
		$payment->_user_id        = $data[ 'onapp_user_id' ];
		$payment->_amount         = $amount;
		$payment->_invoice_number = $invoiceID;
		$payment->save();

		$error = $payment->getErrorsAsString();
		if( empty( $error ) ) {
			$msg = 'OnApp payment was sent. Service ID #' . $data[ 'service_id' ] . ', amount: ' . $amount;

			# delete invoice data
			$where = [
				'itemID' => $invoiceID,
				'type'   => 'invoiceData',
			];
			delete_query( 'OnAppElasticUsers_Cache', $where );
		}
		else {
			$msg = 'ERROR with OnApp payment for service ID #' . $data[ 'service_id' ] . ': ' . $error;
		}
	}
	else {
		$msg = 'ERROR with OnApp payment for service ID #' . $data[ 'service_id' ] . ': Cannot find OnApp amount';
	}

	logactivity( $msg );
}

function TerminateTrialHook_OnAppElasticUsers() {
	global $cron;

	$qry    = 'SELECT
					`WHMCSUserID`,
					`serviceID`
				FROM
					`OnAppElasticUsers`
				LEFT JOIN `tblhosting` ON
					tblhosting.`id` = `serviceID`
				LEFT JOIN `tblproducts` ON
					tblproducts.`id` = tblhosting.`packageid`
				WHERE
					`isTrial` = TRUE
					AND NOW() > DATE_ADD( tblhosting.`regdate`, INTERVAL tblproducts.`configoption4` DAY )';
	$result = full_query( $qry );

	if( ! function_exists( 'serverterminateaccount' ) ) {
		$path = dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/';
		require_once $path . 'modulefunctions.php';
	}

	$cnt = 0;
	echo 'Starting Processing OnAppElasticUsers Trial Terminations', PHP_EOL;
	while( $data = mysql_fetch_assoc( $result ) ) {
		ServerTerminateAccount( $data[ 'id' ] );
		echo ' - terminate service ID ', $data[ 'serviceID' ], ', user ID ', $data[ 'WHMCSUserID' ], PHP_EOL;
		++ $cnt;
	}
	echo ' - Processed ', $cnt, ' Terminations', PHP_EOL;
	$cron->emailLog( $cnt . ' OnAppElasticUsers Trial Services Terminated' );
}

function AutoSuspendHook_OnAppElasticUsers() {
	if( $GLOBALS[ 'CONFIG' ][ 'AutoSuspension' ] != 'on' ) {
		return;
	}

	global $cron;

	$qry = 'SELECT
				tblhosting.`id`,
				tblhosting.`userid`
			FROM
				tblinvoices
			LEFT JOIN tblinvoiceitems ON
				tblinvoiceitems.`invoiceid` = tblinvoices.`id`
			LEFT JOIN tblhosting ON
				tblhosting.`id` = tblinvoiceitems.`relid`
			LEFT JOIN tblproducts ON
				tblproducts.`id` = tblhosting.`packageid`
			WHERE
				tblinvoices.`status` = "Unpaid"
				AND tblinvoiceitems.`type` = "OnAppElasticUsers"
				AND tblhosting.`domainstatus` = "Active"
				AND NOW() > DATE_ADD( tblinvoices.`duedate`, INTERVAL tblproducts.`configoption3` DAY )
				AND ( tblhosting.`overideautosuspend` != 1
                        OR ( tblhosting.`overidesuspenduntil` != "0000-00-00"
                            AND tblhosting.`overidesuspenduntil` <= NOW() ) )
			GROUP BY
				tblhosting.`id`';
	$result = full_query( $qry );

	if( ! function_exists( 'serversuspendaccount' ) ) {
		$path = dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/';
		require_once $path . 'modulefunctions.php';
	}

	$cnt = 0;
	echo 'Starting Processing OnApp OnAppElasticUsers Suspensions', PHP_EOL;
	while( $data = mysql_fetch_assoc( $result ) ) {
		ServerSuspendAccount( $data[ 'id' ] );
		echo ' - suspend service ID ', $data[ 'id' ], ', user ID ', $data[ 'userid' ], PHP_EOL;
		++ $cnt;
	}
	echo ' - Processed ', $cnt, ' Suspensions', PHP_EOL;
	$cron->emailLog( $cnt . ' OnApp OnAppElasticUsers Services Suspended' );
}

function AutoTerminateHook_OnAppElasticUsers() {
	global $CONFIG, $cron;

	if( $CONFIG[ 'AutoTermination' ] != 'on' ) {
		return;
	}

	$qry = 'SELECT
				tblhosting.`id`,
				tblhosting.`userid`
			FROM
				tblinvoices
			LEFT JOIN tblinvoiceitems ON
				tblinvoiceitems.`invoiceid` = tblinvoices.`id`
			LEFT JOIN tblhosting ON
				tblhosting.`id` = tblinvoiceitems.`relid`
			WHERE
				tblinvoices.`status` = "Unpaid"
				AND tblinvoiceitems.`type` = "OnAppElasticUsers"
				AND tblhosting.`domainstatus` = "Suspended"
				AND NOW() > DATE_ADD( tblinvoices.`duedate`, INTERVAL :days DAY )
			GROUP BY
				tblhosting.`id`';
	$qry = str_replace( ':days', $CONFIG[ 'AutoTerminationDays' ], $qry );
	$result = full_query( $qry );

	if( ! function_exists( 'serverterminateaccount' ) ) {
		$path = dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/';
		require_once $path . 'modulefunctions.php';
	}

	$cnt = 0;
	echo 'Starting Processing OnApp OnAppElasticUsers Terminations', PHP_EOL;
	while( $data = mysql_fetch_assoc( $result ) ) {
		ServerTerminateAccount( $data[ 'id' ] );
		echo ' - terminate service ID ', $data[ 'id' ], ', user ID ', $data[ 'userid' ], PHP_EOL;
		++ $cnt;
	}
	echo ' - Processed ', $cnt, ' Terminations', PHP_EOL;
	$cron->emailLog( $cnt . ' OnApp OnAppElasticUsers Services Terminated' );
}

function ProductEditHook_OnAppElasticUsers( $vars ) {
	if( $_REQUEST[ 'servertype' ] !== 'OnAppElasticUsers' ) {
		return;
	}

	if( empty( $_POST[ 'OnAppElasticUsers_Skip' ] ) && isset( $_POST[ 'OnAppElasticUsers_Server' ] ) ) {
		$serverID = $_POST[ 'OnAppElasticUsers_Server' ];
		if( ! empty( $_POST[ 'OnAppElasticUsers_Prev' ] ) ) {
			$settings = json_decode( html_entity_decode( $_POST[ 'OnAppElasticUsers_Prev' ] ) );
		}
		else {
			$settings = new stdClass;
		}
		$settings->$serverID = $_POST[ 'OnAppElasticUsers' ];

		# store product settings
		$common = $_POST[ 'OnAppElasticUsers' ];
		$update = [
			'configoption2' => $common[ 'BillingType' ],
			'configoption3' => $common[ 'SuspendDays' ],
			'configoption4' => $common[ 'TrialDays' ],
			'configoption5' => $common[ 'DueDays' ],
			'configoption24' => json_encode( $settings )
		];
		$where  = [ 'id' => $vars[ 'pid' ] ];
		update_query( 'tblproducts', $update, $where );
	}

	# reset server cache
	if( ! empty( $_POST[ 'reset-server-cache' ] ) ) {
		$where = [
			'type'   => 'serverData',
			'itemID' => $_REQUEST[ 'packageconfigoption' ][ 1 ],
		];
		delete_query( 'OnAppElasticUsers_Cache', $where );
	}

	return true;
}

add_hook( 'InvoicePaid', 1, 'InvoicePaidHook_OnAppElasticUsers' );
add_hook( 'ProductEdit', 1, 'ProductEditHook_OnAppElasticUsers' );
add_hook( 'DailyCronJob', 1, 'AutoSuspendHook_OnAppElasticUsers' );
add_hook( 'DailyCronJob', 2, 'AutoTerminateHook_OnAppElasticUsers' );
add_hook( 'DailyCronJob', 3, 'TerminateTrialHook_OnAppElasticUsers' );