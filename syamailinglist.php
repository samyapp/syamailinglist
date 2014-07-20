<?php
/*
Plugin Name: Simply Yet Another Mailing List
Plugin URI: https://github.com/samyapp/syemail
Description: Simple mailing list sign-up plugin to let visitors to your blog sign-up for your mailing list. Asks for name, email and country. Easy to modify to add your own custom fields.
Version: 0.1 BETA
Author: Sam Yapp
Author URI: http://samyapp.com

***********************************************************************
    Copyright 2014  Sam Yapp  (email : icodeforfood@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die();

class SYAMailingList
{
	const PLUGIN_NAME = 'Simply Yet Another Mailing List';
	const DB_VERSION = 0.3;	// current database version
	const DB_VERSION_OPTION = 'syamailinglist_db_version'; // wp_option name for storing db version
	const SUBMIT_FIELD = 'syamlsubscribe'; // name of the input submit field to use in forms
	const NAME_FIELD = 'name'; // name of the name field
	const EMAIL_FIELD = 'email'; // name of the email field
	const COUNTRY_FIELD = 'country'; // name of the country field
	const DATE_FIELD = 'date_added'; // Name of the date field
	const MIN_NAME_FIELD_LENGTH = 1; // Minimum number of characters required to consider a name is valid

	const FORM_COLLECTION = 'syaml';
	
	/**
	 * Singleton instance of this object
	 */
	protected static $_instance = null;

	/**
	 * Holds field values, if submitted, or set as defaults.
	 */
	protected $form_values = array();

	/**
 	 * If any errors in form submission, store them as field_name => 'error message'
	 */
	protected $errors = array();

	/**
	 * Has the form been submitted
	 */
	protected $submitted = false;

	/**
	 * Contain associative array of country-code => country name, once loaded by
	 * get_countries()
	 */
	protected $countries = null;

	public function table_name()
	{
		global $wpdb;
		return $wpdb->prefix . 'syamailinglist_emails';
	}

	/**
	 * Install (or upgrade) the database table used to store submissions
	 */
	public function install_db()
	{
		global $wpdb;

		if ( self::DB_VERSION > get_option( self::DB_VERSION_OPTION ) )
		{
			$datefield = self::DATE_FIELD;
			$namefield = self::NAME_FIELD;
			$emailfield = self::EMAIL_FIELD;
			$countryfield = self::COUNTRY_FIELD;
			$sql = "CREATE TABLE " . $this->table_name() . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			$datefield datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			$namefield varchar(200) NOT NULL,
			$emailfield varchar(255) NOT NULL,
			$countryfield VARCHAR(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
			);";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Call install_db if the database is out of date
	 */
	public function do_we_need_to_upgrade_db()
	{
		if ( self::DB_VERSION > get_option( self::DB_VERSION_OPTION ) )
		{
			$this->install_db();
		}
	}

	/**
	 * Register our Widget
	 */
	public function register_widget()
	{
		require_once dirname(__FILE__) . '/widget.php';
		register_widget( 'SYAMailingList_Widget');
	}

	/**
	 * Construct the plugin - register hooks and callbacks, and set the singleton instance of ourself
	 */
	public function __construct()
	{
		self::$_instance = $this;

		register_activation_hook( __FILE__, array( $this, 'install_db' ) );
		add_action( 'plugins_loaded', array( $this, 'do_we_need_to_upgrade_db' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_shortcode('syamailinglist_signup', array( $this, 'do_shortcode' ) );
		add_action('init', array( $this, 'check_form_submission' ) );
	}

	/**
	 * Check if our form has been submitted, and store the submitted values.
	 * Called by the 'init' hook.
	 */
	public function check_form_submission()
	{
		if ( isset( $_POST[ self::FORM_COLLECTION ] ) ) {
			$data = $_POST[ self::FORM_COLLECTION ];
			$this->submitted = true;
			$this->form_values[ self::NAME_FIELD ] = trim( $data[ self::NAME_FIELD ] );
			$this->form_values[ self::EMAIL_FIELD ] = trim( $data[ self::EMAIL_FIELD ] );
			$this->form_values[ self::COUNTRY_FIELD ] = trim( $data[ self::COUNTRY_FIELD ] );
			$this->errors = $this->validate_data( $this->form_values );
			if ( ! $this->has_errors() ) {
				$this->add_to_database( $this->form_values );
			}
		}
	}

	/**
	 * Validate the submitted form data, returning an array of errors if any.
	 * @param array $data Associative array of field-name => value pairs.
	 * @return array An array containing field-name => error-message pairs if any errors occurred.
	 */
	public function validate_data( $data )
	{
		global $wpdb;
		$errors = array();
		if ( ! isset( $data[ self::NAME_FIELD ] ) ||
		   	strlen( $data[ self::NAME_FIELD ] ) < self::MIN_NAME_FIELD_LENGTH ) {
			$errors[ self::NAME_FIELD ] = __( 'Please enter your name', self::PLUGIN_NAME );
		}
		if ( empty( $data[ self::EMAIL_FIELD ] ) ) {
			$errors[ self::EMAIL_FIELD ] = __( 'Your email address is required', self::PLUGIN_NAME );
		} elseif ( ! filter_var( $data[ self::EMAIL_FIELD ], FILTER_VALIDATE_EMAIL ) ) {
			$errors[ self::EMAIL_FIELD ] = str_replace( '%s', 
				esc_html( $data[ self::EMAIL_FIELD ] ), __( '"%s" is not a valid email address', self::PLUGIN_NAME ) );
		} elseif ( $wpdb->get_var( $wpdb->prepare( 
				'select count(*) from ' . $this->table_name() . ' where ' . self::EMAIL_FIELD . ' = %s ', $data[ self::EMAIL_FIELD ] ) ) > 0 ) {
			$errors[ self::EMAIL_FIELD ] = str_replace( '%s', 
				esc_html( $data[ self::EMAIL_FIELD ] ), __( 'The email "%s" is already on our mailing list', self::PLUGIN_NAME ) );
		}
		if ( empty( $data[ self::COUNTRY_FIELD ] ) || ! $this->is_valid_country( $data[ self::COUNTRY_FIELD ] ) ) {
			$errors[ $data[ self::COUNTRY_FIELD] ] = __( 'Please select your country', self::PLUGIN_NAME );
		}
		return $errors;
	}

	/**
	 * Get an array of country-code => country names
	 */
	public function get_countries()
	{
		if ( null == $this->countries ) {
			$this->countries = include dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'countries.php';
		}
		return $this->countries;
	}

	/**
	 * Is this a valid country?
	 * @param string $country The name of the country to check
	 * @return bool True if the country is valid, otherwise false.
	 */
	public function is_valid_country( $country )
	{
		$countries = $this->get_countries();
		return isset( $countries[ $country ] );
	}

	/**
	 * Save the data to the database. Assumes data has already been validated.
	 */
	public function add_to_database( $data )
	{
		global $wpdb;
		$wpdb->insert( $this->table_name(), 
			array (
				self::NAME_FIELD => $data[ self::NAME_FIELD ],
				self::EMAIL_FIELD => $data[ self::EMAIL_FIELD ],
				self::COUNTRY_FIELD => $data[ self::COUNTRY_FIELD ],
				self::DATE_FIELD => (new DateTime())->format('Y-m-d H:i:s')				
			),
			array (
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
	}

	/**
	 * Handle a shortcode to display our form by using our widget class
	 */
	public function do_shortcode()
	{
		ob_start();
		the_widget('SYAMailingList_Widget');
		return ob_get_clean();
	}

	/**
	 * Gets the values to display in the form.
	 * If not already set (by form submission), sets defaults.
	 * @return Array of form-field-name => value
	 */
	public function get_form_values()
	{
		if ( !$this->form_values ) {
			$this->form_values = array(
				self::NAME_FIELD => '',
				self::EMAIL_FIELD => '',
				self::COUNTRY_FIELD => $this->get_default_country()
			);
		}
		return $this->form_values;
	}

	/**
	 * Has the form been submitted?
	 * @return true if submitted, otherwise false
	 */
	public function has_been_submitted()
	{
		return $this->submitted;
	}

	/**
	 * Were there any form submission errors?
	 */
	public function has_errors()
	{
		return count($this->errors) > 0;
	}

	/**
	 * Get the array of field-name => 'error-message'
	 */
	public function get_errors()
	{
		return $this->errors;
	}

	/**
	 * Get the default country for the current visitor
	 */
	public function get_default_country()
	{
		return 'UK';
	}

	/**
	 * Get an instance of the SYAMailingList plugin
	 * @return An SYAMailingList plugin object
	 */
	public static function instance()
	{
		if ( null == self::$_instance ) {
			new static();
		}
		return self::$_instance;
	}
}

// This creates a plugin object, which handles all the setup etc in the __construct() method above.
// future calls (for example in the widget) to SYAMailingList::instance() will return this object 
// rather than creating a new object.
SYAMailingList::instance();


