<?php

$installer = $this;

$installer->startSetup();

try
{
	$this->addEmailDirectTables();
	
	// Add install message to inbox

	$this->addNotification(
				'The EmailDirect extension was installed.  Enter your API Key to configure the settings and make sure your cron jobs are running.',
				'The EmailDirect extension was installed.  Enter your API Key to configure the settings and make sure your cron jobs are running. Cron Install and Setup Guide (http://emaildirect.com/Magento)',
				'http://emaildirect.com/Magento'
			);
}
catch (Exception $e)
{
	$this->install_log($e->getMessage());
}

$installer->endSetup();
