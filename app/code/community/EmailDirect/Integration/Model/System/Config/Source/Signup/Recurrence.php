<?php

class EmailDirect_Integration_Model_System_Config_Source_Signup_Recurrence extends Varien_Data_Form_Abstract
{
   public function toOptionArray()
   {
      $options = array(
      				array('value' => 'once','label' => "Only show once"),
                  array('value' => '1 day','label' => "1 day"),
                  array('value' => '1 week','label' => "1 week"),
                  array('value' => '1 month','label' => "1 month"),
						);
                  
      return $options;
   }
}