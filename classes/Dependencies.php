<?php

namespace ACA\Diagnostics;

/**
 * Show a notice when plugin dependencies are not met
 * @version 1.5
 */
final class Dependencies {

	/**
	 * Basename of this plugin
	 * @var string
	 */
	private $basename;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * Missing dependency messages
	 * @var string[]
	 */
	private $messages = array();

	/**
	 * @param string $basename
	 * @param string $version
	 */
	public function __construct( $basename, $version ) {
		$this->basename = $basename;
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function get_basename() {
		return $this->basename;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Register hooks
	 */
	private function register() {
		add_action( 'after_plugin_row_' . $this->basename, array( $this, 'display_notice' ), 5 );
		add_action( 'admin_head', array( $this, 'display_notice_css' ) );
	}

	/**
	 * Add missing dependency
	 *
	 * @param string $message
	 * @param string $key
	 */
	public function add_missing( $message, $key ) {
		if ( ! $this->has_missing() ) {
			$this->register();
		}

		$this->messages[ $key ] = $this->sanitize_message( $message );
	}

	/**
	 * Add missing dependency
	 *
	 * @param string $plugin
	 * @param string $url
	 * @param string $version
	 */
	public function add_missing_plugin( $plugin, $url = null, $version = null ) {
		$this->add_missing(
			$this->get_missing_plugin_message( $plugin, $url, $version ),
			$plugin
		);
	}

	/**
	 * @param string $plugin
	 * @param string $url
	 * @param string $version
	 *
	 * @return string
	 */
	private function get_missing_plugin_message( $plugin, $url = null, $version = null ) {
		$plugin = esc_html( $plugin );

		if ( $url ) {
			$plugin = sprintf( '<a href="%s">%s</a>', esc_url( $url ), $plugin );
		}

		if ( $version ) {
			$plugin .= ' ' . sprintf( 'version %s+', esc_html( $version ) );
		}

		return sprintf( '%s needs to be installed and activated.', $plugin );
	}

	/**
	 * @return bool
	 */
	public function has_missing() {
		return ! empty( $this->messages );
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	private function sanitize_message( $message ) {
		return wp_kses( $message, array(
			'a' => array(
				'href'   => true,
				'target' => true,
			),
		) );
	}

	/**
	 * Check current PHP version
	 *
	 * @param string $version
	 *
	 * @return bool
	 */
	public function requires_php( $version ) {
		if ( ! version_compare( PHP_VERSION, $version, '>=' ) ) {
			$message = sprintf(
				'PHP %s+ is required. Your server currently runs PHP %s. <a href="%s" target="_blank">Learn more about requirements.</a>',
				$version,
				PHP_VERSION,
				esc_url( 'https://www.admincolumns.com/documentation/getting-started/requirements/' )
			);

			$this->add_missing( $message, 'PHP Version' );

			return false;
		}

		return true;
	}

	/**
	 * URL that performs a search in the WordPress repository
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	public function get_search_url( $keywords ) {
		$url = add_query_arg( array(
			'tab' => 'search',
			's'   => $keywords,
		), admin_url( 'plugin-install.php' ) );

		return $url;
	}

	/**
	 * Show a warning when dependencies are not met
	 */
	public function display_notice() {
		$intro = "This plugin can't load because";

		?>

		<tr class="plugin-update-tr active">
			<td colspan="3" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-error notice-alt">
					<?php if ( count( $this->messages ) > 1 )  : ?>
						<p>
							<?php echo $intro . ':' ?>
						</p>

						<ul>
							<?php foreach ( $this->messages as $message ) : ?>
								<li><?php echo $message; ?></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p>
							<?php echo $intro . ' ' . current( $this->messages ); ?>
						</p>
					<?php endif; ?>
				</div>
			</td>
		</tr>

		<?php
	}

	/**
	 * Load additional CSS for the warning
	 */
	public function display_notice_css() {
		?>

		<style>
			.plugins tr[data-plugin='<?php echo $this->basename; ?>'] th,
			.plugins tr[data-plugin='<?php echo $this->basename; ?>'] td {
				box-shadow: none;
			}
		</style>

		<?php
	}

}