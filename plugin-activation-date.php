<?php
/*
 * Plugin Name: Plugin Activation Date
 * Plugin URI: http://wordpress.org/
 * Description: Shows when each plugin was last activated. Useful in instances that deal with many plugins.
 * Version: 1.0
 * Author: Maor Chasen
 * Author URI: http://maorchasen.com
 * License: GPLv2
 * Text Domain: padate
 * Domain Path: /languages/
 */

/**
 * Some optional features:
 * @todo make column sortable using WP_List_Table's built in sorting method
 * @todo support multisite
 */
class Plugin_Activation_Date {

	private $options = array();

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		// Load our text domain
		load_plugin_textdomain( 'padate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Set up some hooks to power this plugin
		add_filter( 'admin_init' , array( $this, 'register_settings_field' ) );
		add_filter( 'manage_plugins_columns', array( $this, 'plugins_columns' ) );
		add_action( 'activate_plugin', array( $this, 'pad_plugin_activated' ) );
		add_action( 'deactivate_plugin', array( $this, 'pad_plugin_deactivated' ) );
		add_action( 'admin_head-plugins.php', array( $this, 'column_css_styles' ) );
		add_action( 'manage_plugins_custom_column', array( $this, 'activated_columns' ), 10, 3 );

		// Get them options, and keep around for later use
		$this->options = get_option( 'pad_activated_plugins', array() );
	}

	public function pad_plugin_activated( $plugin ) {
		$this->options[ $plugin ] = array(
			'status' 	=> 'activated',
			'timestamp' => current_time( 'timestamp' )
		);
		update_option( 'pad_activated_plugins', $this->options );
	}

	public function pad_plugin_deactivated( $plugin ) {
		$this->options[ $plugin ] = array(
			'status' 	=> 'deactivated',
			'timestamp' => current_time( 'timestamp' )
		);
		update_option( 'pad_activated_plugins', $this->options );
	}

	public function plugins_columns( $columns ) {
		global $status;

		if ( ! in_array( $status, array( 'mustuse', 'dropins' ) ) )
			if ( ! in_array( $status, array( 'recently_activated', 'inactive' ) ) )
				$columns['last_activated_date'] = __( 'Last Activated', 'padate' );
			else
				$columns['last_deactivated_date'] = __( 'Last Deactivated', 'padate' );

		return $columns;
	}

	/**
	 * Output the date when this plugin was last activated. Repeats for all plugins.
	 */
	public function activated_columns( $column_name, $plugin_file, $plugin_data ) {
		global $status;

		$current_plugin = &$this->options[ $plugin_file ];

		switch ( $column_name ) {
			case 'last_activated_date':
				if ( ! empty( $current_plugin ) )
					echo $this->display_date( $current_plugin['timestamp'] );
				break;
			case 'last_deactivated_date':
				if ( ! empty( $current_plugin ) && $current_plugin['status'] == 'deactivated' )
					echo $this->display_date( $current_plugin['timestamp'] );
				break;
		}
	}

	public function register_settings_field() {
		register_setting( 'general', 'pad_display_relative_date', 'esc_attr' );
		add_settings_field( 'pad_relative_date', esc_html__( 'Plugin Activation Date', 'padate' ), array( $this, 'fields_html' ), 'general', 'default', array( 'label_for' => 'pad_display_relative_date' ) );
	}

	public function fields_html( $args ) {
		$value = get_option( 'pad_display_relative_date', 0 );
		$the_input = sprintf( '<input type="checkbox" id="pad_display_relative_date" name="pad_display_relative_date" %s /> %s', checked( $value, 'on', false ), esc_html__( 'Display relative date?', 'padate') );
		printf( '<label for="pad_display_relative_date">%s</label>', $the_input );
	}

	public function display_date( $timestamp ) {
		$is_relative = 'on' == get_option( 'pad_display_relative_date' ) ? true : false;
		$date_time_format = apply_filters( 'pad_date_time_format', sprintf( '%s - %s', get_option('date_format'), get_option('time_format') ) );

		if ( $is_relative )
			return sprintf( esc_html__( '%s ago', 'padate' ), human_time_diff( $timestamp, current_time( 'timestamp' ) ) );
		else
			return date_i18n( $date_time_format, $timestamp );
	}

	/**
	 * Set our column's width so it's more readable.
	 * We're rockin' HTML5 style, baby!
	 */
	public function column_css_styles() {
		?>
		<style>#last_activated_date, #last_deactivated_date { width: 18%; }</style>
		<?php
	}

	public function activation() {
		add_option( 'pad_activated_plugins' );
	}
}

new Plugin_Activation_Date;