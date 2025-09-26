<?php

/**
 * CF7_SMTP plugin bootstrap
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Engine;

use cf7_smtp\Engine;

/**
 * CF7_SMTP Initializer.
 */
class Initialize {


	/**
	 * List of class to initialize.
	 *
	 * @var array
	 */
	public $classes = array();

	/**
	 * Instance of this Context.
	 *
	 * @var object
	 */
	protected $content = null;

	/**
	 * Composer autoload file list.
	 *
	 * @var \Composer\Autoload\ClassLoader
	 */
	private $composer;

	/**
	 * The Constructor that load the entry classes
	 *
	 * @param \Composer\Autoload\ClassLoader $composer Composer autoload output.
	 *
	 * @throws \Exception - unable to load classes.
	 * @since 0.0.1
	 */
	public function __construct( \Composer\Autoload\ClassLoader $composer ) {
		if ( defined( 'WPCF7_VERSION' ) ) {
			$this->content  = new Engine\Context();
			$this->composer = $composer;

			if ( $this->content->request( 'rest' ) ) {
				$this->get_classes( 'Rest' );
			}

			if ( $this->content->request( 'backend' ) ) {
				$this->get_classes( 'Backend' );
			}

			if ( $this->content->request( 'core' ) ) {
				$this->get_classes( 'Core' );
			}

			$this->load_classes();
		} else {
			add_action(
				'admin_notices',
				function () {
					printf(
						'<div class="notice notice-info"><p>%s<a href="%s">%s</a>%s</p></div>',
						esc_html__( 'SMTP for Contact Form 7 need ', 'cf7-smtp' ),
						esc_url( 'https://wordpress.org/plugins/contact-form-7/' ),
						esc_html__( 'Contact Form 7', 'cf7-smtp' ),
						esc_html__( ' installed and enabled in order to work.', 'cf7-smtp' )
					);
				}
			);
		}
	}

	/**
	 * Initialize all the classes.
	 *
	 * @since 0.0.1
	 * @return void
	 * @throws \Exception $err - throw warnings wen the module isn't initialized.
	 * @SuppressWarnings("MissingImport")
	 */
	private function load_classes() {
		$this->classes = \apply_filters( 'cf7_smtp_classes_to_execute', $this->classes );

		foreach ( $this->classes as $class ) {
			try {
				$temp = new $class();

				if ( \method_exists( $temp, 'initialize' ) ) {
					$temp->initialize();
				}
			} catch ( \Exception $err ) {
				\do_action( 'cf7_smtp_initialize_failed', $err );

				if ( WP_DEBUG ) {
					throw new \Exception( $err->getMessage() );
				}
			}
		}
	}

	/**
	 * Based on the folder loads the classes automatically using the Composer autoload to detect the classes of a Namespace.
	 *
	 * @param string $namespace Class name to find.
	 * @since 0.0.1
	 * @return array Return the classes.
	 */
	private function get_classes( string $namespace ): array {
		$prefix    = $this->composer->getPrefixesPsr4();
		$classmap  = $this->composer->getClassMap();
		$namespace = 'cf7_smtp\\' . $namespace;

		// In case composer has autoload optimized.
		if ( isset( $classmap['cf7_smtp\\Engine\\Initialize'] ) ) {
			$classes = \array_keys( $classmap );

			foreach ( $classes as $class ) {
				if ( 0 !== \strncmp( (string) $class, $namespace, \strlen( $namespace ) ) ) {
					continue;
				}

				$this->classes[] = $class;
			}

			return $this->classes;
		}

		$namespace .= '\\';

		// In case composer is not optimized.
		if ( isset( $prefix[ $namespace ] ) ) {
			$folder    = $prefix[ $namespace ][0];
			$php_files = $this->scandir( $folder );
			$this->find_classes( $php_files, $folder, $namespace );

			if ( ! WP_DEBUG ) {
				\wp_die( \esc_html__( 'cf7-smtp is on production environment with missing `composer dumpautoload -o` that will improve the performance on autoloading itself.', 'cf7-smtp' ) );
			}

			return $this->classes;
		}

		return $this->classes;
	}

	/**
	 * Get php files inside the folder/subfolder that will be loaded.
	 * This class is used only when Composer is not optimized.
	 *
	 * @param string $folder Path.
	 * @since 0.0.1
	 * @return array List of files.
	 */
	private function scandir( string $folder ): array {
		$temp_files = \scandir( $folder );
		$files      = array();

		if ( \is_array( $temp_files ) ) {
			$files = $temp_files;
		}

		return \array_diff( $files, array( '..', '.', 'index.php' ) );
	}

	/**
	 * Load namespace classes by files.
	 *
	 * @param array  $php_files List of files with the Class.
	 * @param string $folder Path of the folder.
	 * @param string $base Namespace base.
	 * @since 0.0.1
	 * @return void
	 */
	private function find_classes( array $php_files, string $folder, string $base ) {
		foreach ( $php_files as $php_file ) {
			$class_name = \substr( $php_file, 0, -4 );
			$path       = $folder . '/' . $php_file;

			if ( \is_file( $path ) ) {
				$this->classes[] = $base . $class_name;

				continue;
			}

			// Verify the Namespace level.
			if ( \substr_count( $base . $class_name, '\\' ) < 2 ) {
				continue;
			}

			if ( ! \is_dir( $path ) || \strtolower( $php_file ) === $php_file ) {
				continue;
			}

			$sub_php_files = $this->scandir( $folder . '/' . $php_file );
			$this->find_classes( $sub_php_files, $folder . '/' . $php_file, $base . $php_file . '\\' );
		}
	}
}
