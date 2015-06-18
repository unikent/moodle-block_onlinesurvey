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

/*
 * @package    blocks_onlinesurvey
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module blocks_onlinesurvey/core
  */
define(['jquery', 'core/ajax'], function($, ajax) {
    return {
        init: function() {
        	if ($('#onlinesurvey-text').length <= 0) {
        		return;
        	}

        	$('#onlinesurvey-text').text('Requesting surveys...');

            var promises = ajax.call([{
                methodname: 'blocks_onlinesurvey_get_surveys',
                args: { }
            }]);

            promises[0].done(function(data) {
                $('#onlinesurvey-text').html(data.text);
                if (data.footer !== '') {
                    $('#onlinesurvey-footer').html(data.footer);
                } else {
                    $('#onlinesurvey-footer').parent().hide();
                }
            });

            promises[0].fail(function(ex) {
                $('#onlinesurvey-text').html('Unable to obtain surveys..');
                console.log(ex);
            });
        }
    };
});