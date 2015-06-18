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

/**
 * Evasys core
 */
class core
{
    public function get_block_content() {
        global $CFG, $USER;

        // Setup content.
        $content = new \stdClass();
        $content->text = '';
        $content->footer = '';

        if (!isloggedin()) {
            return $content;
        }

        // Build the cache.
        $cache = \cache::make('block_onlinesurvey', 'soapdata');

        // Get the keys!
        $keys = $this->get_surveys();
        if (!$keys) {
            $content->text = get_string('no_surveys', 'block_onlinesurvey');
            $cache->set('os_' . $USER->id, $content);
            return $content;
        }

        $count = count($keys->OnlineSurveyKeys);
        if ($count) {
            $list = '';
            foreach ($keys->OnlineSurveyKeys as $surveykey) {
                $list .= "<li><a href=\"{$CFG->block_onlinesurvey_survey_login}indexstud.php?type=html&user_tan={$surveykey->TransactionNumber}\" target=\"_blank\">";
                $list .= "{$surveykey->CourseName}</a></li>";
            }
            $instructions = get_string('survey_instructions', 'block_onlinesurvey');
            $content->text = "<p>{$instructions}</p><ul class='list'>" . $list . "</ul>";
        }

        $cache->set('os_' . $USER->id, $content);

        return $content;
    }

    private function get_surveys() {
        global $CFG, $USER;

        // Parse wsdlnamespace from the wsdl url.
        preg_match('/\/([^\/]+\.wsdl)$/', $CFG->block_onlinesurvey_survey_server, $matches);
        if (count($matches) !== 2) {
            throw new \moodle_exception("WSDL namespace parse error");
        }
        $wsdlnamespace = $matches[1];

        $client = new onlinesurvey_soap_client($CFG->block_onlinesurvey_survey_server, array(
            'trace' => $CFG->block_onlinesurvey_survey_debug ? 1 : 0,
            'feature' => \SOAP_SINGLE_ELEMENT_ARRAYS,
            'connection_timeout' => max(1, round($CFG->block_onlinesurvey_survey_timeout / 1000)),
            'cache_wsdl' => $CFG->block_onlinesurvey_survey_debug ? \WSDL_CACHE_NONE : \WSDL_CACHE_MEMORY
        ));

        if (!is_object($client)) {
            throw new \moodle_exception("SOAP client configuration error");
        }

        $client->__setSoapHeaders(new \SoapHeader($wsdlnamespace, 'Header', array(
            'Login' => $CFG->block_onlinesurvey_survey_user,
            'Password' => $CFG->block_onlinesurvey_survey_pwd
        )));

        try {
            $keys = $client->GetPswdsByParticipant($USER->email);
        } catch (\SoapFault $e) {
            if ($e->faultstring == 'ERR_102') {
                debugging("IP disallowed.");
            }

            if ($e->faultstring == 'ERR_206') {
                return null;
            }

            throw $e;
        }

        if (!is_object($keys) || empty($keys->OnlineSurveyKeys)) {
            return null;
        }

        if (!is_array($keys->OnlineSurveyKeys)) {
            $keys->OnlineSurveyKeys = array(
                $keys->OnlineSurveyKeys
            );
        }

        return $keys;
    }
}