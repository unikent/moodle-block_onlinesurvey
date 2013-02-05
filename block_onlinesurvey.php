<?php

define('SURVEY_URL', 'indexstud.php?type=html&user_tan=');

class block_onlinesurvey extends block_base {

	private $isDebug;
	private $isConfigured;
	private $logFileHandle = NULL;
	private $guiError;

	private $surveyLogin;

	private $soap_wsdlUrl;
	private $soap_user;
	private $soap_password;
	private $soap_timeout;
	private $soap_namespace;

	private $moodle_userId;
	private $moodle_username;
	private $moodle_email;

	function init()
	{
		global $CFG;

		$this->title = get_string('pluginname', 'block_onlinesurvey');

		if(isset($CFG))
		{
			# Block settings
			$this->surveyLogin = $CFG->block_onlinesurvey_survey_login;

			$this->soap_wsdlUrl = $CFG->block_onlinesurvey_survey_server;
			$this->soap_user = $CFG->block_onlinesurvey_survey_user;
			$this->soap_password = $CFG->block_onlinesurvey_survey_pwd;
			$this->soap_timeout = $CFG->block_onlinesurvey_survey_timeout;
			if(!$this->soap_timeout)
			{
				$this->soap_timeout = 3;
			}


			$this->isDebug = $CFG->block_onlinesurvey_survey_debug == 1;
			$logFilePath = $CFG->block_onlinesurvey_survey_logfile;

			if($this->isDebug)
			{
				try
				{
					$this->logFileHandle = fopen($logFilePath, "a");
				}
				catch(Exception$e)
				{ }
			}

			# Session information
			global $USER;
			if($this->moodle_userId = $USER->id)
			{
				$this->moodle_username = $USER->username;
				$this->moodle_email = $USER->email;

				$this->logMsg("Block initialization completed");

				# Parse namespace from the wsdl url
				preg_match('/\/([^\/]+\.wsdl)$/', $this->soap_wsdlUrl,
					$matches);

				if(count($matches) == 2)
				{
					$this->soap_namespace = $matches[1];
					$this->isConfigured = True;
				}
				else
				{
					$this->isConfigured = False;
					$this->handleError("WSDL namespace parse error");
				}
			}
			else
			{
				$this->isConfigured = False;
				$this->handleError("User ID not found");
			}
		}
		else
		{
			$this->handleError("Configuration not accessible");
			$this->isConfigured = False;
		}
	}

	function get_content()
	{
		global $SESSION;
		if(!empty($this->content))
		{
			return $this->content;
		}

		if($this->moodle_userId && $this->isConfigured)
		{
			$this->logMsg("Validating configuration");
			$validationResult = $this->validateConfig();
			if(!$validationResult[0])
			{
				# Configuration error
				$this->handleError($validationResult);
			}
			else
			{
				$this->content = new stdClass;

				$this->content->footer = get_string('copyright',
					'block_onlinesurvey');

				if(!isset($SESSION->ep_surveyKeys) || $this->isDebug)
				{
					$this->logMsg("Fetching surveys for '" .
						$this->moodle_email . "'");
					$SESSION->ep_surveyKeys = $this->getSurveys();
				}

				if($SESSION->ep_surveyKeys === False && !$this->isDebug)
				{
					$this->content->text =
						get_string('no_surveys', 'block_onlinesurvey');
					return;
				}

				if($this->moodle_username == "admin")
				{
					if(!$this->guiError)
					{
						$this->content->text =
							get_string('conn_works', 'block_onlinesurvey');
						$this->logMsg("Connection test successful");
					}
				}
				elseif(is_object($SESSION->ep_surveyKeys))
				{
					if(!is_array($SESSION->ep_surveyKeys->OnlineSurveyKeys))
					{
						$SESSION->ep_surveyKeys->OnlineSurveyKeys = array(
							$SESSION->ep_surveyKeys->OnlineSurveyKeys,
						);
					}
					$surveyCount = count(
						$SESSION->ep_surveyKeys->OnlineSurveyKeys);
					$this->logMsg("Found $surveyCount surveys");
					if($surveyCount)
					{
						$list = '';

						foreach($SESSION->ep_surveyKeys->OnlineSurveyKeys as
							$onlineSurveyKey)
						{

							$moduleCode = "";
							if(isset($onlineSurveyKey->Instructor->LastName) && trim($onlineSurveyKey->Instructor->LastName) != ""){
								$moduleCode = " (".trim($onlineSurveyKey->Instructor->LastName).")";
							}
							
							$list .= "<li><a href='" .
								$this->surveyLogin .
								SURVEY_URL .
								$onlineSurveyKey->TransactionNumber .
								"' target='_blank'>" .
								$onlineSurveyKey->CourseName . $moduleCode .
								"</a></li>";
						}

						$instructions = get_string('survey_instructions', 'block_onlinesurvey');
						$this->content->text = "<p>{$instructions}</p><ul class='list'>" . $list . "</ul>";
					}
				}

				if($this->isDebug)
				{
					fclose($this->logFileHandle);
				}
			}
		}
		if($this->isDebug && $this->guiError)
		{
			$this->content->text = "<b>An error has occured:</b>:<br />'" .
				$this->guiError . "'";
		}
	}

