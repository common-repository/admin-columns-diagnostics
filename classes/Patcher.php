<?php

namespace ACA\Diagnostics;

class Patcher {

	/**
	 * @var Patch[]
	 */
	private $patches;

	/**
	 * @param Patch $patch
	 *
	 * @return Patcher
	 */
	public function add_patch( Patch $patch ) {
		$this->patches[] = $patch;

		return $this;
	}

	public function apply_patches() {
		foreach ( $this->patches as $patch ) {
			$patch->apply_patch();
		}
	}

}