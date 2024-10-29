<?php
/*
Plugin Name: 	Admin Columns Diagnostics
Version: 		1.0
Description: 	Displays diagnostics information for Admin Columns
Author: 		Codepress
Author URI: 	https://wordpress.org/plugins/codepress-admin-columns/
Text Domain: 	ac-addon-diagnostics
Requires PHP:   5.3.6
License:        GPL2
License URI:    https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Admin Columns - Debug is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Admin Columns - Debug is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Admin Columns - Debug. If not, see {URI to Plugin License}.
*/

use AC\Autoloader;
use ACA\Diagnostics\Dependencies;
use ACA\Diagnostics\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

define( 'ACA_DIAGNOSTICS_FILE', __FILE__ );
define( 'ACA_DIAGNOSTICS_PATCH_DIR', __DIR__ . '/patch-files' );

require_once __DIR__ . '/classes/Dependencies.php';

add_action( 'after_setup_theme', function () {
	$dependencies = new Dependencies( plugin_basename( __FILE__ ), '1.1' );
	$dependencies->requires_php( '5.3.6' );

	if ( ! function_exists( 'AC' ) ) {
		$dependencies->add_missing_plugin( 'Admin Columns', $dependencies->get_search_url( 'Admin Columns' ) );
	}

	if ( $dependencies->has_missing() ) {
		return;
	}

	Autoloader::instance()->register_prefix( 'ACA\Diagnostics', __DIR__ . '/classes/' );

	$plugin = new Diagnostics( __FILE__ );
	$plugin->register();
} );