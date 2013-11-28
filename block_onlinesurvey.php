<?php
class block_onlinesurvey extends block_base {
    const SURVEY_URL = 'indexstud.php?type=html&user_tan=';

    public function init() {
        $this->title = get_string('pluginname', 'block_onlinesurvey');
    }

    public function get_content() {
        global $PAGE;
        $this->content = new stdClass();
        $this->content->text = '<div id="onlinesurvey-text">Requesting surveys</div>';
        $this->content->footer = '<div id="onlinesurvey-footer"></div>';
        $PAGE->requires->js('/blocks/onlinesurvey/js/onlinesurvey.js');
        return $this->content;
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

    public function has_config() {
        return true;
    }

    public function config_save($data) {
        foreach ($data as $name => $value) {
            set_config($name, $value);
        }

        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function hide_header() {
        return false;
    }

    public function applicable_formats() {
        $context = context_system::instance();
        if (has_capability('moodle/site:config', $context)) {
            return array('all' => true);
        } else {
            return array(
                'all' => false,
                'admin' => true
            );
        }
    }

    private function pretty_print_exceptions($e) {
        $msg = '';
        if (get_class($e) == "SoapFault") {
            $msg = "{$e->faultstring}: {$e->detail}";
        } else {
            $msg = $e->getMessage();
        }

        return $msg;
    }

    // Kent change -- Add support for CLI calling
    function _self_test() {
        return true;
    }

    // End change
}
