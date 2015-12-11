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
 * Evasys Block
 *
 * @package    block_onlinesurvey
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_onlinesurvey;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;

/**
 * Evasys web services
 */
class services extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_surveys_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Search a list of modules.
     *
     * @param string $component Limit the search to a component.
     * @param string $search The search string.
     * @return array[string]
     */
    public static function get_surveys() {
        global $USER;

        $core = new core();
        $content = $core->get_block_content();

        return array(
            'text' => $content->text,
            'footer' => $content->footer
        );
    }

    /**
     * Returns description of get_surveys() result value.
     *
     * @return external_description
     */
    public static function get_surveys_returns() {
        return new external_single_structure(
            array(
                'text' => new external_value(PARAM_RAW, 'Block HTML'),
                'footer' => new external_value(PARAM_RAW, 'Block Footer')
            )
        );
    }
}