<?php

namespace ACA\Diagnostics\Patches;

use AC\Message\Notice;
use ACA\Diagnostics\Patch;
use ACP\License\API;
use ACP\License\Manager;

final class V4308
	implements Patch {

	public function apply_patch() {
		if ( ! function_exists( 'ACP' )
		     || ! method_exists( ACP(), 'get_version' )
		     || ACP()->get_version() !== '4.3.8' ) {
			return;
		}

		$option_key = 'acp_patched_' . __CLASS__;

		if ( get_option( $option_key ) ) {
			return;
		}

		if ( $this->copy_file() ) {
			update_option( $option_key, 1, false );

			$manager = new Manager( new API );
			$manager->force_plugin_update_check();

			$notice = Notice::with_register();
			$notice->set_message( sprintf( '%s %s',
				sprintf( __( 'Patch for Admin Columns %s has been succesfully applied.', 'ac-addon-diagnostics' ), ACP()->get_version() ),
				__( 'Refresh this page to check for plugin updates.', 'ac-addon-diagnostics' )
			) );
		}
	}

	/**
	 * Replace the faulty manager with the proper one
	 *
	 * @return bool
	 */
	private function copy_file() {
		$patch = ACA_DIAGNOSTICS_PATCH_DIR . '/v4308/Manager.php';
		$target = ACP()->get_dir() . 'classes/License/Manager.php';

		if ( ! is_readable( $patch ) || ! is_writable( $target ) ) {
			return false;
		}

		return copy( $patch, $target );
	}

}