<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Import_FileReader_Reader {

	var $status='success';
	var $numberOfRecordsRead = 0;
	var $errorMessage='';
	var $user;
	var $request;
	var $moduleModel;

	public function  __construct($request, $user) {
		$this->request = $request;
		$this->user = $user;
		$this->moduleModel = Vtiger_Module_Model::getInstance($this->request->get('module'));
	}

	public function getStatus() {
		return $this->status;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function getNumberOfRecordsRead() {
		return $this->numberOfRecordsRead;
	}

	/**
	 * To set number of records read from CSV to DB
	 * @param type $table
	 * @param type $db
	 */
	public function setNumberOfRecordsRead($table,$db){
		$query = "SELECT count(*) AS count FROM $table";
		$result = $db->pquery($query,array());
		$count = $db->query_result($result,0,'count');
		$this->numberOfRecordsRead = $count;
	}

	public function hasHeader() {
		if($this->request->get('has_header') == 'on'
				|| $this->request->get('has_header') == 1
				|| $this->request->get('has_header') == true) {
			return true;
		}
		return false;
	}

	public function getFirstRowData($hasHeader=true) {
		return null;
	}

	public function getFilePath() {
		return Import_Utils_Helper::getImportFilePath($this->user);
	}

	public function getFileHandler() {
		$filePath = $this->getFilePath();
		if(!file_exists($filePath)) {
			$this->status = 'failed';
			$this->errorMessage = "ERR_FILE_DOESNT_EXIST";
			return false;
		}

		$fileHandler = fopen($filePath, 'r');
		if(!$fileHandler) {
			$this->status = 'failed';
			$this->errorMessage = "ERR_CANT_OPEN_FILE";
			return false;
		}
		return $fileHandler;
	}

	public function convertCharacterEncoding($value, $fromCharset, $toCharset) {
		if (function_exists("mb_convert_encoding")) {
			$value = mb_convert_encoding($value, $toCharset, $fromCharset);
		} else {
			$value = iconv($fromCharset, $toCharset, $value);
		}
		return $value;
	}

	public function read() {
		// Sub-class need to implement this
	}

	public function deleteFile() {
		$filePath = $this->getFilePath();
		@unlink($filePath);
	}

	public function createTable() {
		$db = PearDatabase::getInstance();

		$tableName = Import_Utils_Helper::getDbTableName($this->user);
		$fieldMapping = $this->request->get('field_mapping');

		$moduleFields = $this->moduleModel->getFields();
		$moduleImportableFields = $this->moduleModel->getAdditionalImportFields();
		$moduleFields = array_merge($moduleFields, $moduleImportableFields);

		$columnsListQuery = 'id INT PRIMARY KEY AUTO_INCREMENT, status INT DEFAULT 0, recordid INT';
		$fieldTypes = $this->getModuleFieldDBColumnType();
		foreach($fieldMapping as $fieldName => $index) {
			$fieldObject = $moduleFields[$fieldName];
			$columnsListQuery .= $this->getDBColumnType($fieldObject, $fieldTypes);
		}
		$createTableQuery = 'CREATE TABLE '. $tableName . ' ('.$columnsListQuery.') ENGINE=MyISAM ';
		$db->pquery($createTableQuery, array());
		return true;
	}

	public function addRecordToDB($columnNames, $fieldValues,$current_user) { 

		// echo '<pre>';
		// print_r($columnNames);
		// print_r($fieldValues);
		// astcrm changes
		$list_field_index = array_search('title', $columnNames);
		
		$db = PearDatabase::getInstance(); 
		foreach($fieldValues as $key => $fieldVal){ 
			$newField[$key] = htmlspecialchars_decode($fieldVal, ENT_QUOTES); 
		} 

		if($list_field_index != ''){
			$list_name = $fieldValues[$list_field_index];
		}
		else {
			// $newField[]='NoID';
			// $columnNames[]='title';
			$list_name='NoID';
		}

		$fieldValues = $newField; 

		// echo '<pre>';
		// print_r($columnNames);
		// print_r($fieldValues);

		$tableName = Import_Utils_Helper::getDbTableName($this->user); 

		$db->pquery('INSERT INTO '.$tableName.' ('. implode(',', $columnNames).') VALUES ('. generateQuestionMarks($fieldValues) .')', $fieldValues); 

		// echo $db->convert2SQL('INSERT INTO '.$tableName.' ('. implode(',', $columnNames).') VALUES ('. generateQuestionMarks($fieldValues) .')', $fieldValues); 
		

		$result = $db->pquery("SELECT cvid FROM vtiger_customview WHERE viewname = '$list_name' AND userid = '$current_user' AND status = 1 ");
		if ($result && $db->num_rows($result) == 0) {
			$result_lastid = $db->pquery("SELECT cvid FROM vtiger_customview ORDER BY cvid DESC LIMIT 1 ");
			$row_lastid = $db->fetch_array($result_lastid);
			$last_id = $row_lastid['cvid'];
			$cvid = $last_id + 1;
			// view list name in the CRM
			$db->pquery("INSERT INTO `vtiger_customview` ( `cvid` , `viewname` , `setdefault` , `setmetrics` , `entitytype` , `status` , `userid` ) VALUES ( '$cvid', '$list_name', '0', '0', 'Contacts', '1', '$current_user' )");
			// calling group condition
			$db->pquery("INSERT INTO `vtiger_cvadvfilter_grouping` ( `groupid` , `cvid` , `group_condition` , `condition_expression` ) VALUES ( '1', '$cvid', 'and', ' 0 ' )");

			// columns to be displayed
			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '0', 'vtiger_contactdetails:lastname:lastname:Contacts_Last_Name:V' );");

			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '1', 'vtiger_contactdetails:mobile:mobile:Contacts_Mobile:V' );");

			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '2', 'vtiger_contactdetails:title:title:Contacts_Title:V' );");

			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '3', 'vtiger_crmentity:smownerid:assigned_user_id:Contacts_Assigned_To:V' );");

			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '4', 'vtiger_crmentity:createdtime:createdtime:Contacts_Created_Time:DT' );");

			$db->pquery("INSERT INTO `vtiger_cvcolumnlist` ( `cvid` , `columnindex` , `columnname` ) VALUES ( '$cvid', '5', 'vtiger_crmentity:modifiedtime:modifiedtime:Contacts_Modified_Time:DT' );");
			//
			// list view condition
			$db->pquery("INSERT INTO `vtiger_cvadvfilter` ( `cvid` , `columnindex` , `columnname` , `comparator` , `value` , `groupid` , `column_condition` ) VALUES ( '$cvid', '0', 'vtiger_contactdetails:title:title:Contacts_Title:V', 'e', '$list_name', '1', '' );");

		}

		$this->numberOfRecordsRead++;
	}

	/** Function returns the database column type of the field
	 * @param $fieldObject <Vtiger_Field_Model>
	 * @param $fieldTypes <Array> - fieldnames with column type
	 * @return <String> - column name with type for sql creation of table
	 */	
	public function getDBColumnType($fieldObject,$fieldTypes){
		$columnsListQuery = '';
		$fieldName = $fieldObject->getName();
		$dataType = $fieldObject->getFieldDataType();
		$skipDataType = array('reference','owner', 'currencyList', 'date', 'datetime', 'productTax', 'ownergroup');
		if(in_array($dataType, $skipDataType)){
			$columnsListQuery .= ','.$fieldName.' varchar(250)';
		} else {
			$columnsListQuery .= ','.$fieldName.' '.$fieldTypes[$fieldObject->get('column')];
		}

		return $columnsListQuery;
	}

	/** Function returns array of columnnames and their column datatype
	 * @return <Array>
	 */
	public function getModuleFieldDBColumnType() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT tablename FROM vtiger_field WHERE tabid=? GROUP BY tablename', array($this->moduleModel->getId()));
		$tables = array();
		if ($result && $db->num_rows($result) > 0) {
			while ($row = $db->fetch_array($result)) {
				$tables[] = $row['tablename'];
			}
		}
		$fieldTypes = array();
		foreach ($tables as $table) {
			$result = $db->pquery("DESC $table", array());
			if ($result && $db->num_rows($result) > 0) {
				while ($row = $db->fetch_array($result)) {
					$fieldTypes[$row['field']] = $row['type'];
				}
			}
		}
		return $fieldTypes;
	}
}
?>