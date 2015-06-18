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
 * An online survey block
 */
class block_onlinesurvey extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_onlinesurvey');
    }

    /**
     * Required JS
     */
    public function get_required_javascript() {
        parent::get_required_javascript();

        // Include our custom JS.
        $this->page->requires->js_call_amd('block_onlinesurvey/core', 'init', array());
    }

    /**
     * Parent class version of this function simply returns NULL
     * This should be implemented by the derived class to return
     * the content object.
     *
     * @return stdObject
     */
    public function get_content() {
        global $USER;

        if (isset($this->content)) {
            return $this->content;
        }

        $cache = \cache::make('block_onlinesurvey', 'soapdata');
        $this->content = $cache->get('os_' . $USER->id);
        if ($this->content) {
            return $this->content;
        }

        $this->content = new \stdClass();
        $this->content->text = '<div id="onlinesurvey-text">Requesting surveys</div>';
        $this->content->footer = '<div id="onlinesurvey-footer"></div>';
        return $this->content;
    }

    /**
     * Subclasses should override this and return true if the
     * subclass block has a settings.php file.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Are you going to allow multiple instances of each block?
     * If yes, then it is assumed that the block WILL USE per-instance configuration
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Returns the role that best describes the navigation block... 'navigation'
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }

    /**
     * locations where block can be displayed
     * Moodle override.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'my' => true
        );
    }
}
