<?PHP
//ąćę

/**
* CWidgetTemplate
*
* @version		1.0
* @since		2012-01-05
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
* @uses 		DB
* @uses 		CCommon
* @uses 		CEngine
* @uses			mainParser
*
* 2012-01-05	created
*
*/

require_once('mainParser.php');
require_once('class_Engine.php');
require_once('class_Common.php');
require_once('class_Widget.php');

class CWidgetTemplate extends CWidget
{
	public function __construct()
	{
		return true;
	}
	
	public function __destruct()
	{
		return true;
	}
	#----------------------------------------------------------------------------------------------

	/**
	* prepareItemSet
	*
    * @return	bool
	* @uses		class DB
	* @uses		global $section_id
	*
	* @since	2011-12-27
    */
	public function prepareItemSet()
	{
		global $DB;
		global $PARSERVALS;
		$this->parrItemSet = array();

		return true;
	}# prepareItemSet
	#--------------------------------------------------------------------------------------------------------
	
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
		global $PARSERVALS;
		global $DB;
		if(is_null($sTemplate)) $sTemplate = $this->psTemplate;

		$OUTPUT = $sTemplate;
				
		if(isset($_REQUEST['identify'])) identifyBlok(__METHOD__, "template=".$this->psTemplateFilePath, $OUTPUT);
		return $OUTPUT;
	}# render	
	#--------------------------------------------------------------------------------------------------------
	
	/**
	* _placement_ITEM_LIST
	* returns parsed template
	*
    * @param	string		$sParams				list of parameters for placement, ex. id=10;count=4;
    * @return 	string	
	* @uses		CEngine::templateParseParams
	* @uses		CEngine::templateMatch
	*
	* @since	
    */
	public function _placement_ITEM_LIST($sParams = '')
	{
		$arrParams = CCommon::parseParamString($sParams);
		global $PARSERVALS;
		global $DB;

		$OUTPUT = '';

		# param : hide
		$BLOK_hide = $arrParams['hide'];
		if(!empty($BLOK_hide)) return '';
		
		# param : label
		$BLOK_label = $arrParams['label'];
		if (empty($BLOK_label)) $BLOK_label = 0;
	
		# param : template
		$BLOK_template_arr = CEngine::templateParseParams($arrParams);

		$iItemNum = 0;
		foreach($this->parrItemSet as $item)
		{
			$tpldefs = array(	
								'URL'							=> $item['URL'],
						);
								
			$sTemplate = CEngine::templateMatch($BLOK_template_arr, $iItemNum);
			if(!empty($sTemplate)) $OUTPUT .= mainParser($tpldefs, $sTemplate);
			
			$iItemNum++;
		}
		return $OUTPUT;
	}# _placement_ITEM_LIST	
	#--------------------------------------------------------------------------------------------------------
}
?>