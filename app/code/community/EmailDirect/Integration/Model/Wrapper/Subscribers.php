<?php

class EmailDirect_Integration_Model_Wrapper_subscribers extends EmailDirect_Integration_Model_Wrapper_Abstract
{
   public function subscriberModify($email,$merge_vars)
   {
      $source_data = $this->getSource($email);
		$custom_fields = $this->getCustomFields($merge_vars);
		$list_data = $this->getLists();
		$publication_data = $this->getPublications();
      
		$xml = "<Subscriber><EmailAddress>{$email}</EmailAddress>{$custom_fields}{$source_data}{$publication_data}{$list_data}</Subscriber>";
      
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","",$email,$xml,"PUT");
      if(isset($rc->ErrorCode))
         return false;
      
      return true;
   }
	
   public function subscriberAdd($email,$merge_vars, $extra_data = "", $subscribe = true)
   {
      $publication_data = "";
		$list_data = "";
		
		$source_data = $this->getSource($email);
		
		if ($subscribe)
		{
			$publication_data = $this->getPublications();
      	$list_data = $this->getLists();
		}
		
      $custom_fields = $this->getCustomFields($merge_vars);
      
      $xml = "<Subscriber><EmailAddress>{$email}</EmailAddress>{$custom_fields}{$source_data}{$extra_data}{$publication_data}{$list_data}<Force>true</Force></Subscriber>";
		
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","","",$xml);
		
		return $rc;
   }
	
	public function subscriberDelete($email)
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers",null,$email,null,"DELETE");
      return $rc;
   }
   
   private function fixBouncedMail($old_mail,$new_mail)
   {
      $xml = "<Subscriber><EmailAddress>{$old_mail}</EmailAddress><Force>true</Force></Subscriber>";
      
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","","",$xml);
      
      if (isset($rc->ErrorCode))
      {
         Mage::getSingleton('customer/session')->addError((string)$rc->Message);
         Mage::throwException((string)$rc->Message);
      }
      
      $xml = "<Subscriber><EmailAddress>{$new_mail}</EmailAddress></Subscriber>";
      
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","ChangeEmail",$old_mail,$xml);
      
      if (isset($rc->ErrorCode))
      {
         Mage::getSingleton('customer/session')->addError((string)$rc->Message);
         Mage::throwException((string)$rc->Message);
      }
		
		return $rc;
   }
   
   public function mailModify($old_mail,$new_mail)
   {
      $xml = "<Subscriber><EmailAddress>{$new_mail}</EmailAddress></Subscriber>";
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","ChangeEmail",$old_mail,$xml);
      
      if (isset($rc->ErrorCode))
      {
         if ($rc->ErrorCode == 202)
            return $this->fixBouncedMail($old_mail, $new_mail);
			else if ($rc->ErrorCode == 200)
				return false;
         else 
         {
            Mage::getSingleton('customer/session')->addError((string)$rc->Message);
            Mage::throwException((string)$rc->Message);
         }
      }
      
      return $rc;
   }

   public function getProperties($email)
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","Properties",$email);
      return $rc;
   }
	
	public function subscriberExists($email)
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("subscribers","",$email);
		
		if (isset($rc->ErrorCode))
      	return false;
		
      return true;
   }
   
   public function sendLastLogin($email, $date)
   {
   	$merge_vars = array('LastLogin' => $date);
		
		$rc = $this->subscriberAdd($email, $merge_vars, "", false);
		
		if (isset($rc->ErrorCode))
      {
         Mage::getSingleton('customer/session')->addError((string)$rc->Message);
         Mage::throwException((string)$rc->Message);
		}
	}
}