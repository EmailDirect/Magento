<?php

class EmailDirect_Integration_Block_Widget_Grid_Column_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
   public function render(Varien_Object $row)
	{
		$processed_date = $row->getData('date_sent');
		
		$store_id = $row->getData('store_id');
		
		if (!Mage::helper('emaildirect')->getAbandonedEnabled($store_id))
			return '';
		
		$processed = $processed_date != "";

		$actions = $this->getColumn()->getActions();
		if (empty($actions) || !is_array($actions))
      	return '&nbsp;';

		$out = "";

		foreach ($actions as $action)
		{
			if (is_array($action)) 
			{
				// Change text if cart has already been sent
				if ($action['sent'] == $processed)
				{
					if ($out != "")
						$out .= "<br />";
					
					$out .= $this->_toLinkHtml($action, $row);
				}
			}
		}
  		return $out;
	}
}