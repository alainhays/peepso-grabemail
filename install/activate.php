<?php
require_once(PeepSo::get_plugin_dir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'install.php');

class PeepSoGrabemailInstall extends PeepSoInstall
{

	// optional default settings
	protected $default_config = array(
		'STATUS' => 0,
		'NOTIFICATION_MESSAGE' => 'Posted an email address to their stream',
		'NOTIFIED_USER_ID' => 1
	);

	public function plugin_activation()
	{
		// Set some default settings
		$settings = PeepSoConfigSettings::get_instance();
		$settings->set_option('peepso_grabemail_status', $default_config['STATUS']);
		$settings->set_option('peepso_grabemail_notification_message', $default_config['NOTIFICATION_MESSAGE']);
		$settings->set_option('peepso_grabemail_notified_user_id', $default_config['NOTIFIED_USER_ID']);		

		parent::plugin_activation();

		return (TRUE);
	}
}