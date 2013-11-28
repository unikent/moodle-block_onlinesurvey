<?php
class onlinesurvey_soap_client extends SoapClient {
    public $timeout;
    public $debugmode;
    public $haswarning = false;
    public $warnmessage = "";

    public function __construct($wsdl, $options, $timeout = 15, $debug = false) {
        $this->debugmode = $debug;
        $this->timeout = $timeout;

        // Kent Change: Caching. On error we wait 240 seconds before trying again
        $cache = cache::make('block_onlinesurvey', 'onlinesurvey');
        $uri = $cache->get('WSDLURI');
        if ($uri === false || (is_array($uri) && $uri['error'] + 240 < time("now"))) {
        // End Kent Change
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $wsdl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);

            $wsdlxml = curl_exec($ch);
            if (!$wsdlxml) {
                $cache->set('WSDLURI', array("error" => time("now")));
                throw new Exception('ERROR: Could not fetch WSDL');
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
            throw new Exception('ERROR: Could not fetch WSDL');
        }

        parent::__construct($uri, $options);
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
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
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
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