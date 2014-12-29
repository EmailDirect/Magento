<?php

class EmailDirect_Integration_Model_System_Config_Source_Signup_Opacity extends Varien_Data_Form_Abstract
{
   public function toOptionArray()
   {
   	$options = array();
		
		for($i = 1; $i < 10; $i++)
		{
			$percent = $i * 10;
			$options[] = array('value' => $percent, 'label' => "{$percent}%");
		}
		
      return $options;
   }
}