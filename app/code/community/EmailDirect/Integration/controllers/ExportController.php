<?php
class EmailDirect_Integration_ExportController extends Mage_Core_Controller_Front_Action
{
	protected $_export_type = null;
	
	private function getExportType()
	{
		if ($this->_export_type == null)
			$this->_export_type = $this->getRequest()->getParam('export_type', 'product');
		
		return $this->_export_type;
	}
	
	public function downloadAction()
	{
		$file_name = "emaildirect_" . $this->getExportType() . "s_" . $this->getRequest()->getParam('filename');
		$file = Mage::helper('emaildirect')->getExportFileName($file_name);

		$this->_prepareDownloadResponse(Mage::helper('emaildirect')->getExportFileName($file_name,false), file_get_contents($file));
	}
}