<?PHP
//ąćę

/**
* @version		1.1
* @since		2011-10-17
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
* @uses			CCache
* @uses			CXml
* @uses			CFileIO::readFile
* @uses			CFileIO::getData
* @uses			CEngine::templateParseParams
* @uses			CEngine::templateMatch
* @uses			CCommon::parseParamString
* @uses			CParser::parseArgVar
* @uses			mainParser
*
* 2011-10-04	created
* 2011-10-12	add: read and cache source file
* 2011-10-13	add: /_placement_PRODUCT_LIST/
*				upd: new method of xml parsing and rendering
* 2011-10-17	upd: products links
*/

require_once('mainParser.php');
require_once('class_Widget.php');
require_once('class_FileIO.php');
require_once('class_Settings.php');
require_once('class_Parser.php');
require_once('class_Engine.php');
require_once('class_XML.php');
require_once('class_Cache.php');
require_once('class_Common.php');

class CWidgetRenderXml extends CWidget
{
	var $arrProductList = array();
	
	public function __construct()
	{
		$this->arrProductList = array();
		
		return true;
	}
	
	public function __destruct()
	{
		return true;
	}
	#---------------------------------------------------------------------------------------------------------

    /**
	* prepareItemSet
	*
    * @return	bool
    */
	public function prepareItemSet()
	{

		$arrSettings = $this->parrSettings;

		$sXmlSrcPath = $arrSettings['src_path']['value'];
		$sXmlSrcPath = CParser::parseArgVar($sXmlSrcPath);
		
		$sUrlPrefix = $arrSettings['url_prefix']['value'];

		$iCount = $arrSettings['count']['value'];
		if(empty($iCount)) $iCount = 4;

		$mCacheExpire = $arrSettings['cache_expire']['value'];
		if(empty($mCacheExpire)) $mCacheExpire = 60;
		if(file_exists($sXmlSrcPath) && !preg_match('#^http://#i', $sXmlSrcPath))
		{
			# read from file
			$sXml = CFileIO::readFile($sXmlSrcPath);
		}
		else
		{
			# read from http
			# use cache
			$sXml = '';
			if(class_exists('CCache'))
			{
				$sCacheKey = $this->psRefKey;
				if(is_array($this->psRefKey)) $sCacheKey = implode(',', $this->psRefKey);
				
				$oCache = new CCache(array('expire'=>$mCacheExpire, 'key'=>$sCacheKey));
				if($CACHED = $oCache->get()) $sXml = $CACHED;
			}	
			
			# get data if needed
			if(empty($sXml))
			{
				$oFileIO = new CFileIO();
				$sXml = $oFileIO->getData($sXmlSrcPath);
				$iError = $oFileIO->pGetDataError;
				
				if(class_exists('CCache') && is_object($oCache))
				{
					if(empty($iError))
					{
						# if no erros store xml in cache
						$oCache->store($sXml);
					}else{
						# if error occured while getting xml, read xml from previously stored cache
						$sXml = $oCache->getForced();
					}
				}
				unset($oFileIO);
			}
			unset($oCache);
		}
		
		if(empty($sXml)) return false;
		
		$arrXml = simplexml_load_string($sXml, 'SimpleXMLElement', LIBXML_NOCDATA);

		foreach($arrXml->children() as $arrItem)
		{
			$arrProducts = array();

			$iProductCount = $arrItem->product->count();
			if($iProductCount > 1)
			{
				for($i=0; $i<$iProductCount; $i++)
				{
					$arrProductItemAttributes = $arrItem->product[$i]->attributes();

					$arrProducts[] = array(
											'id'							=> (string)$arrProductItemAttributes['id'],
											'name'							=> (string)$arrProductItemAttributes['name'],
											'url'							=> (string)$arrProductItemAttributes['link'],
											'url_full'						=> $sUrlPrefix . (string)$arrProductItemAttributes['link'],
											'logo_url'						=> (string)$arrProductItemAttributes['logo'],
											'logo_url_full'					=> $sUrlPrefix . (string)$arrProductItemAttributes['logo'],
										);
				}
			}
			else
			{
				$arrProductItemAttributes = $arrItem->product->attributes();
				$arrProducts[] = array(
										'id'							=> (string)$arrProductItemAttributes['id'],
										'name'							=> (string)$arrProductItemAttributes['name'],
										'url'							=> (string)$arrProductItemAttributes['link'],
										'url_full'						=> $sUrlPrefix . (string)$arrProductItemAttributes['link'],										
										'logo_url'						=> (string)$arrProductItemAttributes['logo'],
										'logo_url_full'					=> $sUrlPrefix . (string)$arrProductItemAttributes['logo'],
									);
			}
			
			$arrItemAttributes = $arrItem->attributes();

			$this->parrItemSet[] = array(
											'id'							=> (string)$arrItemAttributes['id'],
											'name'							=> (string)$arrItemAttributes['name'],
											'url'							=> (string)$arrItemAttributes['link'],
											'url_full'						=> $sUrlPrefix . (string)$arrItemAttributes['link'],										
											'logo_url'						=> (string)$arrItemAttributes['logo'],
											'logo_url_full'					=> $sUrlPrefix . (string)$arrItemAttributes['logo'],
											'description'					=> (string)$arrItem->characteristic,
											'products'						=> $arrProducts,
											
										);

			
		}
		
		$this->parrItemSet = array_slice($this->parrItemSet, 0, $iCount);

		unset($arrXml);
		
		return true;
	}# prepareItemSet
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
		if(isset($_REQUEST['identify'])) identifyBlok(__METHOD__, "template=".$this->psTemplateFilePath, $OUTPUT);

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

