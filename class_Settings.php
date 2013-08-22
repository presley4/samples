<?PHP
//ąćę

/**
* @version		1.2
* @since		2013-07-16
*
* 2011-10-15	upd: /setConstants/ safety
* 2012-04-05	upd: use constant TABLE_SETTINGS
* 2013-07-16	add: /getVal/
*/

define('SETTINGS_NAMEKEY',1);
define('SETTINGS_MULTIKEYTOMULTIARRAY', 2);
define('SETTINGS_REFKEYLIKE', 4);

class CSettings
{
	var $psRefKey = '';
	static $psTableSettingsName = 't_settings';
	
	public function __construct()
	{
		self::$psTableSettingsName = trim(@constant('TABLE_SETTINGS'));
		if(empty(self::$psTableSettingsName)) self::$psTableSettingsName = 't_settings';
		return true;
	}
	
	public function __destruct()
	{
		return true;
	}

	#----------------------------------------------------------------------------------------------

	/**
	* setSettingsRefKey
	* returns true
	*
	* @param	string		$sRefKey		key
	* @return	bool
	*/
	function setSettingsRefKey($sRefKey = '')
	{
		$this->psRefKey = $sRefKey;
		return true;
	}# setSettingsRefKey
	#----------------------------------------------------------------------------------------------

	/**
	* get
	* gets settings data from database
	*
	* @param	array|string $arrRef				array with data to use as a filters for settings rows (field_name=>value) , if string is given then the parameter is converted to array('ref_key'=>$arrRef)
	* @param	int			$iOptions				options, default: SETTINGS_NAMEKEY
	* @return	array
	* @uses 	class DB
	*
	* @since	2011-08-25
	*/
	function get($arrRef = array(), $iOptions = SETTINGS_NAMEKEY)
	{
		global $DB;

		self::$psTableSettingsName = trim(@constant('TABLE_SETTINGS'));
		if(empty(self::$psTableSettingsName)) self::$psTableSettingsName = 't_settings';		

		$OUTPUT = NULL;
		$field_for_key = $iOptions & SETTINGS_NAMEKEY ? 'name' : NULL ;
		
		
		$DB->resetWhere();
		$sMultiKey = '';
		
		if(is_string($arrRef)) $arrRef = array('ref_key'=>$arrRef);

		# prepare sql to read all matching settings
		$sSQL_ORDER_FIND_IN_SET_list = '';
		$sSQL_ORDER_FIND_IN_SET = '';		
		foreach($arrRef as $key=>$value)
		{
			if(!empty($key) && is_string($key))
			{
				if(is_numeric($value))
				{
					$DB->addWhere($key . " = " . $value);
				}
				elseif(is_array($value))
				{
					$sValueList = '';
					foreach($value as $value_item)
					{
						if(!empty($sValueList)) $sValueList .= ',';
						$sValueList .=  is_numeric($value_item) ? $value_item : "'" . mysql_real_escape_string($value_item) . "'";

						if(!empty($sSQL_ORDER_FIND_IN_SET_list)) $sSQL_ORDER_FIND_IN_SET_list .= ',';
						$sSQL_ORDER_FIND_IN_SET_list .= $value_item;
					}
					$DB->addWhere($key . " IN (" . $sValueList . ")");
					if(empty($sMultiKey)) $sMultiKey = $key;
				}
				else
				{
					if($iOptions & SETTINGS_REFKEYLIKE)
					{
						$DB->addWhere($key . " LIKE '" . mysql_real_escape_string($value) . "'");
					}
					else
					{
						$DB->addWhere($key . " = '" . mysql_real_escape_string($value) . "'");
					}
				}
			}
		}

		$sSqlWhere = $DB->buildWhere();
		
		if(!empty($sSQL_ORDER_FIND_IN_SET_list)) $sSQL_ORDER_FIND_IN_SET  = " FIND_IN_SET(ref_key, '{$sSQL_ORDER_FIND_IN_SET_list}'), ";

		$query = "SELECT * FROM ".DB_MAIN.".".self::$psTableSettingsName." {$sSqlWhere} ORDER BY {$sSQL_ORDER_FIND_IN_SET} ref_id ASC, ref_key ASC";
		$set = $DB->getSet($query, MYSQLI_ASSOC, $field_for_key);
		if($iOptions & SETTINGS_MULTIKEYTOMULTIARRAY && !empty($sMultiKey))
		{
			$arrTemp = array();
			foreach($set as $row)
			{
				$arrTemp[$row[$sMultiKey]][$row['name']] = $row;
			}
			$set = $arrTemp;
		}
		$OUTPUT = $set;
		return $OUTPUT;
	}# get	
	#----------------------------------------------------------------------------------------------

	/**
	* getVal
	* gets setting value
	*
	* @param	string 		$sRef					key for setting to fetch
	* @param	int			$iOptions				options
	* @return	string
	*
	* @since	2013-07-16
	*/
	function getVal($sRef = NULL, $iOptions = 0)
	{
		if(empty($sRef)) return NULL;
		$arrSettings = self::get($sRef, $iOptions);
		reset($arrSettings);
		$arrSettings = current($arrSettings);
		return $arrSettings['value'];
	}# getVal
	#----------------------------------------------------------------------------------------------
	
	/**
	* getMulti
	* gets settings data from database
	*
	* @param	array		$arrRef					array with data to use as a filters for settings rows (field_name=>value)
	* @param	int			$iOptions				options, default: SETTINGS_NAMEKEY
	* @return	array
	* @uses 	class DB
	*
	* @since 2011-07-03
	*/
	function getMulti($arrRef = array(), $iOptions = SETTINGS_NAMEKEY)
	{
		global $DB;

		$OUTPUT = NULL;
		$field_for_key = $iOptions & SETTINGS_NAMEKEY ? 'name' : NULL ;
		
		$DB->resetWhere();
		
		foreach($arrRef as $key=>$value)
		{
			if(!empty($key) && is_string($key)) $DB->addWhere($key . " = " . (is_numeric($value) ? $value : "'" . mysql_real_escape_string($value) . "'"));
		}

		$sSqlWhere = $DB->buildWhere();

		$query = "SELECT * FROM ".DB_MAIN.".".$this->psTableSettingsName." {$sSqlWhere}";
		$set = $DB->getSet($query, MYSQLI_ASSOC, $field_for_key);

		$OUTPUT = $set;
		return $OUTPUT;
	}# get
	#--------------------------------------------------------------------------------------------------------
	
	/**
	* setConstants
	* sets constants based on not empty names found in database rows 
	*
	* @return	bool
	* @uses 	class DB
	* 
	* @since 2011-10-15
	*/
	function setConstants()
	{
		global $DB;

		$query = "SELECT * FROM ".DB_MAIN.".".$this->psTableSettingsName." WHERE constant IS NOT NULL";
		$set = $DB->getSet($query, MYSQLI_ASSOC);

		foreach($set as $arrConstant)
		{
			if(!empty($arrConstant['constant']) && is_string($arrConstant['constant']))
			{
				if(!defined($arrConstant['constant'])) define($arrConstant['constant'], $arrConstant['value']);
			}
		}
		return true;
	}# setConstants
	#--------------------------------------------------------------------------------------------------------	
}
?>