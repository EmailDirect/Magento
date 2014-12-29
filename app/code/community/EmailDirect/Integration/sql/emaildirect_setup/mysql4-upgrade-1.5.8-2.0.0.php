<?php

$installer = $this;

$installer->startSetup();

try
{
	$this->addEmailDirectTables();
	
	// Rename the old local installation so that it doesn't interfere with the new community code
	$this->renameOld();
}
catch (Exception $e)
{
	$this->install_log($e->getMessage());
}

$installer->endSetup();
