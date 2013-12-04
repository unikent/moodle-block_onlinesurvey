<?php
/**
 * Kent Change: Add caching to WSDL
 */
$definitions = array(
    'onlinesurvey' => array(
        'mode' => cache_store::MODE_APPLICATION
    ),
    'onlinesurvey_session' => array(
        'mode' => cache_store::MODE_SESSION,
        'ttl' => 900
    )
);