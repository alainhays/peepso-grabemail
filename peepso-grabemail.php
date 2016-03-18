<?php
/**
 * Plugin Name: PeepSoGrabemail
 * Plugin URI: https://peepso.com
 * Description: Plugin PeepSo Grab email for testing purpose of PeepSo addons Development
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.5.4
 * Copyright: (c) 2015 PeepSo All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsotest
 * Domain Path: /language
 *
 * This software contains GPLv2 or later software courtesy of PeepSo.com, Inc
 *
 * PeepSoGrabemail is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free SALPHA1oftware Foundation, either version 2 of the License, or
 * any later version.
 *
 * PeepSoGrabemail is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY. See the
 * GNU General Public License for more details.
 */

Class PeepSoGrabemail
{

	/**
	 * Instance Object
	 *
	 * @var object
	 */
	private static $_instance = NULL;

	/**
	 * Plugin version
	 *
	 * @var const
	 */
	const PLUGIN_VERSION = '1.5.4';

	/**
	 * Plugin Release
	 *
	 * @var const
	 */
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE

	/**
	 * Plugin Name
	 *
	 * @var const
	 */
	const PLUGIN_NAME = 'PeepSoGrabemail';

	/**
	 * Plugin Edd
	 *
	 * @var const
	 */
    const PLUGIN_EDD = 'peepsograbemail';

    /**
	 * Plugin slug
	 *
	 * @var const
	 */
    const PLUGIN_SLUG = 'peepsograbemail';

    /**
	 * Module ID of PeepSo
	 *
	 * @var const
	 */
    const MODULE_ID = 99;

    // optional default settings
	protected $default_config = array(
		'STATUS' => 0,
		'NOTIFICATION_MESSAGE' => 'posted an email address to their stream',
		'NOTIFIED_USER_ID' => 1
	);

	/**
	 * Class constructor
	 *
	 * Initialize all variables, filters and actions
	 *
	 * @return	void
	 */
	private function __construct()
	{
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		add_action('peepso_init', array(&$this, 'init'));

		if (is_admin()) {
			add_action('admin_init', array(&$this, 'check_peepso'));
		}

		register_activation_hook(__FILE__, array(&$this, 'activate'));
	}

	/**
	 * retrieve singleton class instance
	 * @return instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance) {
			self::$_instance = new self();
		}
		return (self::$_instance);
	}

	/**
	 * Loads the translation file for the PeepSo plugin
	 */
	public function load_textdomain()
	{
		$path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
		load_plugin_textdomain('peepsograbemail', FALSE, $path);
	}	

	/**
	 * Initialize the PeepSoGrabemail plugin
	 * @return void
	 */
	public function init()
	{
		PeepSo::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
		PeepSoTemplate::add_template_directory(plugin_dir_path(__FILE__));

		if (is_admin()) {
			add_action('admin_init', array(&$this, 'check_peepso'));
			add_filter('peepso_admin_config_tabs', array(&$this, 'admin_config_tabs'));
		} else {
			add_action('peepso_activity_after_add_post', array(&$this, 'after_save_post'), 10, 2);
		}

		// used by Profile page UI to configure alerts and notifications setting
		add_filter('peepso_profile_alerts', array(&$this, 'profile_alerts'), 10, 1);
	}

	/**
     * Check if PeepSo is installed and activated
     * Prevent from activating self if peepso is not installed
     * Prevent from activating self if peepso version is not compatible
     */
	public function check_peepso()
	{
		if (!$this->peepso_exist())
		{
			if (is_plugin_active(plugin_basename(__FILE__))) {
				// deactivate the plugin
				deactivate_plugins(plugin_basename(__FILE__));
				// display notice for admin
				add_action('admin_notices', array(&$this, 'disabled_notice'));
				if (isset($_GET['activate'])) {
					unset($_GET['activate']);
				}
			}
			return (FALSE);
		}
		// run core version comparison
		if( defined('PeepSo::PLUGIN_RELEASE') ) {
			$this->version_check = PeepSo::check_version_compat(self::PLUGIN_VERSION, self::PLUGIN_RELEASE);
		} else {
			$this->version_check = PeepSo::check_version_compat(self::PLUGIN_VERSION);
		}

        // if it's not OK, render an error/warning
        if( 1 != $this->version_check['compat'] ) {

            add_action('admin_notices', array(&$this, 'version_notice'));

            // only if it's a total failure, disable the plugin
            if( 0 == $this->version_check['compat'] ) {
                deactivate_plugins(plugin_basename(__FILE__));
                if (isset($_GET['activate']))
                    unset($_GET['activate']);

                return (FALSE);
            }
        }

        return (TRUE);
	}

	/**
	 * Show message if version not compatible
	 * @return void
	 */
    public function version_notice()
    {
        PeepSo::version_notice(self::PLUGIN_NAME, self::PLUGIN_SLUG, $this->version_check);
    }	

    /**
     * Called on first activation
     * @return void
     */
	public function activate()
	{
		if (!$this->check_peepso()) {
			return (FALSE);
		}

		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
		$install = new PeepSoGrabemailInstall();
		$res = $install->plugin_activation();
		if (FALSE === $res) {
			// error during installation - disable
			deactivate_plugins(plugin_basename(__FILE__));
		}

		return (TRUE);
	}

    /**
	 * Check for existence of the PeepSo class
	 * @return bool
	 */
	private function peepso_exist()
	{
		return (class_exists('PeepSo'));
	}

	/**
	 * Show message if peepsomood can not be installed or run
	 * @return void
	 */
	public function disabled_notice()
	{
		echo '<div class="error fade">';
		echo
		'<strong>' , self::PLUGIN_NAME , ' ' ,
		__('plugin requires the PeepSo plugin to be installed and activated.', 'peepso'),
		' <a href="plugin-install.php?tab=plugin-information&amp;plugin=peepso-core&amp;TB_iframe=true&amp;width=772&amp;height=291" class="thickbox">',
		__('Get it now!', 'peepso'),
		'</a>',
		'</strong>';
		echo '</div>';
	}

	/**
	 * Registers a tab in the PeepSo Config Toolbar
	 * PS_FILTER
	 *
	 * @param $tabs array
	 * @return array
	 */
	public function admin_config_tabs( $tabs )
	{
		$tabs['mentions'] = array(
			'label' => __('Grab Email', 'PeepSoGrabemail'),
			'tab' => 'mentions',
			'description' => __('PeepSo Grab Email', 'PeepSoGrabemail'),
			'function' => 'PeepSoConfigSectionGrabemail'
		);

		return $tabs;
	}

	/**
	 * Fires once a post has been saved.
	 * @param int $post_id Post ID.
	 * @param int $act_id  The activity ID.
	 */
	public function after_save_post($post_id, $act_id)
	{
		$post_obj = get_post($post_id);
		// extract email address from post content
		$emails = $this->extract_email_address($post_obj->post_content);

		if (count($emails)) {
			global $post;

			$activity = PeepSoActivity::get_instance();
			// TODO: not always successful. Should check return value
			$post_act = $activity->get_activity($act_id);

			$post = $post_obj;
			setup_postdata($post);

			$user_author = new PeepSoUser($post->post_author);
			$data = array('permalink' => peepso('activity', 'post-link', FALSE));
			$from_fields = $user_author->get_template_fields('from');

			// get option value
			$settings = PeepSoConfigSettings::get_instance();
			$mentions_status = $settings->get_option('peepso_grabmail_status', $default_config['STATUS']);
			$mentions_notification_message = $settings->get_option('peepso_grabmail_notification_message', $default_config['NOTIFICATION_MESSAGE']);
			$mentions_user_id = $settings->get_option('peepso_grabmail_notified_user_id', $default_config['NOTIFIED_USER_ID']);		

			$user_id = intval($mentions_user_id);

			// If self don't send the notification
			if (intval($post->post_author) === $user_id)
				continue;

			// Check access
			if (!PeepSo::check_permissions($user_id, PeepSo::PERM_POST_VIEW, intval($post->post_author)))
				continue;

			// check the peepso mentions status
			if (!boolval($mentions_status))
				continue;

			$user_owner = new PeepSoUser($user_id);
			$data = array_merge($data, $from_fields, $user_owner->get_template_fields('user'));
			
			// send email immediately
			PeepSoMailQueue::add_message($user_id, $data, $mentions_notification_message, 'mentions', 'mention', self::MODULE_ID, 1);

			$notifications = new PeepSoNotifications();
			$_notification = __('Email address mention in a post', 'peepsograbemail');
			$notifications->add_notification(intval($post->post_author), $user_id, $_notification, 'tag', self::MODULE_ID, $post_id);
		}
	}

	/**
	 * Filter and validate email address from post
	 * 
	 * @param String
	 * @return array
	 */
	function extract_email_address ($string) {
	    foreach(preg_split('/\s/', $string) as $token) {
	        $email = filter_var(filter_var($token, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);
	        if ($email !== false) {
	            $emails[] = $email;
	        }
	    }
	    return $emails;
	}

	/**
	 * Append profile alerts definition for peepsotags. Used on profile?alerts page
	 * @param array
	 * @return array
	 */
	public function profile_alerts($alerts)
	{
		$alerts['tags'] = array(
				'title' => __('Grab Email', 'peepsograbemail'),
				'items' => array(
					array(
						'label' => __('You were Tagged in a Post', 'peepsograbemail'),
						'setting' => 'tag',
					)
				),
		);
		// NOTE: when adding new items here, also add settings to /install/activate.php site_alerts_ sections
		return ($alerts);
	}

}

PeepSoGrabemail::get_instance();

// EOF