		if(!is_array($this->parrItemSet)) return '';
		
		# param : hide
		$BLOK_hide = $arrParams['hide'];
		if(!empty($BLOK_hide)) return '';

		# param : label
		$BLOK_label = $arrParams['label'];
		if (empty($BLOK_label)) $BLOK_label = 0;
	
		# param : template
		$BLOK_template_arr = CEngine::templateParseParams($arrParams);
		$template = CEngine::templateMatch($BLOK_template_arr);		


		# param : template
		$BLOK_count = $arrParams['count'];
		if(empty($BLOK_count)) $BLOK_count = 4;


		$OUTPUT = '';
		
		$arrItemSet = $this->parrItemSet;
		
		$i = 0;
		foreach($arrItemSet as $arrItem)
		{
			
			$this->arrProductList = $arrItem['products'];
			$tpldefs = array(
								'URL'							=> $arrItem['url'],
								'URL_FULL'						=> $arrItem['url_full'],
								'OFFERER_LABEL'					=> html_entity_decode($arrItem['name'], NULL, 'utf-8'),
								'DESCRIPTION'					=> html_entity_decode($arrItem['description'], NULL, 'utf-8'),
								'THUMB_URL'						=> $arrItem['logo_url'],
								'THUMB_URL_FULL'				=> $arrItem['logo_url_full'],
								'PRODUCT_LIST'					=> 'call $_this->_placement_PRODUCT_LIST',
								
								
								'NUMZ'							=> $i,
								'NUM'							=> $i+1,
								'FIRST'							=> $i == 0 ? 1 : 0,
								'LAST'							=> $i+1 >= count($this->arrProductList) ? 1 : 0,								
							
							);		
			$OUTPUT .= mainParser($tpldefs, $template, NULL, $this);
		}
		
		return $OUTPUT;
	}# _placement_ITEM_LIST
	#---------------------------------------------------------------------------------------------------------

    /**
	* _placement_ITEM_LIST
	*
    * @return	string
    */	
	public function _placement_PRODUCT_LIST($sParams = '')
	{
		$arrParams = CCommon::parseParamString($sParams);
		global $PARSERVALS;

		if(!is_array($this->arrProductList)) return '';
		
		# param : hide
		$BLOK_hide = $arrParams['hide'];
		if(!empty($BLOK_hide)) return '';

		# param : label
		$BLOK_label = $arrParams['label'];
		if (empty($BLOK_label)) $BLOK_label = 0;
	
		# param : template
		$BLOK_template_arr = CEngine::templateParseParams($arrParams);
		$template = CEngine::templateMatch($BLOK_template_arr);		

		# param : count
		$BLOK_count = $arrParams['count'];
		if(empty($BLOK_count)) $BLOK_count = trim($this->parrSettings['count_products']['value']);
		if(empty($BLOK_count) || !is_numeric($BLOK_count)) $BLOK_count = 1;
		
		$this->arrProductList = array_slice($this->arrProductList, 0, $BLOK_count);

		$OUTPUT = '';
		
		$i = 0;
		foreach($this->arrProductList as $arrProductItem)
		{
			$tpldefs = array(
								'URL'							=> $arrProductItem['url'],
								'URL_FULL'						=> $arrProductItem['url_full'],
								'PRODUCT_LABEL'					=> html_entity_decode($arrProductItem['name'], NULL, 'utf-8'),
								'NUMZ'							=> $i,
								'NUM'							=> $i+1,
								'FIRST'							=> $i == 0 ? 1 : 0,
								'LAST'							=> $i+1 >= count($this->arrProductList) ? 1 : 0,								
							
							);		
			$OUTPUT .= mainParser($tpldefs, $template);
		}
		
		return $OUTPUT;
	}# _placement_ITEM_LIST
	#---------------------------------------------------------------------------------------------------------	
	#---------------------------------------------------------------------------------------------------------
}
?>