<?php

class EmailDirect_Integration_Model_Wrapper_Database
{
   public function add($name,$type,$size=0)
   {
      $xml = "<DatabaseColumnAdd>";
      $xml .= "<ColumnName><![CDATA[$name]]></ColumnName>";
      $xml .= "<ColumnType><![CDATA[$type]]></ColumnType>";
      if($size) {
         $xml .= "<ColumnSize><![CDATA[$size]]></ColumnSize>";
      }
      $xml .= "</DatabaseColumnAdd>";
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("database","","",$xml);
      
      return $rc;
   }
   
   public function get($name)
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("database",$name);
      
      return $rc;
   }
   
   public function getAllColumns()
   {
      $rc = Mage::getSingleton('emaildirect/wrapper_execute')->sendCommand("database");
      
      return $rc;
   }
   
   public function exists($name)
   {
      $column = $this->get($name);
      
      if (isset($column->ErrorCode) && $column->ErrorCode == 234)
         return false;
      
      return true;
   }
}
