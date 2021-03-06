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

/**
 * Grabs and displays the raw data from Evasys.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'username' => ''
    )
);

if (empty($options['username'])) {
    cli_error('You must specify a username.');
}

$user = $DB->get_record('user', array(
    'username' => $options['username']
), '*', MUST_EXIST);
\core\session\manager::set_user($user);


$CFG->block_onlinesurvey_survey_debug = 1;
$ajax = new \block_onlinesurvey\core();
print_r($ajax->get_block_content());