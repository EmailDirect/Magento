<?php

class EmailDirect_Integration_Model_Wrapper_Ftp
{
   public function upload($url,$name, $folder = "importFiles")
   {
      $xml = "<FileUpload>";
  		$xml .= "<URL>{$url}</URL>";
		$xml .= "<FileName>{$name}</FileName>";
  		$xml .= "<FolderPath>{$folder}</FolderPath>";
		$xml .= "</FileUpload>";
		
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("ftp","","",$xml);
      
      return $rc;
   }
}