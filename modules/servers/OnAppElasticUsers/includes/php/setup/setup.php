<?php

# process SQL
logactivity( 'OnApp Elastic User Module: process SQL file.' );

$sql = file_get_contents( __DIR__ . '/module.sql' );
$sql = explode( PHP_EOL . PHP_EOL, $sql );

$tmpSQLConfig = $CONFIG[ 'SQLErrorReporting' ];
$CONFIG[ 'SQLErrorReporting' ] = '';
foreach( $sql as $qry ) {
	full_query( $qry );
}
$CONFIG[ 'SQLErrorReporting' ] = $tmpSQLConfig;
unset( $tmpSQLConfig );

# process mail templates todo uncomment
require __DIR__ . '/module.mail.php';

# store module version
$whmcs->set_config( 'OnAppElasticUsersVersion', OnAppElasticUsersModule::MODULE_VERSION );