<?php

class EmailDirect_Integration_Model_Wrapper_Execute
{
	public function __construct()
	{
		$this->_helper = Mage::helper('emaildirect');
		$this->_logger = Mage::helper('emaildirect/troubleshooting');
	}
	
	private function parseHttpResponse($str)
	{
		try
		{
			$parts = explode(PHP_EOL,$str,2);
		
			$http_parts = explode(' ',$parts[0],3);
		
			return array("code" => $http_parts[1], "msg" => $http_parts[2]);
		}
		catch (Exception $e)
		{
			$this->_logger->log($e->getMessage(),"Failed to parse HTTP Response");
			$this->_logger->log($str,"HTTP Response");
			
			return array("code" => "0", "msg" => "Failed to parse HTTP Response");
		}
	}

	public function sendCommand($command, $subcommand = null, $id= null, $xmldata=null, $method = "POST")
	{
		$debug = $this->_logger->isDebugMode();
		
		if ($debug)
		{
			$this->_logger->setDebugRequest($xmldata);
			
			if ($this->_logger->getDebugExecuteMode() == 'request_only')
				return $xmldata;
		}
		
		if (!$this->_helper->canEdirect())
		{
			$strxml = "<Response><ErrorCode>0</ErrorCode><Message>EmailDirect not enabled</Message></Response>";
			return simplexml_load_string($strxml);
		}
		
		$apikey = $this->_helper->getApiKey();
		
		$response = $this->sendCommandDirect($apikey, $command, $subcommand,$id,$xmldata,$method);
		
		if ($debug)
		{
			$this->_logger->setDebugResponse($response->saveXml());
		}
		return $response;
	}

	public function sendCommandDirect($apikey, $command, $subcommand = null, $id= null, $xmldata=null, $method = "POST")
	{
		
		if (!$apikey || $apikey == "")
		{
			$strxml = "<Response><ErrorCode>0</ErrorCode><Message>Invalid or Missing APIKey</Message></Response>";
			return simplexml_load_string($strxml);
		}
		
		if ($xmldata != null)
			$this->_logger->logXml($xmldata,"Xml Data ({$command})");
		
		$URL = $this->_helper->config('urls/accesspoint');
		$urlsuffix = $this->_helper->config("urls/{$command}");
		$URL .= $urlsuffix;
		if ($id)
			$URL .= "/$id";
		if ($subcommand)
			$URL .= "/$subcommand";
		
		$this->_logger->log("API KEY: {$apikey}");
		$this->_logger->log("URL: {$URL}");
		
		$header = array('Content-Type: text/xml','ApiKey: '.$apikey,'Accept: application/xml');
		$ch = curl_init($URL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		
		switch ($method)
		{
			case "DELETE":
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			} break;
			case "POST":
			{
				curl_setopt($ch, CURLOPT_POST, 0);
				if($xmldata)
					curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);
			} break;
			default:
			{
				$putString = stripslashes($xmldata);
				$putData = tmpfile();
				fwrite($putData, $putString);
				fseek($putData, 0);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_INFILE, $putData);
				curl_setopt($ch, CURLOPT_INFILESIZE, strlen($putString));
			} break;
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		
		curl_close($ch);
		$start = strpos($output,'<?xml');
		if(!$start)
		{
			$start = strpos($output,'<Response>');
		}
		if (!$start)
		{
			$results = $this->parseHttpResponse($output);
			$strxml = "<Response><ErrorCode>{$results['code']}</ErrorCode><Message>{$results['msg']}</Message></Response>";
		}
		else
			$strxml = substr($output,$start);
		
		try
		{
			$xml = simplexml_load_string($strxml);
		}
		catch(Exception $e)
		{
			Mage::throwException($e->getMessage());
		}
		
		$this->_logger->log($xml, "Response");
		
		return $xml;
	}
}
