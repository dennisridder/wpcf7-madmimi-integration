<?php

namespace DennisRidder\Integrations\WPCF7;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Connector.php';
require_once __DIR__ . '/MadmimiService.php';

require_once dirname(__FILE__) . '/../vendor/madmimi/Spyc.class.php';
require_once dirname(__FILE__) . '/../vendor/madmimi/MadMimi.class.php';


$service = MadMimi_Service::get_instance();

$connector = new Connector( $service );
