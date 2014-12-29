<?php

class EmailDirect_Integration_Model_Newsletter_Observer extends EmailDirect_Integration_Model_Observer_Abstract
{
	/**
	 * Handle Subscriber object saving process
	 */
	public function handleSubscriber(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::NEWSLETTER);
			$this->_log('handleSubscriber Start');
			
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				return;
			}
			
			$subscriber = $observer->getEvent()->getSubscriber();
			$subscriber->setImportMode(false);
			
			$email  = $subscriber->getSubscriberEmail();
			$listId = $this->_helper->getDefaultPublication($subscriber->getStoreId());
			$isConfirmNeed = (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRMATION_FLAG, $subscriber->getStoreId()) == 1) ? TRUE : FALSE;
	
			//New subscriber, just add
			if ($subscriber->isObjectNew())
			{
				$this->_log("New Subscriber");
				if (TRUE === $isConfirmNeed)
				{
					$this->_log("Confirmation Needed");
					$subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED);
					if (!Mage::helper('customer')->isLoggedIn() && Mage::registry('ed_guest_customer'))
					{
						$this->_log("Guest Customer");
						$guestCustomer = Mage::registry('ed_guest_customer');
						$subscriber->setFirstname($guestCustomer->getFirstname());
						$subscriber->setLastname($guestCustomer->getLastname());
						Mage::unregister('ed_guest_customer');
						$subscriber->save();
					}
					else
						$this->_log("Not a Guest Customer (Doing Nothing?)");
				}
				else
				{
					$this->_log("Confirmation Not Required");
					$subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
					$merge_vars = $this->_helper->getMergeVars($subscriber);
					
					$this->_log($merge_vars,"Merge Vars");
					
					$rc = Mage::getSingleton('emaildirect/wrapper_subscribers')
										->subscriberAdd($email,$merge_vars);
				}
			}
			else
			{
				$this->_log("Existing Subscriber");
				$status = (int)$subscriber->getData('subscriber_status');
				
				$oldSubscriber = Mage::getModel('newsletter/subscriber')
									->load($subscriber->getId());
				$oldstatus = (int)$oldSubscriber->getOrigData('subscriber_status');
				if ($oldstatus == Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED && $status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
				{
					$this->_log("Unconfirmed to Subscribed");
					$subscriber->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
					$merge_vars = $this->_helper->getMergeVars($subscriber);
					
					$this->_log($merge_vars,"Merge Vars");
					$rc = Mage::getSingleton('emaildirect/wrapper_subscribers')
										->subscriberAdd($email,$merge_vars);
				}
				elseif( $status !== $oldstatus )
				{
					//Status change
					$this->_log("Status Change");
				
					//Unsubscribe customer
					if($status == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
					{
						$this->_log("Unsubscribed");
						$rc = Mage::getSingleton('emaildirect/wrapper_publications')
										->unsubscribe($listId, $email);
					}
					else if($status == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
					{
						$this->_log("Subscribed");
						if( $oldstatus == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE || $oldstatus == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED )
						{
							$this->_log("Changing Status");
							$rc = Mage::getSingleton('emaildirect/wrapper_publications')
										->subscribe($listId, $email);
						}
						else
							$this->_log("Status Not Changed");
					}
				}
			}
			$this->_log('handleSubscriber End');
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	// Remove Unsubscribe option from Newsletter grid
	public function updateNewsletterMassAction($observer)
	{
		try
		{
			if (!$this->_helper->canEdirect())
				return;
			
			$block = $observer->getEvent()->getBlock();
			
			if (get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
	            && $block->getRequest()->getControllerName() == 'newsletter_subscriber')
			{
				$block->removeItem('unsubscribe');
			}
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	/**
	 * Handle Subscriber deletion from Magento, unsubcribes email
	 * and sends the delete_member flag so the subscriber gets deleted.
	 */
	public function handleSubscriberDeletion(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::NEWSLETTER);
			$this->_log("Handle Subscriber Deletion Start");
			
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				return;
			}
			
			$subscriber = $observer->getEvent()->getSubscriber();
			
			$email = $subscriber->getSubscriberEmail();
			
			$rc = Mage::getSingleton('emaildirect/wrapper_subscribers')
										->subscriberDelete($email);
			
			$this->_log("Handle Subscriber Deletion End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	public function registerCheckoutSubscribe(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::NEWSLETTER);
			$this->_log("Register Checkout Subscribe Start");
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				return;
			}
			$subscribe = Mage::app()->getRequest()->getPost('emaildirect_subscribe');
			
			if (!is_null($subscribe) || $this->_helper->forceSubscribe())
				Mage::getSingleton('core/session')->setEmaildirectCheckout($subscribe);
			
			$this->_log("Register Checkout Subscribe End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}

	/**
	 * Subscribe customer to Newsletter if flag on session is present
	 */
	public function registerCheckoutSuccess(Varien_Event_Observer $observer)
	{
		try
		{
			$this->_setLogArea(EmailDirect_Integration_Helper_Troubleshooting::NEWSLETTER);
			$this->_log("Register Checkout Success Start");
			
			if (!$this->_helper->canEdirect())
			{
				$this->_logReason($this->_helper->getDisabledReason());
				return;
			}
			
			$sessionFlag = Mage::getSingleton('core/session')->getEmaildirectCheckout(TRUE);
			if (!$sessionFlag && !$this->_helper->forceSubscribe())
			{
				$this->_logReason("Session flag not found.");
				return;
			}
			
			$order_id = (int)current($observer->getEvent()->getOrderIds());
			
			if (!$order_id)
			{
				$this->_logReason("Order ID not found.");
				return;
			}
				
			$order = Mage::getModel('sales/order')->load($order_id);
			if (!$order->getId())
			{
				$this->_logReason("Failed to Load Order ({$order_id}).");
				return;
			}
			
			$this->_log("Processing Order # " . $order->getIncrementId());
			
			//Guest Checkout
			if ((int)$order->getCustomerGroupId() === Mage_Customer_Model_Group::NOT_LOGGED_IN_ID )
			{
				$this->_log("Guest Checkout");
				$this->_helper->registerGuestCustomer($order);
			}
	
			$subscriber = Mage::getModel('newsletter/subscriber')
						->subscribe($order->getCustomerEmail());
			
			$this->_log("Register Checkout Success End");
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_logException($e);
		}
	}
}	