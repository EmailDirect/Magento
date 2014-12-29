<?php

class EmailDirect_Integration_Admin_AbandonedController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
   {
      $this->loadLayout();
      
		$this->getLayout()->getBlock('head')->setTitle($this->__('EmailDirect Abandoned Carts'));
      $this->_setActiveMenu('emaildirect');
      $this->renderLayout();
   }
	
	public function detailsAction()
   {
      $this->loadLayout();
      
		$this->getLayout()->getBlock('head')->setTitle($this->__('EmailDirect Abandoned Carts'));
      $this->_setActiveMenu('emaildirect');
      $this->renderLayout();
   }
   
   public function gridAction()
   {
      $this->showGrid();
   }
   
   private function showGrid()
   {
      $this->loadLayout();
      $this->getResponse()->setBody($this->getLayout()->createBlock('emaildirect/adminhtml_abandoned_grid')->toHtml());
   }

	public function runAction()
	{
		$store_id = $this->getRequest()->getParam('store_id');
		
		Mage::getSingleton('emaildirect/abandoned_observer')->manualCartsProcessor($store_id);
		
		$msg = "The Abandoned Cart process has been run";
		
		if ($store_id)
			$msg .= " on store: " . Mage::helper('emaildirect')->getFullStoreNameById($store_id);
		
		$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__($msg));
      $this->_redirect('*/*/index');
	}
	
	public function sendAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		if ($id == null)
			$this->_getSession()->addError(Mage::helper('adminhtml')->__("No Carts were specified."));
		else
		{
			$id_list = array($id);
			Mage::getSingleton('emaildirect/abandoned_observer')->SendAbandonedCarts($id_list);
			$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Abandoned Cart has been sent."));
		}
      $this->_redirect('*/*/index');
	}
	
	public function massSendAction()
	{
		$params = array();
      if( $this->getRequest()->isPost() )
         $params = $this->getRequest()->getPost();

      $id_list = $params['id'];
      
      if (!is_array($id_list)) 
         Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select at least one cart to send or resend.'));
      else 
      {
         try 
         {
      		$count = Mage::getSingleton('emaildirect/abandoned_observer')->SendAbandonedCarts($id_list);
				
            Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('adminhtml')->__('Total of %d carts(s) were sent.', $count));
         } 
         catch (Exception $e) 
         {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
         }
      }
      $this->_redirect('*/*/index', Mage::helper('emaildirect')->getUrlParams());
	}
}