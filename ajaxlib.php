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
    const SURVEY_URL = 'indexstud.php?type=html&user_tan=';

    private $debugmode;
    private $isconfigured;
    private $warning;
    private $error;
    private $connectionok;

    private $surveyurl;

    private $wsdl;
    private $soapuser;
    private $soappassword;
    private $timeout;
    private $wsdlnamespace;

    private $moodleuserid;
    private $moodleusername;
    private $moodleemail;

    public function __construct() {
        global $CFG, $USER;

        $this->connectionok = false;

        // Block settings.
        $this->debugmode = $CFG->block_onlinesurvey_survey_debug == 1;
        $this->surveyurl = $CFG->block_onlinesurvey_survey_login;
        $this->wsdl = $CFG->block_onlinesurvey_survey_server;
        $this->soapuser = $CFG->block_onlinesurvey_survey_user;
        $this->soappassword = $CFG->block_onlinesurvey_survey_pwd;
        $this->timeout = $CFG->block_onlinesurvey_survey_timeout;

        // Session information.
        if ($this->moodleuserid = $USER->id) {
            $this->moodleusername = $USER->username;
            $this->moodleemail = $USER->email;

            // Parse wsdlnamespace from the wsdl url.
            preg_match('/\/([^\/]+\.wsdl)$/', $this->wsdl, $matches);

            if (count($matches) == 2) {
                $this->wsdlnamespace = $matches[1];
                $this->isconfigured = true;
            } else {
                $this->isconfigured = false;
                $this->handle_error("WSDL namespace parse error");
            }
        } else {
            $this->isconfigured = false;
            $this->handle_error("User ID not found");
        }
    }

    public function get_content() {
        global $USER;

        $cache = \cache::make('block_onlinesurvey', 'onlinesurvey');
        $content = $cache->get('os_' . $USER->id);
        if ($content && $content->timeout > time()) {
            return $content;
        }

        // Setup content.
        $content = new stdClass();
        $content->timeout = time() + 1800;
        $content->text = '';
        $content->footer = '';

        // Should we be trying this?
        if ($this->moodleuserid && $this->isconfigured) {
            $keys = $this->get_surveys();
            if (!is_object($keys) || empty($keys->OnlineSurveyKeys)) {
                $keys = false;
            }

            // No keys, set cache and let the user know.
            if ($keys === false) {
                $content->text = get_string('no_surveys', 'block_onlinesurvey');

                if ($this->debugmode && has_capability('moodle/site:config', context_system::instance())) {
                    if ($this->error) {
                        $content->text = "<b>An error has occured:</b><br />{$this->error}<br />" . $content->text;
                    } else if ($this->warning) {
                        $content->text = "<b>Warning:</b><br />{$this->warning}<hr />" . $content->text;
                    } else if ($this->connectionok) {
                        $content->text = get_string('conn_works', 'block_onlinesurvey');
                    }
                }

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
                    $list .= "<li><a href=\"{$this->surveyurl}" . self::SURVEY_URL .
                             "{$surveykey->TransactionNumber}\" target=\"_blank\">".
                             $surveykey->CourseName .
                             "</a></li>";
                }
                $instructions = get_string('survey_instructions', 'block_onlinesurvey');
                $content->text = "<p>{$instructions}</p><ul class='list'>" . $list . "</ul>";
            }
        }

        $cache->set('os_' . $USER->id, $content);

        return $content;
    }

    private function get_surveys() {
        try {
            $client = new \block_onlinesurvey\onlinesurvey_soap_client( $this->wsdl,
                array (
                    'trace' => $this->debugmode ? 1 : 0,
                    'feature' => SOAP_SINGLE_ELEMENT_ARRAYS,
                    'connection_timeout' => max(1, round($this->timeout / 1000)),
                    'cache_wsdl' => $this->debugmode ? WSDL_CACHE_NONE : WSDL_CACHE_MEMORY
                ),
                $this->debugmode
            );

            $header = array(
                'Login' => $this->soapuser,
                'Password' => $this->soappassword
            );

            if (is_object($client)) {
                if ($client->haswarning) {
                    $this->warning = $client->warnmessage;
                }

                $soapheader = new SoapHeader($this->wsdlnamespace, 'Header', $header);
                $client->__setSoapHeaders($soapheader);
            } else {
                $this->handle_error("SOAP client configuration error");
                return false;
            }

            $this->client = $client;
            $this->connectionok = true;
            return $client->GetPswdsByParticipant($this->moodleemail);
        } catch (Exception $e) {
            $this->handle_error($e);
            return false;
        }
    }

    private function handle_error($err) {
        if (is_array($err)) {
            // Configuration validation error.
            if (!$err[0]) {
                $this->error = $err[1];
            }
        } else if (is_string($err)) {
            // Simple error message.
            $this->error = $err;
        } else {
            // Error should be an exception.
            $this->error = $this->pretty_print_exceptions($err);
        }
    }

    private function pretty_print_exceptions($e) {
        $msg = '';
        if (get_class($e) == "SoapFault") {
            $msg = "{$e->faultstring}: " . $e->getMessage();
        } else {
            $msg = $e->getMessage();
        }

        return $msg;
    }
}
