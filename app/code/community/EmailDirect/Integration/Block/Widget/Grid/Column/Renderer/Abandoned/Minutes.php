<?php
class EmailDirect_Integration_Block_Widget_Grid_Column_Renderer_Abandoned_Minutes extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract 
{
	public function render(Varien_Object $row)
	{
		$processed_date = $row->getData('date_sent');
		
		if ($processed_date != "")
			return "";
		
		$abandoned_date = strtotime($row->getData('updated_at'));
	
		$current_date = Mage::getModel('core/date')->gmtTimestamp();
		
		$minutes = round(abs($current_date - $abandoned_date) / 60,0);
		
		$class = 'ab_ok';
		if ($minutes > 60)
			$class = 'ab_ng';
	
		return "<span class='{$class}'>{$minutes}</span>";
	}
}