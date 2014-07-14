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
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_onlinesurvey;

defined('MOODLE_INTERNAL') || die();

/**
 * Evasys SOAP client
 */
class onlinesurvey_soap_client extends \SoapClient
{
    public $debugmode;
    public $haswarning = false;
    public $warnmessage = "";

    /**
     * Construct the SOAP Client
     */
    public function __construct($wsdl, $options, $debug = false) {
        global $CFG;

        $this->debugmode = $debug;

        $cache = \cache::make('block_onlinesurvey', 'onlinesurvey');
        $uri = $cache->get('WSDLURI');
        if ($uri === false || (is_array($uri) && $uri['error'] + 240 < time("now"))) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL             => $wsdl,
                CURLOPT_HEADER          => false,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CONNECTTIMEOUT  => $CFG->block_onlinesurvey_survey_timeout,
                CURLOPT_TIMEOUT         => $CFG->block_onlinesurvey_survey_timeout,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1
            ));

            $wsdlxml = curl_exec($ch);
            if (!$wsdlxml) {
                $cache->set('WSDLURI', array("error" => time("now")));
                throw new \Exception('ERROR: Could not fetch WSDL');
            }

            $url = parse_url($wsdl);
            if (is_array($url)) {
                $urlserveraddress = $url['host'];
            }

            preg_match('/<soap:address location="https*:\/\/([0-9a-z\.\-_]+)/i', $wsdlxml, $match);
            if (count($match) == 2) {
                $wsdlserveraddress = $match[1];
            }

            if ($urlserveraddress != $wsdlserveraddress AND $debug) {
                $this->haswarning = true;
                $this->warnmessage = "WSDL endpoint setting might not be correct.
                        URL: $urlserveraddress,
                        Endpoint address: $wsdlserveraddress.";
            }

            $base64 = base64_encode($wsdlxml);
            $uri = "data:application/wsdl+xml;base64,$base64";

            $cache->set('WSDLURI', $uri);
        }

        if (is_array($uri)) {
            throw new \Exception('ERROR: Could not fetch WSDL');
        }

        parent::__construct($uri, $options);
    }

    /**
     * Override the doRequest thing
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
        global $CFG;

        $headers = array(
            'Content-Type: text/xml;charset=UTF-8',
            "SOAPAction: \"$action\"",
            'Content-Length: ' . strlen($request)
        );

        $ch = curl_init();

        // Set the url, number of POST vars, POST data.
        curl_setopt($ch, CURLOPT_URL, $location);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $CFG->block_onlinesurvey_survey_timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute post.
        $ret = curl_exec($ch);
        if (!$ret) {
            $ret = '<SOAP-ENV:Envelope
                    xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                        <SOAP-ENV:Body>
                            <SOAP-ENV:Fault>
                                <faultcode>SOAP-ENV:Server</faultcode>
                                <faultstring></faultstring>
                                <faultactor/>
                                <detail>' . curl_error($ch) . '</detail>
                            </SOAP-ENV:Fault>
                        </SOAP-ENV:Body>
                    </SOAP-ENV:Envelope>';
        }
        curl_close($ch);

        return $ret;
    }
}
