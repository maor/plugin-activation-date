<?php
/*
 * Plugin Name: Plugin Activation Date
 * Plugin URI: http://wordpress.org/extend/plugins/plugin-activation-date/
 * Description: Shows when each plugin's status was changed. Useful in instances where many plugins are installed.
 * Version: 1.1
 * Author: Maor Chasen
 * Author URI: http://maorchasen.com
 * License: GPLv2
 * Text Domain: padate
 * Domain Path: /languages/
 */

/**
 * Main plugin wrapper.
 *
 * @since  1.0
 * @todo   support multisite
 */
class Plugin_Activation_Date {

	/**
	 * Holds the de/activation date for all plugins.
	 *
	 * @since  1.0
	 * @access private
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor.
	 * Sets up activation hook, localization support and registers some essential hooks.
	 *
	 * @since  1.0
	 * @access public
	 * @return Plugin_Activation_Date
	 */
	public function __construct() {
		// Register essential hooks. Pay special attention to {activate/deactivate}_plugin.
		add_filter( 'admin_init',                      array( $this, 'register_settings_field' ) );
		add_filter( 'manage_plugins_columns',          array( $this, 'plugins_columns' ) );
		add_filter( 'manage_plugins_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'all_plugins',                     array( $this, 'filter_all_plugins' ) );
		add_action( 'activate_plugin',                 array( $this, 'pad_plugin_status_changed' ) );
		add_action( 'deactivate_plugin',               array( $this, 'pad_plugin_status_changed' ) );
		add_action( 'admin_head-plugins.php',          array( $this, 'column_css_styles' ) );
		add_action( 'manage_plugins_custom_column',    array( $this, 'activated_columns' ), 10, 3 );

		// Get them options, and keep around for later use
		$this->options = get_option( 'pad_activated_plugins', array() );
		// Load our text domain
		load_plugin_textdomain( 'padate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		// Runs on activation only
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}

	/**
	 * Add the last activated/deactivate data to all plugins.
	 *
	 * This allows for sorting on the manage plugins screen.
	 *
	 * @param array $plugins All plugins.
	 *
	 * @return array
	 */
	public function filter_all_plugins( $plugins ) {
		$options = get_option( 'pad_activated_plugins', array() );

		$plugin_files = array_keys( $plugins );

		foreach ( $plugin_files as &$plugin ) {

			$plugins[ $plugin ]['Last_activated_date']   = '';
			$plugins[ $plugin ]['Last_deactivated_date'] = '';

			if ( isset( $options[ $plugin ] ) ) {
				$plugins[ $plugin ][ 'Last_' . $options[ $plugin ]['status'] . '_date' ] = $options[ $plugin ]['timestamp'];
			}
		}

		return $plugins;
	}

	/**
	 * Runs when a plugin changes status, and adds the de/activation timestamp
	 * to $this->options, then stores it in the options table.
	 *
	 * @since  1.1
	 *
	 * @param string $plugin The path to the de/activated plugin
	 */
	public function pad_plugin_status_changed( $plugin ) {
		$this->options[ $plugin ] = array(
			'status'    => current_filter() == 'activate_plugin' ? 'activated' : 'deactivated',
			'timestamp' => current_time( 'timestamp' )
		);
		update_option( 'pad_activated_plugins', $this->options );
	}

	/**
	 * Sets up the column headings.
	 *
	 * @since  1.0
	 * @uses   $status Indicates on which plugin screen we are currently
	 *
	 * @param  array $columns All of the columns for the plugins page
	 *
	 * @return array The same array with our new column
	 */
	public function plugins_columns( $columns ) {
		global $status;

		// If we're either on the Must Use or Drop-ins tabs, there's no reason to show the column
		if ( ! in_array( $status, array( 'mustuse', 'dropins' ) ) ) {
			if ( ! in_array( $status, array( 'recently_activated', 'inactive' ) ) ) {
				$columns['last_activated_date'] = __( 'Last Activated', 'padate' );
			} else {
				$columns['last_deactivated_date'] = __( 'Last Deactivated', 'padate' );
			}
		}

		return $columns;
	}

	/**
	 * Filter the list table sortable columns.
	 *
	 * @param array $sortable_columns An array of sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns( $sortable_columns ) {

		if ( ! isset( $sortable_columns['name'] ) ) {
			$sortable_columns['name'] = array( 'name', false );
		}

		$sortable_columns['last_activated_date']   = array( 'last_activated_date', true );
		$sortable_columns['last_deactivated_date'] = array( 'last_deactivated_date', true );

		return $sortable_columns;
	}

	/**
	 * Outputs the date when this plugin was last activated. Repeats for all plugins.
	 *
	 * @since  1.0
	 *
	 * @param  string $column_name The column key
	 * @param  string $plugin_file The path to the current plugin in the loop
	 * @param  array  $plugin_data Extra plugin data
	 */
	public function activated_columns( $column_name, $plugin_file, $plugin_data ) {
		$current_plugin = &$this->options[ $plugin_file ];

		switch ( $column_name ) {
			case 'last_activated_date':
				if ( ! empty( $current_plugin ) ) {
					echo $this->display_date( $current_plugin['timestamp'] );
				}
				break;
			case 'last_deactivated_date':
				if ( ! empty( $current_plugin ) && $current_plugin['status'] == 'deactivated' ) {
					echo $this->display_date( $current_plugin['timestamp'] );
				}
				break;
		}
	}

	/**
	 * Register a settings field under Settings > General
	 *
	 * @since  1.0
	 * @uses   register_setting Registers the setting option
	 * @uses   add_settings_field Regisers the field
	 */
	public function register_settings_field() {
		register_setting( 'general', 'pad_display_relative_date', 'esc_attr' );
		add_settings_field( 'pad_relative_date', esc_html__( 'Plugin Activation Date', 'padate' ), array(
			$this,
			'fields_html'
		), 'general', 'default', array( 'label_for' => 'pad_display_relative_date' ) );
	}

	/**
	 * Prints the field's HTML
	 *
	 * @since  1.0
	 *
	 * @param  array $args Extra arguments passed by add_settings_field
	 */
	public function fields_html( $args ) {
		$value     = get_option( 'pad_display_relative_date', 0 );
		$the_input = sprintf( '<input type="checkbox" id="pad_display_relative_date" name="pad_display_relative_date" %s /> %s', checked( $value, 'on', false ), esc_html__( 'Display relative date?', 'padate' ) );
		printf( '<label for="pad_display_relative_date">%s</label>', $the_input );
	}

	/**
	 * Displays the de/activation date for every plugin respectively.
	 *
	 * @since  1.0
	 * @uses   apply_filters() Calls 'pad_date_time_format' for plugins to alter the output date format.
	 *
	 * @param  string $timestamp The timestamp for the current plugin in the loop
	 *
	 * @return string The formatted date
	 */
	public function display_date( $timestamp ) {
		$is_relative      = 'on' == get_option( 'pad_display_relative_date' ) ? true : false;
		$date_time_format = apply_filters( 'pad_date_time_format', sprintf( '%s - %s', get_option( 'date_format' ), get_option( 'time_format' ) ) );

		if ( $is_relative ) {
			return sprintf( esc_html__( '%s ago', 'padate' ), human_time_diff( $timestamp, current_time( 'timestamp' ) ) );
		} else {
			return date_i18n( $date_time_format, $timestamp );
		}
	}

	/**
	 * Set our column's width so it's more readable.
	 *
	 * @since  1.0
	 */
	public function column_css_styles() {
		?>
		<style>#last_activated_date, #last_deactivated_date {
				width: 18%;
			}</style>
	<?php
	}

	/**
	 * Runs on activation, registers a few options for this plugin to operate.
	 *
	 * @since 1.0
	 * @uses  add_option()
	 */
	public function activation() {
		add_option( 'pad_activated_plugins' );
		add_option( 'pad_display_relative_date' );
	}
}

// Initiate the plugin. Access everywhere using $global plugin_activation_date
$GLOBALS['plugin_activation_date'] = new Plugin_Activation_Date;