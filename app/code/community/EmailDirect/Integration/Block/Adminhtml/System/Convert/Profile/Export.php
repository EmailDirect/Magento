<?php

class EmailDirect_Integration_Block_Adminhtml_System_Convert_Profile_Export extends Mage_Adminhtml_Block_Abstract
{
	
	protected $_record_name = "record";
	
   public function __construct()
	{
		parent::__construct();
		$this->_init();
	}
	
	protected function getCollection()
	{
	
	}
	
	protected function _init()
	{
		$collection = $this->getCollection();
    	$importData = array_map('intval',$collection->getAllIds());
		$this->setBatchItemsCount(count($importData));
		
		$this->setBatchConfig(
                    array(
                        'styles' => array(
                            'error' => array(
                                'icon' => Mage::getDesign()->getSkinUrl('images/error_msg_icon.gif'),
                                'bg'   => '#FDD'
                            ),
                            'message' => array(
                                'icon' => Mage::getDesign()->getSkinUrl('images/fam_bullet_success.gif'),
                                'bg'   => '#DDF'
                            ),
                            'loader'  => Mage::getDesign()->getSkinUrl('images/ajax-loader.gif')
                        ),
                        'template' => '<li style="#{style}" id="#{id}">'
                                    . '<img id="#{id}_img" src="#{image}" class="v-middle" style="margin-right:5px"/>'
                                    . '<span id="#{id}_status" class="text">#{text}</span>'
                                    . '</li>',
                        'text'     => $this->__('Processed <strong>%s%% %s/%d</strong> ' . $this->_record_name . 's', '#{percent}', '#{updated}', $this->getBatchItemsCount()),
                        'uploadText'  => $this->__('Sending file to EmailDirect...'),
                        'successText'  => $this->__('Exported <strong>%s</strong> ' . $this->_record_name . 's', '#{updated}')
                    )
                );
		
		$this->setImportData($importData);
		
		$this->setUploadStatus('true');
		
		$this->setBatchSize(Mage::helper('emaildirect')->exportConfig('batch'));
	}
	
	public function getBatchConfigJson()
	{
		return Mage::helper('core')->jsonEncode(
            $this->getBatchConfig()
		);
	}

	public function jsonEncode($source)
	{
		return Mage::helper('core')->jsonEncode($source);
	}

	public function getFormKey()
	{
		return Mage::getSingleton('core/session')->getFormKey();
	}
	
	public function getStore()
	{
		$store = Mage::app()->getRequest()->getParam('store');
		if ($store)
			return $store;
		
		return 0;
	}
}