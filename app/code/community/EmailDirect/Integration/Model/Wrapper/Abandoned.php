<?php
class EmailDirect_Integration_Model_Wrapper_Abandoned extends EmailDirect_Integration_Model_Wrapper_Abstract 
{
   public function sendSubscribers($xml)
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("abandoned","","",$xml);
		return $rc;
   }
   
   public function getOneSubscriber($email,$merge_vars)
   {
      $source_data = $this->getSource($email);
      $publication_data = $this->getPublications("abandonedpublication");
      $custom_fields = $this->getCustomFields($merge_vars);
      $list_data = $this->getLists("abandonedlist");
      
      $xml = "<Subscriber><EmailAddress>{$email}</EmailAddress>{$custom_fields}{$source_data}{$publication_data}{$list_data}<Force>true</Force></Subscriber>";
		
      return $xml;
   }
}
