<?php
/**
 * @package    Internals
 * @since      1.1.0
 * @version    1.1.0
 */

// Avoid direct calls to this file.
if ( ! class_exists( 'Yoast_WooCommerce_SEO' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 *****************************************************************
 * Option: wpseo_woo
 */
if ( ! class_exists( 'WPSEO_Option_Woo' ) && class_exists( 'WPSEO_Option' ) ) {

	/**
	 * Class WPSEO_Option_Woo
	 */
	class WPSEO_Option_Woo extends WPSEO_Option {

		/**
		 * @var  string  option name
		 */
		public $option_name = 'wpseo_woo';

		/**
		 * @var  string  option group name for use in settings forms
		 */
		public $group_name = 'wpseo_woo_options';

		/**
		 * @var  bool  whether to include the option in the return for WPSEO_Options::get_all()
		 */
		public $include_in_all = false;

		/**
		 * @var  bool  whether this option is only for when the install is multisite
		 */
		public $multisite_only = false;

		/**
		 * @var int Database version to check whether the plugins options need updating.
		 */
		public $db_version = 2;

		/**
		 * @var  array  Array of defaults for the option
		 *        Shouldn't be requested directly, use $this->get_defaults().
		 */
		protected $defaults = array(
			// Non-form fields, set via validation routine.
			'dbversion'           => 0, // Leave default as 0 to ensure activation/upgrade works.

			// Form fields.
			'data1_type'          => 'price',
			'data2_type'          => 'stock',
			'schema_brand'        => '',
			'schema_manufacturer' => '',
			'breadcrumbs'         => true,
			'hide_columns'        => true,
			'metabox_woo_top'     => true,
		);

		/**
		 * @var    array $valid_data_types Array of pre-defined valid data types, will be enriched with taxonomies.
		 */
		public $valid_data_types = array();


		/**
		 * Add the actions and filters for the option.
		 *
		 * @return \WPSEO_Option_Woo
		 */
		protected function __construct() {
			parent::__construct();

			// Set and translate the valid data types.
			$this->valid_data_types = array(
				'price' => __( 'Price', 'yoast-woo-seo' ),
				'stock' => __( 'Stock', 'yoast-woo-seo' ),
			);
		}


		/**
		 * Get the singleton instance of this class
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Validates the option.
		 *
		 * @param  array $dirty New value for the option.
		 * @param  array $clean Clean value for the option, normally the defaults.
		 * @param  array $old   Old value of the option.
		 *
		 * @todo remove code using $short, there is no "short form" anymore.
		 *
		 * @return  array      Validated clean value for the option to be saved to the database.
		 */
		protected function validate_option( $dirty, $clean, $old ) {

			// Have we receive input from a short (license only) form.
			$short = ( isset( $dirty['short_form'] ) && $dirty['short_form'] === 'on' );

			// Prepare an array of valid data types and taxonomies to validate against.
			$valid_data_types = array_keys( $this->valid_data_types );
			$valid_taxonomies = $this->get_taxonomies();
			if ( ! empty( $valid_taxonomies ) ) {
				$valid_data_types = array_merge( $valid_data_types, $valid_taxonomies );
			}

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					case 'dbversion':
						$clean[ $key ] = $this->db_version;
						break;

					case 'data1_type':
					case 'data2_type':
						if ( isset( $dirty[ $key ] ) ) {
							if ( in_array( $dirty[ $key ], $valid_data_types, true ) ) {
								$clean[ $key ] = $dirty[ $key ];
							}
							else {
								if ( sanitize_title_with_dashes( $dirty[ $key ] ) === $dirty[ $key ] ) {
									// Allow taxonomies which may not be registered yet.
									$clean[ $key ] = $dirty[ $key ];
								}
							}
						}
						else {
							if ( $short && isset( $old[ $key ] ) ) {
								if ( in_array( $old[ $key ], $valid_data_types, true ) ) {
									$clean[ $key ] = $old[ $key ];
								}
								else {
									if ( sanitize_title_with_dashes( $old[ $key ] ) === $old[ $key ] ) {
										// Allow taxonomies which may not be registered yet.
										$clean[ $key ] = $old[ $key ];
									}
								}
							}
						}
						break;

					case 'schema_brand':
					case 'schema_manufacturer':
						if ( isset( $dirty[ $key ] ) ) {
							if ( in_array( $dirty[ $key ], $valid_taxonomies, true ) ) {
								$clean[ $key ] = $dirty[ $key ];
							}
							else {
								if ( sanitize_title_with_dashes( $dirty[ $key ] ) === $dirty[ $key ] ) {
									// Allow taxonomies which may not be registered yet.
									$clean[ $key ] = $dirty[ $key ];
								}
							}
						}
						else {
							if ( $short && isset( $old[ $key ] ) ) {
								if ( in_array( $old[ $key ], $valid_taxonomies, true ) ) {
									$clean[ $key ] = $old[ $key ];
								}
								else {
									if ( sanitize_title_with_dashes( $old[ $key ] ) === $old[ $key ] ) {
										// Allow taxonomies which may not be registered yet.
										$clean[ $key ] = $old[ $key ];
									}
								}
							}
						}
						break;

					/* boolean (checkbox) field - may not be in form */
					case 'breadcrumbs':
					case 'hide_columns':
					case 'metabox_woo_top':
						if ( isset( $dirty[ $key ] ) ) {
							$clean[ $key ] = WPSEO_WooCommerce_Wrappers::validate_bool( $dirty[ $key ] );
						}
						else {
							if ( $short && isset( $old[ $key ] ) ) {
								$clean[ $key ] = WPSEO_WooCommerce_Wrappers::validate_bool( $old[ $key ] );
							}
							else {
								$clean[ $key ] = false;
							}
						}
						break;
				}
			}

			return $clean;
		}

		/**
		 * Returns a list of lower cased taxonomies.
		 *
		 * @return array The found taxonomies.
		 */
		protected function get_taxonomies() {
			$taxonomies = get_object_taxonomies( 'product', 'objects' );

			if ( ! is_array( $taxonomies ) || empty( $taxonomies ) ) {
				return array();
			}

			$processed_taxonomies = array();
			foreach ( $taxonomies as $taxonomy ) {
				$processed_taxonomies[] = strtolower( $taxonomy->name );
			}

			unset( $taxonomies );

			return $processed_taxonomies;
		}
	}

}
