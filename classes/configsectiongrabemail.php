<?php

class PeepSoConfigSectionGrabemail extends PeepSoConfigSectionAbstract
{
	// Builds the groups array
	public function register_config_groups()
	{
		$this->context='left';
		$this->group_general();
	}

	
	/**
	 * Mentions Configuration Box
	 * This Will set status greetings and userID number
	 */
	private function group_general()
	{
		// # Message Custom Greeting
		$this->set_field(
			'peepso_grabemail_text',
			__('Switch this on to enable send notification to email','peepsograbemail'),
			'message'
		);

		// # Use Mentions
		$this->set_field(
			'peepso_grabemail_status',
			__('Use Mentions', 'peepsograbemail'),
			'yesno_switch'
		);

		// # Notification Message that will send to specified user_id
		$this->set_field(
			'peepso_grabemail_notification_message',
			__('Notification Message', 'peepsograbemail'),
			'text'
		);

		// The next has to be a number
		$this->args('int', TRUE);
		$this->args('validation', array('required','numeric'));

		// If we didn't specify a default during plugin activation, we can do it now
		$this->args('default', 1);

		// Once again the args will be included automatically. Note that args set before previous field are gone
		$this->set_field(
			'peepso_grabemail_notified_user_id',
			__('Notified User ID', 'peepsograbemail'),
			'text'
		);

		$this->set_group(
			'peepso_grabemail_general',
			__('General', 'peepsograbemail')
		);
	}
}