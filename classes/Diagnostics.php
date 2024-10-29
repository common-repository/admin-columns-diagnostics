<?php

namespace ACA\Diagnostics;

use AC;
use ACA\Diagnostics\Admin\Pages;
use ACA\Diagnostics\Patches\V4308;

final class Diagnostics extends AC\Plugin {

	/**
	 * @var string
	 */
	private $file;

	/** @var string */
	private $page;

	/**
	 * @param string $file Location of the plugin main file
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->page = new Pages\Diagnostics( $this );

		$this->add_page();
	}

	/**
	 * Add settings link
	 */
	public function register() {
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 1, 2 );
		add_filter( 'wp_loaded', array( $this, 'apply_patches' ) );
	}

	/**
	 * @param array  $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function add_settings_link( $links, $file ) {
		if ( $file === $this->get_basename() ) {
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', $this->page->get_link(), __( 'Settings', 'codepress-admin-columns' ) ) );
		}

		return $links;
	}

	/**
	 * @return string
	 */
	protected function get_file() {
		return $this->file;
	}

	/**
	 * @return string
	 */
	protected function get_version_key() {
		return 'aca_diagnostics';
	}

	/**
	 * Add diagnostics page to the settings
	 * @see AC
	 */
	public function add_page() {
		AC()->admin()->get_pages()->register_page( $this->page );
	}

	/**
	 * Apply available patches
	 */
	public function apply_patches() {
		$patcher = new Patcher();
		$patcher
			->add_patch( new V4308() )
			->apply_patches();
	}

}