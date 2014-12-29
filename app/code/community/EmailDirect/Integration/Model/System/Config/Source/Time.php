<?php

class EmailDirect_Integration_Model_System_Config_Source_Time
{
   public function toOptionArray()
   {
   	$options = array(
                  array('value' => 15,'label' => "15 Minutes"),
                  array('value' => 30,'label' => "30 Minutes"),
                  array('value' => 45,'label' => "45 Minutes"),
                  array('value' => 60,'label' => "60 Minutes"));
                  
      return $options;
   }
}