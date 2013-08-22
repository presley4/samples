<?PHP
//ąćę

/**
* CWidget
*
* @version		1.2
* @since		2012-08-08
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
* 2011-05-30	created
* 2011-10-03	upd: based on v10
* 2011-11-19	add: /psTemplateFilePath/
* 2011-11-19	upd: /setTemplate/ params sTemplateFilePath
* 2012-01-05	upd: /getOutput/ if structure
* 				add: /render/ default render method
* 2012-08-08	add: /arrParams/
*
*/

class CWidget 
{
	public $psRefKey = '';
	public $psTemplate = '';
	public $psTemplateFilePath = '';
	public $poDb = NULL;
	public $parrSettings = array();
	public $parrParserVals = array();	
	public $parrParams = array();	
	
	public function __construct()
	{
		$this->psRefKey = '';
		$this->psTemplate = '';
		$this->psTemplateFilePath = '';
		$this->poDb = NULL;
		$this->parrSettings = array();
		$this->parrParserVals = array();
		$this->parrParams = array();
				
		return true;
	}
	
	public function __destruct()
	{
		return true;
	}
	#---------------------------------------------------------------------------------------------------------

	/**
	* setSettingsRefKey
	* returns true
	*
	* @param	string		$sRefKey				key
	* @return	bool
	*/
	function setSettingsRefKey($sRefKey = '')
	{
		$this->psRefKey = $sRefKey;
		return true;
	}# setSettingsRefKey
	#---------------------------------------------------------------------------------------------------------
	
    /**
	* setTemplate
	* sets template for the widget 
	*
    * @param	string		$sTemplate				template html code
	* @param	string		$sTemplateFilePath		optional template path (for information purposes)
    * @return	bool
    */
	public function setTemplate($sTemplate = '', $sTemplateFilePath = '')
	{
		$this->psTemplate = $sTemplate;
		$this->psTemplateFilePath = $sTemplateFilePath;
		return true;
	}# setTemplate
	#---------------------------------------------------------------------------------------------------------

	/**
	* setSettings
	* sets widget settings 
	*
	* @param	array		$arrSettings			settings
	* @return	bool
	*
	* @since	2011-07-06
	*/
	function setSettings($arrSettings = array())
	{
		$this->parrSettings = $arrSettings;
		return true;
	}# setSettings
	#--------------------------------------------------------------------------------------------------------

	/**
	* setParams
	* sets widget params
	*
	* @param	array		$arrParams				params
	* @return	bool
	*
	* @since	2012-08-08
	*/
	function setParams($arrParams = array())
	{
		$this->parrParams = $arrParams;
		return true;
	}# arrParams
	#--------------------------------------------------------------------------------------------------------
		
    /**
	* getOutput
	*
    * @return	string		returns parsed template with prepared data
	*
	* @since	2012-01-05
    */
	public function getOutput()
	{
		$OUTPUT = '';

		if(method_exists($this, 'prepareItemSet')) $this->prepareItemSet();
		if(method_exists($this, 'render')) $OUTPUT = $this->render();

		return $OUTPUT;
	}# getOutput		
	#---------------------------------------------------------------------------------------------------------

	/**
	* render
	* returns parsed template
	*
    * @param	string		$sTemplate				optional template, if null, gets from template class property
    * @return	string
	*
	* @since	2011-12-27
    */
	public function render($sTemplate = NULL)
	{
		if(is_null($sTemplate)) $sTemplate = $this->psTemplate;

		$OUTPUT = $sTemplate; //CParser::parseArgVar($sTemplate, $tpldefs);
				
		if(isset($_REQUEST['identify'])) identifyBlok(__METHOD__, "template=".$this->psTemplateFilePath, $OUTPUT);
		return $OUTPUT;
	}# render	
	#--------------------------------------------------------------------------------------------------------	
	#---------------------------------------------------------------------------------------------------------
	#---------------------------------------------------------------------------------------------------------
}
?>