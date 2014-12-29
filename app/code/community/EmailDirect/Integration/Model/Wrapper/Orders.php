<?php

class EmailDirect_Integration_Model_Wrapper_Orders extends EmailDirect_Integration_Model_Wrapper_Abstract
{
   public function getOrderXml($order, $email = null)
   {
      $date = $order->getCreatedAt();
      $orderNum = $order->getIncrementId();
      
      $items = $order->getAllItems();
      
      $xml = "<Order>";
      if ($email != null)
         $xml .= "<EmailAddress><![CDATA[{$email}]]></EmailAddress>";
      $xml .= "<PurchaseDate><![CDATA[{$date}]]></PurchaseDate>";
      $xml .= "<OrderNumber><![CDATA[{$orderNum}]]></OrderNumber>";
		
		
      if (is_array($items))
      {
      	$parent_items = array();
      	foreach($items as $item)
			{
				$type = $item->getProductType();
				if ($type == "bundle")
					$parent_items[$item->getId()] = $item;
			}
			
         $xml .= "<Items>";
         foreach($items as $item)
         {
         	$parent = null;
         	if ($item->getParentItemId() != null)
				{
					if (isset($parent_items[$item->getParentItemId()]))
						$parent = $parent_items[$item->getParentItemId()];
					else
						continue;
				}
         	
				$qty = (int)$item->getQtyOrdered();
            $xml .= "<OrderItem>";
            $name = $item->getName();
            $xml .= "<ProductName><![CDATA[$name]]></ProductName>";
            $sku = $item->getSku();
            $xml .= "<SKU><![CDATA[{$sku}]]></SKU>";
            $xml .= "<Quantity>{$qty}</Quantity>";
            $price = $item->getPrice();
            $xml .= "<UnitPrice>{$price}</UnitPrice>";
            $weight = $item->getWeight();
				if ($weight)
            	$xml .= "<Weight>{$weight}</Weight>";
            $status = 'Completed';
            $xml .= "<Status><![CDATA[{$status}]]></Status>";
            $xml .= "</OrderItem>";
         }
         $xml .= "</Items>";
      }
      $xml .= "</Order>";
      
      return $xml;
   }
   
   public function addSubscriberOrder($email, $order, $merge_vars)
   {
		$order_data = "<Orders>" . $this->getOrderXml($order) . "</Orders>";
		
		$subscribe = Mage::helper('emaildirect')->forceSubscribe();
		
      // Same call just different adding the order info
      return Mage::getSingleton('emaildirect/wrapper_subscribers')->subscriberAdd($email, $merge_vars, $order_data, $subscribe);
   }

	public function addSubscriberTracking($email,$merge_vars)
   {
   	// Same call just different Merge Vars
      return Mage::getSingleton('emaildirect/wrapper_subscribers')->subscriberAdd($email, $merge_vars, "", false);
   }

}
