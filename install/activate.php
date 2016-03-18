<?php
require_once(PeepSo::get_plugin_dir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'install.php');

class PeepSoGrabemailInstall extends PeepSoInstall
{
	protected $default_config = array(
		'site_alerts_grabemail' => 1,
	);

	public function plugin_activation()
	{
		// Set some default settings
		$settings = PeepSoConfigSettings::get_instance();
		$settings->set_option('peepso_grabemail_status', 0);
		$settings->set_option('peepso_grabemail_notification_message', "User %s posted an email address to their stream");
		$settings->set_option('peepso_grabemail_notified_user_id', 1);		

		parent::plugin_activation();

		return (TRUE);
	}

	public function get_email_contents()
	{
		$emails = array(
			'email_grabemail' => "Hello {userfirstname},

			User {fromfirstname} posted an email address to their stream!

			You can view the post here:
			{permalink}

			Thank you.",

		);

		return $emails;
	}
}