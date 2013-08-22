<?PHP
//ąćę

/**
* @version		1.1
* @since		2011-10-03
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
* @uses			CFileIO::getData
* @uses			CSettings::get
* @uses			CRss
* @uses			CEngine::templateParseParams
* @uses			CEngine::templateMatch
* @uses			CCommon::parseParamString
* @uses			mainParser
*
* 2011-05-30	created
* 2011-10-03	updated
*
*/

require_once('mainParser.php');
require_once('class_Widget.php');
require_once('class_Rss.php');
require_once('class_FileIO.php');
require_once('class_Settings.php');
require_once('class_Common.php');
require_once('class_Engine.php');

class CWidgetRss extends CWidget
{
	public $psRssUrl = '';
	public $piCount = 6;
	public $parrRss = array();
	public $psAllowedLinksRegexpPattern = '';
	public $psDeniedLinksRegexpPattern = '';
	
	public function __construct()
	{
		$this->psRssUrl = '';
		$this->piCount = 6;
		$this->parrRss = array();
		$this->psAllowedLinksRegexpPattern = '';
		$this->psDeniedLinksRegexpPattern = '';
		
		return true;
	}
	
	public function __destruct()
	{
		return true;
	}
	#---------------------------------------------------------------------------------------------------------

    /**
	* getOutput
	*
    * @return	string		returns parsed template with prepared data
    */
	public function getOutput()
	{
		$arrSettings = CSettings::get($this->psRefKey);

		//$class_filename = $settings['class_filename']['value'];
		$sRssUrl = $arrSettings['rss_url']['value'];
		if(isset($arrSettings['count']['value']) && is_numeric($arrSettings['count']['value'])) $this->piCount = $arrSettings['count']['value'];
		if(isset($arrSettings['allowed_links_regexp_pattern']['value'])) $this->psAllowedLinksRegexpPattern = trim($arrSettings['allowed_links_regexp_pattern']['value']);
		if(isset($arrSettings['denied_links_regexp_pattern']['value'])) $this->psDeniedLinksRegexpPattern = trim($arrSettings['denied_links_regexp_pattern']['value']);
		
		$sRss = CFileIO::getData($sRssUrl);
		
		$RSS = new CRss();
		$RSS->parse($sRss);
		$RSS->extractImg();
		$RSS->stripTags();
		$RSS->convertToUTF8();
		$this->parrRss = $RSS->parrRss;

		$OUTPUT = $this->render();
		unset($RSS);
		unset($GTD);
		
		return $OUTPUT;
	}# getOutput
	#---------------------------------------------------------------------------------------------------------	
	
    /**
	* render
	*
    * @param	string		$sTemplate			optional template, if null, gets template class property
    * @return	string 		returns parsed template
    */
	public function render($sTemplate = NULL)
	{
		global $PARSERVALS;
		if(is_null($sTemplate)) $sTemplate = $this->psTemplate;
		
		$OUTPUT = '';
		
		$tpldefs = array(	
							'ITEM_LIST'					=> 'call $_this->_placement_ITEM_LIST',
						);
		if(!empty($sTemplate)) $OUTPUT = mainParser($tpldefs, $sTemplate, NULL, $this);

		return $OUTPUT;
	}# render
	#---------------------------------------------------------------------------------------------------------

    /**
	* _placement_ITEM_LIST
	*
    * @return	string
    */	
	public function _placement_ITEM_LIST($sParams = '')
	{
		$arrParams = CCommon::parseParamString($sParams);
		global $PARSERVALS;
		
		if(!is_array($this->parrRss)) return '';
		
		# param : hide
		$BLOK_hide = $arrParams['hide'];
		if(!empty($BLOK_hide)) return '';

		# param : label
		$BLOK_label = $arrParams['label'];
		if (empty($BLOK_label)) $BLOK_label = 0;
	
		# param : template
		$BLOK_template_arr = CEngine::templateParseParams($arrParams);
		$template = CEngine::templateMatch($BLOK_template_arr);		
			
		$OUTPUT = '';
		
	
		$i = 0;
		foreach($this->parrRss as $arrItem)
		{
			if(empty($arrItem)) continue;
			if(!empty($this->psDeniedLinksRegexpPattern))
			{
				if(preg_match($this->psDeniedLinksRegexpPattern, $arrItem['link'])) continue;
			}			
			if(!empty($this->psAllowedLinksRegexpPattern))
			{
				if(!preg_match($this->psAllowedLinksRegexpPattern, $arrItem['link'])) continue;
			}
			$tpldefs = array(
								'URL'						=> $arrItem['link'],
								'LABEL'						=> $arrItem['title'],
								'BODY'						=> $arrItem['description'],
								
								'NUMZ'						=> $i,
								'NUM'						=> $i+1,
								'FIRST'						=> $i == 0 ? 1 : 0,
								'LAST'						=> $i+1 >= count($this->parrRss) ? 1 : 0,								
							
							);		
			$OUTPUT .= mainParser($tpldefs, $template);

			$i++;
			if($i >= $this->piCount) break;
		}
		
		return $OUTPUT;
	}# _placement_ITEM_LIST
	#---------------------------------------------------------------------------------------------------------
	#---------------------------------------------------------------------------------------------------------
}
?>