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

defined('MOODLE_INTERNAL') || die();

/**
 * Online Survey AJAX class
 */
class onlinesurvey_ajax {
    private $wsdlnamespace;

    public function __construct() {
        global $CFG, $USER;

        // Parse wsdlnamespace from the wsdl url.
        preg_match('/\/([^\/]+\.wsdl)$/', $CFG->block_onlinesurvey_survey_server, $matches);
        if (count($matches) !== 2) {
            throw new \moodle_exception("WSDL namespace parse error");
        }

        $this->wsdlnamespace = $matches[1];
    }

    public function get_content() {
        global $CFG, $USER;

        // Setup content.
        $content = new \stdClass();
        $content->text = '';
        $content->footer = '';

        if (!isloggedin()) {
            return $content;
        }

        $cache = \cache::make('block_onlinesurvey', 'soapdata');
        $content->timeout = time() + 1800;

        // Get the keys!
        $keys = $this->get_surveys();
        if (!is_object($keys) || empty($keys->OnlineSurveyKeys)) {
            $keys = false;
        }

        // No keys, set cache and let the user know.
        if ($keys === false) {
            $content->text = get_string('no_surveys', 'block_onlinesurvey');
            $cache->set('os_' . $USER->id, $content);
            return $content;
        }

        if (!is_array($keys->OnlineSurveyKeys)) {
            $keys->OnlineSurveyKeys = array(
                $keys->OnlineSurveyKeys
            );
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

        $client = new \block_onlinesurvey\onlinesurvey_soap_client($CFG->block_onlinesurvey_survey_server, array(
            'trace' => $CFG->block_onlinesurvey_survey_debug ? 1 : 0,
            'feature' => \SOAP_SINGLE_ELEMENT_ARRAYS,
            'connection_timeout' => max(1, round($CFG->block_onlinesurvey_survey_timeout / 1000)),
            'cache_wsdl' => $CFG->block_onlinesurvey_survey_debug ? \WSDL_CACHE_NONE : \WSDL_CACHE_MEMORY
        ));

        if (!is_object($client)) {
            throw new \moodle_exception("SOAP client configuration error");
        }

        $client->__setSoapHeaders(new \SoapHeader($this->wsdlnamespace, 'Header', array(
            'Login' => $CFG->block_onlinesurvey_survey_user,
            'Password' => $CFG->block_onlinesurvey_survey_pwd
        )));

        return $client->GetPswdsByParticipant($USER->email);
    }
}
