<?php

class EmailDirect_Integration_Block_Adminhtml_System_Config_Form_Field_Common extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('emaildirect/system/config/form/field/array.phtml');
	}
	
	/**
	 * Add a column to array-grid
	 *
	 * @param string $name
	 * @param array $params
	 */
	public function addColumn($name, $params)
	{
		$this->_columns[$name] = array(
			'label'	 	=> empty($params['label']) 	? 'Column' 	: $params['label'],
			'size'	  	=> empty($params['size'])  	? false		: $params['size'],
			'style'	 	=> empty($params['style'])  	? null		: $params['style'],
			'class'	 	=> empty($params['class'])  	? null		: $params['class'],
			'type'	  	=> empty($params['type'])  	? null		: $params['type'],
			'options'	=> empty($params['options']) 	? array()	: $params['options'],
			'renderer'  => false,
		);
		if ((!empty($params['renderer'])) && ($params['renderer'] instanceof Mage_Core_Block_Abstract)) {
			$this->_columns[$name]['renderer'] = $params['renderer'];
		}
	}
	
	/**
	 * Render array cell for prototypeJS template
	 *
	 * @param string $columnName
	 * @return string
	 */
	protected function _renderCellTemplate($columnName)
	{
		if (empty($this->_columns[$columnName])) {
			throw new Exception('Wrong column name specified.');
		}
		$column	 = $this->_columns[$columnName];
		$inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

		if ($column['renderer'])
		{
			return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
				->toHtml();
		}
		
		if ($column['type'] == 'options')
		{
		   //$name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
		   $html = '<select name="' . $inputName . '"class="' .
			(isset($column['class']) ? $column['class'] : 'input-text') . '"'.
			(isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '>';
		   //$value = $row->getData($this->getColumn()->getIndex());
		   
		   foreach ($column['options'] as $val => $label)
		   {
			   //$selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
			   $html .= '<option #{_selected_' . $this->escapeHtml($val) . '} value="' . $this->escapeHtml($val) . '">';
			   $html .= $this->escapeHtml($label) . '</option>';
		   }
		   $html.='</select>';
		   
		   return $html;
		}
		
		return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
			($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
			(isset($column['class']) ? $column['class'] : 'input-text') . '"'.
			(isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
	}
}