<?php

class WPSEO_Option_Woo_Double extends WPSEO_Option_Woo {

	/**
	 * Makes the constructor public.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Makes the method testable by setting its visibility to public.
	 *
	 * Validates the option.
	 *
	 * @param  array $dirty New value for the option.
	 * @param  array $clean Clean value for the option, normally the defaults.
	 * @param  array $old   Old value of the option.
	 *
	 *
	 * @return  array      Validated clean value for the option to be saved to the database.
	 */
	public function validate_option( $dirty, $clean, $old ) {
		return parent::validate_option( $dirty, $clean, $old );
	}

}
