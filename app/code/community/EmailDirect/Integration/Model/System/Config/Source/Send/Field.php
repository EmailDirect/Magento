<?php

class EmailDirect_Integration_Model_System_Config_Source_Send_Field extends Varien_Data_Form_Abstract
{
   public function toOptionArray()
   {
      $options = array(
      				array('value' => 'state','label' => "State"),
                  array('value' => 'status','label' => "Status"),
						);
                  
      return $options;
   }
}