	function getSurveys()
	{
		# Client settings
		ini_set('soap.wsdl_cache', 0);
		ini_set('soap.wsdl_cache_enabled', 0);
		ini_set('default_socket_timeout', $this->soap_timeout);

		try
		{
			$client = new SoapClient( $this->soap_wsdlUrl,
				array(
					'trace' => 1,
					'feature' => SOAP_SINGLE_ELEMENT_ARRAYS)
				);

			$header_input = array(
				'Login' => $this->soap_user,
				'Password' => $this->soap_password,
			);

			if(is_object($client))
			{
				$soapHeaders = new SoapHeader($this->soap_namespace,
					'Header', $header_input);

				$client->__setSoapHeaders($soapHeaders);
			}
			else
			{
				handleError("SOAP client configuration error");
				return False;
			}

			return $client->GetPswdsByParticipant(
				$this->moodle_email);
		}
		catch(Exception $e)
		{
			$this->handleError($e);
			return False;
		}
	}

	function validateConfig()
	{
		if(!$this->checkUrl($this->soap_wsdlUrl))
		{
			return Array(False, "Invalid WSDL URL");
		}

		if(!$this->checkUrl($this->surveyLogin . 'index.php'))
		{
			return Array(False, "Invalid survey login address");
		}

		return Array(True, "No configuration errors found");
	}

	private function checkUrl($p_url)
	{
		if ($parsedUrl = parse_url($p_url))
		{
			$fileHandle = fsockopen($parsedUrl['host'], 80);

			if($fileHandle)
			{
				$httpMessage = "GET {$parsedUrl['path']} HTTP/1.1\r\n";
				$httpMessage .= "Host: {$parsedUrl['host']}\r\n";
				$httpMessage .= "Connection: Close\r\n\r\n";
				fwrite($fileHandle, $httpMessage);

				$httpResponse = "";
				while (!feof($fileHandle))
				{
					$httpResponse .= fgets($fileHandle, 128);
				}
				fclose($fileHandle);

				$httpPattern = "/HTTP\/1\.\d\s(\d+)/";

				return(preg_match($httpPattern, $httpResponse, $matches) &&
					($matches[1] == 200 || $matches[1] == 302));
			}
			else
			{
				return False;
			}
		}
		else
		{
			return False;
		}
	}

	function handleError($err)
	{
		if(is_array($err))
		{
			# Configuration validation error
			if(!$err[0])
			{
				$this->logError($err[1]);
			}
		}
		elseif(is_string($err))
		{
			# Simple error message
			$this->logError($err);
		}
		else
		{
			# Error should be an exception
			$this->logError($this->ppExc($err));
		}
	}

	function has_config()
	{
		return True;
	}

	function config_save($data)
	{
		foreach($data as $name => $value)
			set_config($name, $value);
		return True;
	}

	function instance_allow_multiple()
	{
		return False;
	}

	function hide_header()
	{
		return False;
	}

	function applicable_formats()
	{
		if( has_capability('moodle/site:config',
			get_context_instance(CONTEXT_SYSTEM)))
		{
			return array('all' => true);
		}
		else
		{
			return array('all' => false);
		}
	}

	# Pretty-prints exceptions
	function ppExc($e)
	{
		$msg = '';
		if(get_class($e) == "SoapFault")
		{
			$msg .= $e->faultstring;
			$msg .= ": ";
			$msg .= $e->detail;
		}
		else
		{
			$msg .= $e->getMessage();
		}
		return $msg;
	}

	function logError($errMsg)
	{
		$this->guiError = $errMsg;
		$this->logMsg($errMsg);
	}

	function logMsg($msg = '')
	{

		if($this->logFileHandle)
		{
			fwrite($this->logFileHandle, strftime("%b %d %H:%M:%S", time()) .
				" - " . $msg . "\n" );
		}
	}


  function _self_test() {
    return true;
  }
}
