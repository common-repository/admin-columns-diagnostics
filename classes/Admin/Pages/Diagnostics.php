<?php

namespace ACA\Diagnostics\Admin\Pages;

use AC\Admin\Page;
use AC\Plugin;

class Diagnostics extends Page {

	/** @var Plugin */
	private $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this
			->set_slug( 'debug' )
			->set_label( __( 'Debug', 'ac-addon-diagnostics' ) );

		$this->register();
	}

	/**
	 * @inheritDoc
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Admin scripts
	 */
	public function admin_scripts() {
		if ( ! $this->is_current_screen() ) {
			return;
		}

		wp_enqueue_style( 'ac-debug', $this->plugin->get_url() . 'assets/css/debug.css', array(), $this->plugin->get_version() );
		wp_enqueue_script( 'ac-debug', $this->plugin->get_url() . 'assets/js/admin-debug.js', array(), $this->plugin->get_version(), true );
	}

	/**
	 * @inheritDoc
	 */
	public function display() {
		?>

		<table class="form-table ac-form-table debug">
		<tr class="debug">
			<th scope="row">
				<h2>
					<?php _e( 'Diagnostics', 'ac-addon-diagnostics' ); ?>
				</h2>
				<p>
					<?php _e( 'Paste this information in your support request.', 'ac-addon-diagnostics' ); ?>
				</p>

				<button class="button button-primary" data-ac-copy-clipboard="ac-debug-info">
					<?php _e( 'Copy to clipboard', 'ac-addon-diagnostics' ); ?>
				</button>
			</th>
			<td id="ac-debug-info">
				<h3>
					<?php _e( 'General', 'ac-addon-diagnostics' ); ?>
				</h3>

				<?php echo nl2br( implode( $this->formatted_diagnostic_info() ) ); ?>

			</td>
		</tr>

		<?php
	}

	/**
	 * @return array
	 */
	private function formatted_diagnostic_info() {
		$ouput = array();

		foreach ( $this->get_diagnostic_info() as $name => $section ) {
			$ouput[] = '<p>';

			// for active and mu plugins
			if ( isset( $section[1] ) && is_array( $section[1] ) ) {
				$ouput[] = sprintf( "\n<strong>%s</strong>\n", $section[0] );
				$ouput[] = implode( "\n", $section[1] );
			} else {
				foreach ( $section as $label => $value ) {
					$ouput[] = sprintf( "\n<strong>%s</strong> : %s", $label, $value );
				}
			}

			$ouput[] = '</p>';
		}

		return $ouput;
	}

	/**
	 * @param string $plugin_path
	 *
	 * @return false|string
	 */
	private function get_plugin_details( $plugin_path ) {
		$plugin_data = get_plugin_data( $plugin_path );
		$plugin_name = strlen( $plugin_data['Name'] ) ? $plugin_data['Name'] : basename( $plugin_path );

		if ( empty( $plugin_name ) ) {
			return false;
		}

		$version = '';
		if ( $plugin_data['Version'] ) {
			$version = sprintf( " (v%s)", $plugin_data['Version'] );
		}

		$author = '';
		if ( $plugin_data['AuthorName'] ) {
			$author = sprintf( " by %s", $plugin_data['AuthorName'] );
		}

		return sprintf( "%s%s%s", $plugin_name, $version, $author );
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	private function remove_wp_plugin_dir( $name ) {
		$plugin = str_replace( WP_PLUGIN_DIR, '', $name );

		return substr( $plugin, 1 );
	}

	/**
	 * @return int
	 */
	private function get_post_max_size() {
		$bytes = max( wp_convert_hr_to_bytes( trim( ini_get( 'post_max_size' ) ) ), wp_convert_hr_to_bytes( trim( ini_get( 'hhvm.server.max_post_size' ) ) ) );

		return $bytes;
	}

	/**
	 * Disagnostics info
	 * @return array
	 * @global \wpdb $wpdb
	 */
	private function get_diagnostic_info() {
		global $wpdb;

		$diagnostic_info = array();

		$diagnostic_info['basic-info'] = array(
			'site_url()' => site_url(),
			'home_url()' => home_url(),
		);

		$diagnostic_info['db-info'] = array(
			'Database Name' => $wpdb->dbname,
			'Table Prefix'  => $wpdb->base_prefix,
		);

		$diagnostic_info['wp-version'] = array(
			'WordPress Version' => get_bloginfo( 'version' ),
		);

		if ( is_multisite() ) {
			$diagnostic_info['multisite-info'] = array(
				'Multisite'            => defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ? 'Sub-domain' : 'Sub-directory',
				'Domain Current Site'  => defined( 'DOMAIN_CURRENT_SITE' ) ? DOMAIN_CURRENT_SITE : 'Not Defined',
				'Path Current Site'    => defined( 'PATH_CURRENT_SITE' ) ? PATH_CURRENT_SITE : 'Not Defined',
				'Site ID Current Site' => defined( 'SITE_ID_CURRENT_SITE' ) ? SITE_ID_CURRENT_SITE : 'Not Defined',
				'Blog ID Current Site' => defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 'Not Defined',
			);
		}

		$diagnostic_info['server-info'] = array(
			'Web Server'                     => ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '',
			'PHP'                            => ( function_exists( 'phpversion' ) ) ? phpversion() : '',
			'WP Memory Limit'                => WP_MEMORY_LIMIT,
			'PHP Time Limit'                 => ( function_exists( 'ini_get' ) ) ? ini_get( 'max_execution_time' ) : '',
			'Blocked External HTTP Requests' => ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) ? 'None' : ( WP_ACCESSIBLE_HOSTS ? 'Partially (Accessible Hosts: ' . WP_ACCESSIBLE_HOSTS . ')' : 'All' ),
			'fsockopen'                      => ( function_exists( 'fsockopen' ) ) ? 'Enabled' : 'Disabled',
			'OpenSSL'                        => ( defined( 'OPENSSL_VERSION_TEXT' ) ) ? OPENSSL_VERSION_TEXT : 'Disabled',
			'cURL'                           => ( function_exists( 'curl_init' ) ) ? 'Enabled' : 'Disabled',
		);

		$diagnostic_info['db-server-info'] = array(
			'MySQL'      => empty( $wpdb->use_mysqli ) ? mysql_get_server_info() : mysqli_get_server_info( $wpdb->dbh ),
			'ext/mysqli' => empty( $wpdb->use_mysqli ) ? 'no' : 'yes',
			'WP Locale'  => get_locale(),
			'DB Charset' => DB_CHARSET,
		);

		$diagnostic_info['debug-settings'] = array(
			'Debug Mode'    => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No',
			'Debug Log'     => ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ? 'Yes' : 'No',
			'Debug Display' => ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) ? 'Yes' : 'No',
			'Script Debug'  => ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'Yes' : 'No',
			'PHP Error Log' => ( function_exists( 'ini_get' ) ) ? ini_get( 'error_log' ) : '',
		);

		$server_limits = array(
			'WP Max Upload Size' => size_format( wp_max_upload_size() ),
			'PHP Post Max Size'  => size_format( $this->get_post_max_size() ),
		);

		if ( function_exists( 'ini_get' ) ) {
			if ( $suhosin_limit = ini_get( 'suhosin.post.max_value_length' ) ) {
				$server_limits['Suhosin Post Max Value Length'] = is_numeric( $suhosin_limit ) ? size_format( $suhosin_limit ) : $suhosin_limit;
			}
			if ( $suhosin_limit = ini_get( 'suhosin.request.max_value_length' ) ) {
				$server_limits['Suhosin Request Max Value Length'] = is_numeric( $suhosin_limit ) ? size_format( $suhosin_limit ) : $suhosin_limit;
			}
		}
		$diagnostic_info['server-limits'] = $server_limits;

		$constants = array(
			'WP_HOME'        => ( defined( 'WP_HOME' ) && WP_HOME ) ? WP_HOME : 'Not defined',
			'WP_SITEURL'     => ( defined( 'WP_SITEURL' ) && WP_SITEURL ) ? WP_SITEURL : 'Not defined',
			'WP_CONTENT_URL' => ( defined( 'WP_CONTENT_URL' ) && WP_CONTENT_URL ) ? WP_CONTENT_URL : 'Not defined',
			'WP_CONTENT_DIR' => ( defined( 'WP_CONTENT_DIR' ) && WP_CONTENT_DIR ) ? WP_CONTENT_DIR : 'Not defined',
			'WP_PLUGIN_DIR'  => ( defined( 'WP_PLUGIN_DIR' ) && WP_PLUGIN_DIR ) ? WP_PLUGIN_DIR : 'Not defined',
			'WP_PLUGIN_URL'  => ( defined( 'WP_PLUGIN_URL' ) && WP_PLUGIN_URL ) ? WP_PLUGIN_URL : 'Not defined',
		);

		if ( is_multisite() ) {
			$constants['UPLOADS'] = ( defined( 'UPLOADS' ) && UPLOADS ) ? UPLOADS : 'Not defined';
			$constants['UPLOADBLOGSDIR'] = ( defined( 'UPLOADBLOGSDIR' ) && UPLOADBLOGSDIR ) ? UPLOADBLOGSDIR : 'Not defined';
		}

		$diagnostic_info['constants'] = $constants;

		$theme_info = wp_get_theme();
		$theme_info_log = array(
			'Active Theme Name'   => $theme_info->Name,
			'Active Theme Folder' => $theme_info->get_stylesheet_directory(),
		);
		if ( $theme_info->get( 'Template' ) ) {
			$theme_info_log['Parent Theme Folder'] = $theme_info->get( 'Template' );
		}

		$diagnostic_info['theme-info'] = $theme_info_log;

		$active_plugins_log = array( 'Active Plugins' );

		$active_plugins_log[1] = array();

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_active_plugins = wp_get_active_network_plugins();
			$active_plugins = array_map( array( $this, 'remove_wp_plugin_dir' ), $network_active_plugins );
		}
		foreach ( $active_plugins as $plugin ) {
			$active_plugins_log[1][] = $this->get_plugin_details( WP_PLUGIN_DIR . '/' . $plugin );
		}

		$diagnostic_info['active-plugins'] = $active_plugins_log;

		$mu_plugins = wp_get_mu_plugins();
		if ( $mu_plugins ) {
			$mu_plugins_log = array( 'Must-Use Plugins' );
			$mu_plugins_log[1] = array();
			foreach ( $mu_plugins as $mu_plugin ) {
				$mu_plugins_log[1][] = $this->get_plugin_details( $mu_plugin );
			}
			$diagnostic_info['mu-plugins'] = $mu_plugins_log;
		}

		return $diagnostic_info;
	}

}