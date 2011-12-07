<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_server', // setting name
			get_string('survey_server','block_onlinesurvey'), //short name
			'', // long description
			'')); // default value

	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_login',
			get_string('survey_login','block_onlinesurvey'),
			'',
			''));

	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_user',
			get_string('survey_user','block_onlinesurvey'),
			'',
			''));

	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_pwd',
			get_string('survey_pwd','block_onlinesurvey'),
			'',
			''));
	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_timeout',
			get_string('survey_timeout', 'block_onlinesurvey'),
			'',
			3,
			PARAM_INT));
	$settings->add(
		new admin_setting_configcheckbox(
			'block_onlinesurvey_survey_debug',
			'DEBUG',
			'',
			0));
	$settings->add(
		new admin_setting_configtext(
			'block_onlinesurvey_survey_logfile',
			get_string('survey_logfile','block_onlinesurvey'),
			get_string('survey_logfile_desc', 'block_onlinesurvey'),
			''));
}
