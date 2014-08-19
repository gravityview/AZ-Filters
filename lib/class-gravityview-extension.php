<?php
/**
 * @version 1.0.4
 */
abstract class GravityView_Extension {

	protected $_title = NULL;

	protected $_version = NULL;

	protected $_text_domain = 'gravity-view';

	protected $_min_gravityview_version = '1.1.5';

	protected $_remote_update_url = 'https://gravityview.co';

	protected $_author = 'Katz Web Services, Inc.';

	protected $_path = NULL;

	static private $admin_notices = array();

	static $is_compatible = true;

	function __construct() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'admin_init', array( $this, 'settings') );
		add_action( 'admin_notices', array( $this, 'admin_notice' ), 100 );

		if( false === $this->is_extension_supported() ) {
			return;
		}

		add_filter( 'gravityview_tooltips', array( $this, 'tooltips' ) );

		// Save the form configuration. Run at 20 so that View metadata is already saved (at 10)
		add_action( 'save_post', array( $this, 'save_post' ), 20 );

		$this->add_hooks();
	}

	/**
	 * Load translations for the extension
	 * @return void
	 */
	function load_plugin_textdomain() {
		if( empty( $this->_text_domain ) ) { return; }

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$lang_dir = apply_filters( 'gravityview_az_entry_filter_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale',  get_locale(), $this->_text_domain );
		$mofile = sprintf( '%1$s-%2$s.mo', $this->_text_domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->_text_domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/gravityview-a-z-entry-filter/ folder
			load_textdomain( $this->_text_domain, $mofile_global );
		}
		elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/gravityview-a-z-entry-filter/languages/ folder
			load_textdomain( $this->_text_domain, $mofile_local );
		}
		else {
			// Load the default language files
			load_plugin_textdomain( $this->_text_domain, false, $lang_dir );
		}
	}

	function settings( $settings ) {
		if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include_once plugin_dir_path( __FILE__ ) . 'EDD_SL_Plugin_Updater.php';
		}

		if( !class_exists( 'GravityView_Settings' ) ) { return; }

		$license = GravityView_Settings::getSetting('license');

		// Don't update if invalid license.
		if( empty( $license['status'] ) || strtolower( $license['status'] ) !== 'valid' ) { return; }

		new EDD_SL_Plugin_Updater(
			$this->_remote_update_url,
			$this->_path,
			array(
            	'version'	=> $this->_version, // current version number
            	'license'	=> $license['license'],
            	'item_name' => $this->_title,  // name of this plugin
            	'author' 	=> strip_tags( $this->_author )  // author of this plugin
          	)
        );
	}

	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @return void
	 */
	function admin_notice() {

		if( empty( self::$admin_notices ) ) {
			return;
		}

		foreach( self::$admin_notices as $key => $notice ) {

			echo '<div id="message" class="'. esc_attr( $notice['class'] ).'">';
			echo wpautop( $notice['message'] );
			echo '<div class="clear"></div>';
			echo '</div>';
		}

		//reset the notices handler
		self::$admin_notices = array();
	}

	/**
	 * Add a notice to be displayed in the admin.
	 * @param array $notice Array with `class` and `message` keys. The message is not escaped.
	 */
	public static function add_notice( $notice = array() ) {

		if( is_array( $notice ) && !isset( $notice['message'] ) ) {
			do_action( 'gravityview_log_error', __CLASS__.'[add_notice] Notice not set', $notice );
			return;
		} else if( is_string( $notice ) ) {
			$notice = array( 'message' => $notice );
		}

		$notice['class'] = empty( $notice['class'] ) ? 'error' : $notice['class'];

		self::$admin_notices[] = $notice;
	}

	function add_hooks() { }

	/**
	 * Store the filter settings in the `_gravityview_filters` post meta
	 * @param  int $post_id Post ID
	 * @return void
	 */
	function save_post( $post_id ) {}

	function tooltips( $tooltips = array() ) { return $tooltips; }

	private function is_extension_supported() {

		self::$is_compatible = true;

		if( !class_exists( 'GravityView_Plugin' ) ) {

			$message = sprintf( __('Could not activate the %s Extension; GravityView is not active.', 'gravity-view'), $this->_title );

			self::add_notice( $message );

			do_action( 'gravityview_log_error', __CLASS__.'[is_compatible] ' . $message );

			self::$is_compatible = false;

		} else if( false === version_compare(GravityView_Plugin::version, $this->_min_gravityview_version , ">=") ) {

			$message = sprintf( __('The %s Extension requires GravityView Version %s or newer.', 'gravity-view' ), $this->_title, '<tt>'.$this->_min_gravityview_version.'</tt>' );

			self::add_notice( $message );

			do_action( 'gravityview_log_error', __CLASS__.'[is_compatible] ' . $message );

			self::$is_compatible = false;

		} else if( !GravityView_Admin::check_gravityforms() ) {
			self::$is_compatible = false;
		}

		return self::$is_compatible;
	}

}
?>