<?php

class EmailDirect_Integration_Block_Adminhtml_Troubleshooting_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		$this->_blockGroup = "emaildirect";
		$this->_controller  = 'adminhtml_troubleshooting';
		$this->_mode        = 'view';

		parent::__construct();
		
		$this->_removeButton('delete');
		$this->_removeButton('reset');
		$this->_removeButton('save');
		$this->setId('troubleshooting_view');
		
		$this->_addButton('refresh', array(
                'label'     => Mage::helper('emaildirect')->__('Refresh'),
                'onclick'   => 'window.location.reload();',
            ));
      
		$this->setTemplate('emaildirect/troubleshooting/view.phtml');
	}

	public function getHeaderText()
	{
		return Mage::helper('emaildirect')->__('EmailDirect Troubleshooting');
	}

	public function getUrl($params='', $params2=array())
	{
		return parent::getUrl($params, $params2);
	}
	
	public function getBackUrl()
	{
		return Mage::helper('emaildirect')->getAdminUrl('adminhtml/system_config/edit/section/emaildirect');
	}
}