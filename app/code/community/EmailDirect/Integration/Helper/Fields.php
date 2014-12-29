<?php

class EmailDirect_Integration_Helper_Fields extends Mage_Core_Helper_Abstract
{
	const ABANDONED_SETUP_PATH = 'emaildirect/general/abandonedsetup';
	const SETUP_VERSION_PATH = 'emaildirect/general/setup_version';
	
	private function getVersion()
	{
		return (string) Mage::getConfig()->getNode('modules/EmailDirect_Integration/version');
	}
	
	private function addDatabaseField($data)
	{
		$name = $data['name'];
		$type = $data['type'];
		
   	if (isset($data['size']))
			$rc = Mage::getSingleton('emaildirect/wrapper_database')->add($name,$type,$data['size']);
		else
      	$rc = Mage::getSingleton('emaildirect/wrapper_database')->add($name,$type);
		
      if(isset($rc->ErrorCode) && $rc->ErrorCode != 233)
		{
			Mage::helper('emaildirect')->log("Error adding {$name} field");
			Mage::throwException("Error adding {$name} field");
		}
	}
	
	private function getMultiFields($fields, $prefix = "", $count = null)
	{
		if ($count == null)
			$count = Mage::helper('emaildirect')->config('product_fields');
			
		for ($i = 1; $i <= $count; $i++)
		{
			$fields[] = 
				$this->addField("{$prefix}ProductName{$i}", 'Text', '200');
			
			if ($prefix != 'Related')
				$fields[] = $this->addField("{$prefix}ParentName{$i}", 'Text', '200');
			$fields[] = $this->addField("{$prefix}SKU{$i}", 'Text', '50');
			$fields[] = $this->addField("{$prefix}URL{$i}", 'Text', '200');
			$fields[] = $this->addField("{$prefix}Image{$i}", 'Text', '200');
			$fields[] = $this->addField("{$prefix}Description{$i}", 'Text', '200');
			$fields[] = $this->addField("{$prefix}Cost{$i}", 'Text', '20');
		}
		
		return $fields;
	}
	
	private function getExistingColumns()
	{
		$columns = Mage::getSingleton('emaildirect/wrapper_database')->getAllColumns();
		
		$existing = array();
		
		if (!isset($columns))
			return $existing;
		
		foreach ($columns as $column)
		{
			$name = (string)$column->ColumnName;
			
			$existing[$name] = $name;
		}
		
		return $existing;
	}
	
	private function getMissingColumns($fields)
	{
		$columns = $this->getExistingColumns();
		
		$check_columns = array();
		
		foreach ($columns as $key => $column)
		{
			$check_columns[strtolower($key)] = $column;
		}
		
		$missing = array();
		
		foreach ($fields as $field)
		{
			if (!isset($check_columns[strtolower($field['name'])]))
			{
				$missing[] = $field;
			}
		}

		return $missing;
	}
	
	private function addField($name, $type, $size = null)
	{
		$new_field = array('name' => $name, 'type' => $type);
		
		if ($size != null)
			$new_field['size'] = $size;
		
		return $new_field;
	}
	
	public function getCustomFields($force_product = false)
	{
		$fields = array();
		
		if (Mage::helper('emaildirect')->config('save_latest_order') || $force_product)
		{
			$fields[] = $this->addField('LastOrderNumber', 'Text', '30');
			$fields[] = $this->addField('LastPurchaseDate', 'Date');
			$fields[] = $this->addField('LastPurchaseTotal', 'Text', '20');
			
			$fields = $this->getMultiFields($fields);
			
			$fields = $this->getMultiFields($fields,"Related", Mage::helper('emaildirect')->config('related_fields'));
		}
		
		if (Mage::helper('emaildirect')->config('wishlist_enabled'))
		{
			$fields[] = $this->addField('WishListDate', 'Date');
			$fields[] = $this->addField('WishListUrl', 'Text', '200');
		}
		
		$fields[] = $this->addField('LastLogin', 'Date');
		
		$fields[] = $this->addField('AbandonedDate', 'Date');
		$fields[] = $this->addField('AbandonedUrl', 'Text', '1000');
		
		$fields = $this->getMultiFields($fields,"AB");
		
		return $fields;
	}
	
	public function verifyFields($force_product = false)
	{
		$fields = $this->getCustomFields($force_product);
		
		$missing = $this->getMissingColumns($fields);
		
		if (count($missing) > 0)
		{
			foreach ($missing as $data)
			{
				$this->addDatabaseField($data);
			}
			
			$missing_check = $this->getMissingColumns($fields);
		
			if (count($missing_check) != 0)
			{
				Mage::helper('emaildirect')->log("Error creating fields");
				Mage::helper('emaildirect')->log($missing_check, "Missing Check");
				Mage::throwException("Error creating fields");
			}
		}
		
		// Save version so we know if we are up to date or not
      Mage::getConfig()->saveConfig(self::ABANDONED_SETUP_PATH, 1,"default","default");
		Mage::getConfig()->saveConfig(self::SETUP_VERSION_PATH, $this->getVersion(),"default","default");
	}

	public function checkFields()
	{
		$version = $this->getVersion();
		
		$setup_version = Mage::helper('emaildirect')->config('setup_version');
		
		if ($setup_version == NULL || (version_compare($setup_version, $version) < 0))
			$this->verifyFields();
	}
}