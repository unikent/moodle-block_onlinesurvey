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
        $this->page->requires->js('/blocks/onlinesurvey/js/onlinesurvey.js');
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

        $cache = \cache::make('block_onlinesurvey', 'onlinesurvey');
        $this->content = $cache->get('os_' . $USER->id);
        if ($this->content && $this->content->timeout > time()) {
            return $this->content;
        }

        $this->content = new stdClass();
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
     * Default return is false - header will be shown
     * @return boolean
     */
    public function hide_header() {
        return false;
    }

    /**
     * Which page types this block may appear on.
     *
     * The information returned here is processed by the
     * {@link blocks_name_allowed_in_format()} function. Look there if you need
     * to know exactly how this works.
     *
     * Default case: everything except mod and tag.
     *
     * @return array page-type prefix => true/false.
     */
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
}
