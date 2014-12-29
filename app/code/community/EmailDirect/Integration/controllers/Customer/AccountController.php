<?php
class EmailDirect_Integration_Customer_AccountController extends EmailDirect_Integration_Controller_Front_Abstract
{
	/**
	 * Action predispatch
	 *
	 * Check customer authentication for some actions
	 */
	public function preDispatch()
	{
		parent::preDispatch();

		if (!$this->getRequest()->isDispatched()) {
			return;
		}

		if (!$this->_getCustomerSession()->authenticate($this)) {
			$this->setFlag('', 'no-dispatch', true);
		}
	}

	/**
	 * Retrieve customer session model object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	protected function _getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}

	/**
	 * Display data
	 */
	public function indexAction()
	{
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');

		$this->getLayout()->getBlock('head')->setTitle($this->__('Newsletter Subscription'));
		$this->renderLayout();
	}
	
	public function saveadditionalAction()
	{
		$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::NEWSLETTER);
		$this->_log('Save Additional Start');
		
		$session = Mage::getSingleton('customer/session');
		
		if ($this->getRequest()->isPost())
		{
			$state = $this->getRequest()->getPost('state');
			$this->_log($state, 'State Serialized');
			//<state> param is an html serialized field containing the default form state
			//before submission, we need to parse it as a request in order to save it to $odata and process it
			parse_str($state, $odata);
			
			$this->_log($odata, 'State Data');
			
			$active_lists = (TRUE === array_key_exists('list', $odata)) ? $odata['list'] : array();
			$lists	= $this->getRequest()->getPost('list', array());
			
			$this->_log($lists, 'List Selection');
			
			$customer  = Mage::helper('customer')->getCustomer();
			$email	 = $customer->getEmail();
			
			$this->_log("Email: {$email}");
			
			// Manage the main publication and subscription
			$publication = (TRUE === array_key_exists('publication', $odata)) ? $odata['publication'] : array();
			$pub_selection = $this->getRequest()->getPost('publication', array());
			
			$this->_log($pub_selection, 'Publication Selection');
			$general = Mage::helper('emaildirect')->config('publication');
			
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
			$subscriber->setImportMode(false);
			$rc = Mage::getSingleton('emaildirect/wrapper_publications')->getPublication($general);
			$subscriber->setListName((string)$rc->Name);
			
			$new_subscriber = false;

			if (!$pub_selection) 
			{
				$this->_log("Unsubscribe from All");
				
				// Publication is deslected so Unsubscribe from all.
				$rc = Mage::getSingleton('emaildirect/wrapper_publications')->unsubscribe($general,$email);
				$subscriber->unsubscribe();
				// unsuscribe for all the lists
				foreach($active_lists as $listId => $list)
				{
					$rc = Mage::getSingleton('emaildirect/wrapper_lists')->listUnsubscribe($listId, $email);
				}
				
				$session->addSuccess('Successfully unsubscribed from Newsletter');
				
				$this->_log("Unsubscribe from all success redirect");
				
				$this->_redirect('*/*/index');
				return;
			}
			elseif ($publication != $pub_selection) 
			{
				$this->_log("Publication != Publication Selection");
				
				if($subscriber->isObjectNew())
				{
					// This code happens when ->subscribe is called below
					$this->_log("New Subscriber");
					
					$additional_lists = Mage::helper('emaildirect')->config('additional_lists');
					if ($additional_lists != "")
					{
						$this->_log("Get New Active Lists");
						$temp_lists = explode(",",$additional_lists);
						$active_lists = array();
						
						foreach ($temp_lists as $temp_id)
						{
							$active_lists[$temp_id] = array('subscribed' => $temp_id);
						}
						
						$this->_log($active_lists,"New Active Lists Data");
					}
						
					$new_subscriber = true;
				}
				else
				{
					$this->_log("Existing Subscriber");
					$rc = Mage::getSingleton('emaildirect/wrapper_publications')->subscribe($general,$email);
				}
				$subscriber->subscribe($email);
			}

			if( !empty($active_lists) )
			{
				$this->_log("Active Lists");
				foreach($active_lists as $listId => $list)
				{
					if (FALSE === array_key_exists($listId, $lists))
					{
						$this->_log("Unsubscribe from list {$listId}");
						$rc = Mage::getSingleton('emaildirect/wrapper_lists')->listUnsubscribe($listId, $email);
					}
				}
			}
			
			//Subscribe to new lists
			$subscribe = array_diff_key($lists, $active_lists);
			if (!empty($subscribe))
			{
				$this->_log("Subscribe to new lists");
				foreach($subscribe as $listId => $slist)
				{
					$this->_log("Subscribe to list {$listId}");
					$rc = Mage::getSingleton('emaildirect/wrapper_lists')->listSubscribe($listId, $email);
				}
			}
		}

		$session->addSuccess('Subscriptions Updated');
		
		$this->_log('Save Additional End');

		$this->_redirect('*/*/index');
	}
}