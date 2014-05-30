<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(
        new admin_setting_configtext(
            'block_onlinesurvey_survey_server', // Setting name.
            get_string('survey_server', 'block_onlinesurvey'), // Short name.
            '', // Long description.
            ''
        )
    ); // Default value.

    $settings->add(
        new admin_setting_configtext(
            'block_onlinesurvey_survey_login',
            get_string('survey_login', 'block_onlinesurvey'),
            '',
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'block_onlinesurvey_survey_user',
            get_string('survey_user', 'block_onlinesurvey'),
            '',
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'block_onlinesurvey_survey_pwd',
            get_string('survey_pwd', 'block_onlinesurvey'),
            '',
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'block_onlinesurvey_survey_timeout',
            get_string('survey_timeout', 'block_onlinesurvey'),
            '',
            3000,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'block_onlinesurvey_survey_debug',
            'DEBUG',
            '',
            0
        )
    );
}
