<?php

class EmailDirect_Integration_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
	const INSTALL_LOG_FILE_NAME = 'emaildirect_install';
	
	public function addEmailDirectTables()
	{
		$this->run("

			DROP TABLE IF EXISTS {$this->getTable('emaildirect/session')};
			CREATE TABLE {$this->getTable('emaildirect/session')} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`customer_id` int(11) DEFAULT NULL,
				`magento_session_id` varchar(255) NOT NULL,
				`email` varchar(255) NULL,
				PRIMARY KEY (`id`),
				KEY `idx_magento_session_id` (`magento_session_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				
			DROP TABLE IF EXISTS {$this->getTable('emaildirect/abandoned')};
			CREATE TABLE {$this->getTable('emaildirect/abandoned')} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`session_id` int(11) NOT NULL,
				`date_sent` TIMESTAMP NULL,
				`quote_id` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_quote_id` (`quote_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			 
			DROP TABLE IF EXISTS {$this->getTable('emaildirect/order')};
			CREATE TABLE {$this->getTable('emaildirect/order')} (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`date_sent` TIMESTAMP NULL,
				`order_id` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_order_id` (`order_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		");
	}
	
	public function addNotification($title, $description, $url = "", $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
	{
		$admin_notification = Mage::getSingleton('adminnotification/inbox');
		
		// Older versions of Magento don't have this method
		if (method_exists($admin_notification,"add"))
		{
			$admin_notification->add(
				$severity,
				$title,
				$description,
				$url
			);
		}
		else
		{
			$this->addLegacyNotification($title, $description, $url, $severity);
		}	
	}
	
	private function addLegacyNotification($title, $description, $url = "", $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
	{
		if (is_array($description))
			$description = '<ul><li>' . implode('</li><li>', $description) . '</li></ul>';
		
		$date = date(EmailDirect_Integration_Helper_Troubleshooting::DATE_FORMAT);
		Mage::getSingleton('adminnotification/inbox')->parse(array(array(
			'severity'		=> $severity,
			'date_added'  	=> $date,
			'title'	   	=> $title,
			'description' 	=> $description,
			'url'		 		=> $url,
			'internal'		=> $isInternal
		)));
	}
	
	public function renameOld()
	{
		try
		{
			$local = Mage::getBaseDir('code') . DS . "local" . DS;
			
			$old = "{$local}EmailDirect";
			$new = "{$local}EmailDirect_Old";
			
			if (is_dir($old))
			{
				rename($old,$new);
				
				$this->addNotification(
					"The EmailDirect extension was updated. The older version has been moved.",
					"The EmailDirect extension is now located in the Community folder.  The version in the Local folder has been moved to {$new} so that it does not interfere with the new code."
				);
			}
		}
		catch (Exception $e)
		{
			Mage::helper('emaildirect')->logException($e);
		}
	}
		
	public function install_log($data)
	{
		Mage::log($data,null,self::INSTALL_LOG_FILE_NAME . EmailDirect_Integration_Helper_Data::LOG_FILE_EXT, true);
	}
}	