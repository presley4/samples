<?PHP
//ąćę

/**
* SYSTEM
* system placements
* 
*
* @version		1.1
* @since		2013-08-20
* @package		dms
* @uses			global $DB
* @uses			...
*
* 2013-06-14	created
* 2013-08-20	add: /ALERTS/
*				add: /ALERTS_LIST/
*
*/


#--------------------------------------------------------------------------------------------------------
#	SOURCES
#--------------------------------------------------------------------------------------------------------
function SOURCES($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
		
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	#param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';
	
	#param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;

	# param : count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
	$PARSERVALS['SOURCES']['count'] = $BLOK_count;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\b(no|none)\b#i', $BLOK_user)) $BLOK_user = NULL;
	$PARSERVALS['SOURCES']['user'] = $BLOK_user;
	
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	$template = CEngine::templateMatch($BLOK_template_arr);

	$iTotal = $DB->getVal("SELECT COUNT(*) FROM ".TABLE_SOURCES);
	
	$tpldefs = array(	
						'SOURCES_COUNT'					=> $iTotal,
						'ITEM_LIST'						=> 'call SOURCES_LIST',
						'PAGES'							=> 'call PAGES',
					);
	if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
	
	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));
	
	return $OUTPUT;
}# SOURCES

#--------------------------------------------------------------------------------------------------------
#   SOURCES_LIST
#--------------------------------------------------------------------------------------------------------	
function SOURCES_LIST ($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
	
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	# param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';

	# param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;	
		
	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = $PARSERVALS['SOURCES']['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\b(no|none)\b#i', $BLOK_user)) $BLOK_user = $PARSERVALS['SOURCES']['user'];
	if(empty($BLOK_user) || preg_match('#\b(no|none)\b#i', $BLOK_user)) $BLOK_user = NULL;
			
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);

	# paging limits
	$LIMIT = "LIMIT ".(($pg-1)*$BLOK_count).','.$BLOK_count;
	if(preg_match('#\ball\b#i', $BLOK_count)) $LIMIT = '';
	
	$bGrouppedByBlocks = false;
	$SQL_ORDER = " ORDER BY id DESC ";
	$SQL_WHERE_x = $SQL_FROM_x = $SQL_SELECT_x = '';
	if(preg_match('#\bauto\b#i', $BLOK_user))
	{
		$iUserId = $_SESSION[SESNAME]['user']['user_id'];
		$iHubId = $PARSERVALS['REF']['hub_id'];
		
		if(empty($iHubId)|| !is_numeric($iHubId) || $iHubId < 0 || $iUserId || !is_numeric($iUserId)) return CLang::get('DESC_ERROR_NOT_LOGGED');
		
		$bGrouppedByBlocks = true;

		$arrSet = $DB->getSet("	SELECT SQL_CALC_FOUND_ROWS b.id AS block_id, b.title AS block_title, b.added AS block_added, s.* , st.name AS source_type_name, st.default_favico_path , h.title AS hub_title
								FROM ".TABLE_BLOCKS." b 
								JOIN ".TABLE_HUBS." h ON b.hub_id = h.id AND h.id = {$iHubId} 
									LEFT JOIN ".TABLE_BLOCKS_X_SOURCES." bxs 
										JOIN ".TABLE_SOURCES." s ON bxs.source_id = s.id 
											LEFT JOIN ".TABLE_SOURCES_TYPES." st ON st.id = s.type_id 
									ON bxs.block_id = b.id 
								ORDER BY b.added DESC;
								");
	}
	else
	{
		$arrSet = $DB->getSet("	SELECT SQL_CALC_FOUND_ROWS s.* , st.name AS source_type_name, st.default_favico_path 
								FROM ".TABLE_SOURCES." s LEFT JOIN ".TABLE_SOURCES_TYPES." st ON st.id = s.type_id 
								{$SQL_ORDER}
								{$LIMIT}
							");
	}
	$DB->debug();
	
	$iTotal = $DB->getVal("SELECT FOUND_ROWS();");

	if(empty($arrSet))
	{
		# not found
		$template = $BLOK_template_arr['sub']['not_found'];
		if(!empty($template)) $OUTPUT .= mainParser(array(),$template);
		return $OUTPUT;
	}	

	$bBlockGroupOpened = false;
	$iBlockId = NULL;
	$i = 0;
	foreach($arrSet as $arrItem)
	{
		
		# prepare filenames and paths for fav icon
		$sFileName = $arrItem['id'] . "_favicon.ico";
		$sFilePathBySourceId = CSource::getUploadPath($arrItem['id']);
		
		$sFullFilePath = ARCHIV_PATH . $sFilePathBySourceId . '/' . $sFileName;
		$sFilePath = ARCHIV_SUBPATH . $sFilePathBySourceId . '/' . $sFileName;
		
		$sSourceFavicoUrl = ARCHIV_URL . $sFilePathBySourceId . '/' . $sFileName;
		if(!file_exists(ARCHIV_PATH . $sFilePathBySourceId . '/' . $sFileName))
		{
			$sSourceFavicoUrl = SITE_URL . "/" . $arrItem['default_favico_path'];
		}

		
		if($bGrouppedByBlocks && $iBlockId != $arrItem['block_id'])
		{
			if($bBlockGroupOpened) $OUTPUT .= $BLOK_template_arr['sub']['block_group_end'];


			$bBlockGroupOpened = true;
			$iBlockId = $arrItem['block_id'];

			$tpldefs = array(	
								'BLOCK_ID'						=> $arrItem['block_id'],
								'BLOCK_TITLE'					=> $arrItem['block_title'],
								'BLOCK_TITLE_SAFE_HTML'			=> CString::getSafeStringForHtml($arrItem['block_title']),
								'CLASS_BLOCK_GROUP_EMPTY'		=> empty($arrItem['id']) ? "block_group_empty" : "",
							);

			$template = $BLOK_template_arr['sub']['block_group_separator'];
			if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);

			$template = $BLOK_template_arr['sub']['block_group_begin'];
			if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
		}
		
		if(!empty($arrItem['id']))
		{
			$tpldefs = array(	
								'ID'							=> $arrItem['id'],
								'ACTIVE'						=> !empty($arrItem['active']) ? 1 : 0,
								'SRC' 							=> $arrItem['src'],
								'LINK_BASE' 					=> $arrItem['link_base'],
								'LANGUAGE' 						=> $arrItem['language'],
								'LAST_UPDATE_ATTEMPT'			=> $arrItem['last_update_attempt'],
								'LAST_UPDATE_STATUS'			=> $arrItem['last_update_status'],
								'FAVICON_ATTEMPTS'				=> $arrItem['favicon_attempts'],
								'ADDED' 						=> $arrItem['added'],
								'UPDATED' 						=> $arrItem['updated'],
								
								'TITLE' 						=> $arrItem['title'],
								'TITLE_SAFE_HTML'				=> CString::getSafeStringForHtml($arrItem['title']),
								
								'USED'							=> $arrItem['used'],
								'CONTENTS_COUNT'				=> $arrItem['contents_count'],
								'WATCH_MARK'					=> $arrItem['watch_mark'],
								'THUMBS_SERVER'					=> $arrItem['thumbs_server'],
	
								'FAVICON_URL'					=> $sSourceFavicoUrl,
								'FAVICON_EXISTS'				=> 1,					
								'FAVICON_SUBPATH'				=> $sFilePath,

								'BLOCK_ID'						=> $arrItem['block_id'],
								'SOURCE_ARCHIVE'				=> CEngine::urlBuild(NULL, NULL, REF_SRC_ID_BLOCK_CONTENT_LIST, $arrItem['block_id'])."?filter_source=".$arrItem['id'],
								
							);
			$template = CEngine::templateMatch($BLOK_template_arr, $i);
			if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
			$i++;
		}
	}# foreach($arrSet
	if($bBlockGroupOpened) $OUTPUT .= $BLOK_template_arr['sub']['block_group_end'];

	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));

	return $OUTPUT;
}# SOURCES_LIST



#--------------------------------------------------------------------------------------------------------
#	CONTACTS
#--------------------------------------------------------------------------------------------------------
function CONTACTS($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;

	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	#param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';
	
	#param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;

	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
	$PARSERVALS['CONTACTS']['count'] = $BLOK_count;

	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	$template = CEngine::templateMatch($BLOK_template_arr);

	$iTotal = $DB->getVal("SELECT COUNT(*) FROM ".DB_REPORTS_CONTACT.".".TABLE_REPORT_FORM_CONTACT);
	
	$tpldefs = array(	
						'CONTACTS_COUNT'				=> $iTotal,
						'ITEM_LIST'						=> 'call CONTACTS_LIST',
					);
	if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
	
	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));
	
	return $OUTPUT;
}# CONTACTS

#--------------------------------------------------------------------------------------------------------
#   CONTACTS_LIST
#--------------------------------------------------------------------------------------------------------	
function CONTACTS_LIST ($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
	
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	# param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';

	# param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;	
		
	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = $PARSERVALS['CONTACTS']['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
			
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);

	# paging limits
	$LIMIT = "LIMIT ".(($pg-1)*$BLOK_count).','.$BLOK_count;
	
	$arrSet = $DB->getSet("	SELECT c.* FROM ".DB_REPORTS_CONTACT.".".TABLE_REPORT_FORM_CONTACT." c ORDER BY c.id DESC {$LIMIT}");	
	$DB->debug();

	$i = 0;
	foreach($arrSet as $arrItem)
	{

		$tpldefs = array(	
							'ID'							=> $arrItem['id'],
							'USER_ID'						=> $arrItem['user_id'],
							'REGISTRATION_SENDER_NAME'		=> $arrItem['registration_name'],
							'REGISTRATION_SENDER_EMAIL'		=> $arrItem['registration_email'],
							'SENDER_NAME'					=> $arrItem['first_last_name'],
							'EMAIL'							=> $arrItem['email'],
							'BODY'							=> $arrItem['body'],
							'IP'							=> $arrItem['user_ip'],
							'ADDED'							=> $arrItem['added'],
						);
		$template = CEngine::templateMatch($BLOK_template_arr, $i);
		if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
		$i++;
	}# foreach($arrSet

	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));

	return $OUTPUT;
}# CONTACTS_LIST



#--------------------------------------------------------------------------------------------------------
#	ABUSES
#--------------------------------------------------------------------------------------------------------
function ABUSES($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;

	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	#param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';
	
	#param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;

	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
	$PARSERVALS['ABUSES']['count'] = $BLOK_count;

	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	$template = CEngine::templateMatch($BLOK_template_arr);

	$iTotal = $DB->getVal("SELECT COUNT(*) FROM ".DB_REPORTS_ABUSE.".".TABLE_REPORT_FORM_ABUSE);
	
	$tpldefs = array(	
						'ABUSES_COUNT'					=> $iTotal,
						'ITEM_LIST'						=> 'call ABUSES_LIST',
					);
	if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
	
	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));
	
	return $OUTPUT;
}# ABUSES



#--------------------------------------------------------------------------------------------------------
#   ABUSES_LIST
#--------------------------------------------------------------------------------------------------------	
function ABUSES_LIST ($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
	
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	# param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';

	# param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;	
		
	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = $PARSERVALS['ABUSES']['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
			
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);

	# paging limits
	$LIMIT = "LIMIT ".(($pg-1)*$BLOK_count).','.$BLOK_count;
	
	$arrSet = $DB->getSet("	SELECT a.* FROM ".DB_REPORTS_ABUSE.".".TABLE_REPORT_FORM_ABUSE." a ORDER BY a.id DESC {$LIMIT}");	
	$DB->debug();

	$i = 0;
	foreach($arrSet as $arrItem)
	{

		$tpldefs = array(	
							'ID'							=> $arrItem['id'],
							'USER_ID'						=> $arrItem['user_id'],
							'REGISTRATION_SENDER_NAME'		=> $arrItem['registration_name'],
							'REGISTRATION_SENDER_EMAIL'		=> $arrItem['registration_email'],
							'SENDER_NAME'					=> $arrItem['first_last_name'],
							'EMAIL'							=> $arrItem['email'],
							'URL'							=> $arrItem['url'],
							'BODY'							=> $arrItem['body'],
							'IP'							=> $arrItem['user_ip'],
							'ADDED'							=> $arrItem['added'],
							'ABUSE_TYPE_ID'					=> $arrItem['type_id'],
							'ABUSE_NAME'					=> CCommon::getAbuseName($arrItem['type_id'],'pl'),
						);
		$template = CEngine::templateMatch($BLOK_template_arr, $i);
		if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
		$i++;
	}# foreach($arrSet

	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));

	return $OUTPUT;
}# ABUSES_LIST



#--------------------------------------------------------------------------------------------------------
#	ALERTS
#--------------------------------------------------------------------------------------------------------
function ALERTS($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
		
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	#param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';
	
	#param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;

	# param : count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;
	$PARSERVALS['ALERTS']['count'] = $BLOK_count;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $_SESSION[SESNAME]['user']['user_id'];
	$PARSERVALS['ALERTS']['user'] = $BLOK_user;
	
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	$template = CEngine::templateMatch($BLOK_template_arr);

	$tpldefs = array(	
						'ITEM_LIST'						=> 'call ALERTS_LIST',
						'PAGES'							=> 'call PAGES',
					);
	if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
	
	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));
	
	return $OUTPUT;
}# ALERTS


#--------------------------------------------------------------------------------------------------------
#   ALERTS_LIST
#--------------------------------------------------------------------------------------------------------
function ALERTS_LIST ($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
	
	$iTimeStart = CCommon::begin();
	
	$OUTPUT = '';
	
	# param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';

	# param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;	
		
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	
	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = $PARSERVALS['ALERTS']['count'];
	if(empty($BLOK_count)) $BLOK_count = 20;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $PARSERVALS['ALERTS']['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $_SESSION[SESNAME]['user']['user_id'];
	if(empty($BLOK_user) || !is_numeric($BLOK_user)) $BLOK_user = NULL;
	if(empty($BLOK_user)) return CLang::get('DESC_ERROR_NOT_LOGGED');
	$iUserId = $BLOK_user;
	
	#reklama rect listing path
	$BLOK_reklama_listing_path = $arrParams['rectpath'];
	if(empty($BLOK_reklama_listing_path)) $BLOK_reklama_listing_path = trim($PARSERVALS['ALERTS']['rectpath']);
	if(empty($BLOK_reklama_listing_path)) $BLOK_reklama_listing_path = @constant('AD_RECT_LISTING_PATH_DEFAULT');
	
	$RECT_DATA = '';
	$BLOK_rect_pos = 0;
	$BLOK_rect_on = $BLOK_rect_x = $BLOK_rect_y = $BLOK_rect_span_x = $BLOK_rect_span_y = NULL;
	if(!file_exists($BLOK_reklama_listing_path) || !is_file($BLOK_reklama_listing_path))
	{
		$BLOK_rect_on = 0;
	}else{
		#reklama rect on 
		$arrParams['recton'] = empty($arrParams['recton'])&& $arrParams['recton']!='0' ? $PARSERVALS['ALERTS']['recton'] : $arrParams['recton'];
		$BLOK_rect_on = $arrParams['recton'];

		if( (empty($BLOK_rect_on)&&$BLOK_rect_on!='0') || preg_match('#1|yes#i',$BLOK_rect_on) ) { $BLOK_rect_on = 1; }else{ $BLOK_rect_on = 0; }

		if($BLOK_rect_on)
		{
			$RECT_DATA = CFileIO::getIncludeContents($BLOK_reklama_listing_path);	
			#reklama rect position in list
			$BLOK_rect_pos = $arrParams['rectpos'];
			if(empty($BLOK_rect_pos) && $BLOK_rect_pos != '0') $BLOK_rect_pos = @constant("AD_RECT_LISTING_ARCHIV_POS_DEFAULT");
			if(empty($BLOK_rect_pos) && $BLOK_rect_pos != '0') $BLOK_rect_pos = 3;
		}
	}

	
	# param : crop
	switch(true)
	{	
		case preg_match('#none#i',$arrParams['crop']):
			$BLOK_crop = NULL;
			break;
		case preg_match('#([0-9]+)#',$arrParams['crop'],$m): #266
			$BLOK_crop = $m[1];
			break;
		default:
			$BLOK_crop = 210;
	}

	# param : cropbody
	switch(true){	
		case preg_match('#\b(none|no|0)\b#i',$arrParams['cropbody']):
			$BLOK_cropbody = NULL;
			break;
		case preg_match('#([0-9]+)#',$arrParams['cropbody'],$m): #266
			$BLOK_cropbody = $m[1];
			break;
		default:
			$BLOK_cropbody = 90;
	}
	
	# paging limits
	$iLimit = $BLOK_count;
	$iOffset = ($pg-1)*$BLOK_count;	
	if($iOffset<0 || !is_numeric($iOffset)) $iOffset = 0;
	
	$LIMIT = "LIMIT ".(($pg-1)*$BLOK_count).','.$BLOK_count;

	$arrAlerts = $DB->getSet("	SELECT SQL_CALC_FOUND_ROWS a.*, axu.phrase_original 
								FROM ".TABLE_ALERTS." a 
									JOIN ".TABLE_ALERTS_X_USERS." axu ON axu.user_id = {$iUserId} AND a.id = axu.alert_id
								ORDER BY added DESC 
								{$LIMIT}
							");
	$DB->debug();

	$iTotal = $DB->getVal("SELECT FOUND_ROWS()");

	if(empty($arrAlerts))
	{
		# not found
		$template = $BLOK_template_arr['sub']['not_found'];
		if(!empty($template)) $OUTPUT .= mainParser(array(),$template);
		return $OUTPUT;
	}	

	# handle abusive
	$iSourcesWatchMark = 0;
	$iSourcesAbusiveCount = 0;
	$arrSourcesAbusive = array();
	$iContentsWatchMark = 0;
	$iContentsAbusiveCount = 0;
	
	
	$sItemIdList = '';
	foreach($arrAlerts as $arrItem) $sItemIdList .= (!empty($sItemIdList)?",":"") . $arrItem['item_id'];
	
	if(!empty($arrAlerts))
	{	
		$sSourceIdList = '';
		foreach($arrAlerts as $arrItem) $sSourceIdList .= (!empty($sSourceIdList)?",":"") . $arrItem['source_id'];
		if(!empty($sSourceIdList))
		{
			$arrContentSources = $DB->getSet("
												SELECT 
													s.id AS source_id, s.title AS source_title, s.link_base AS source_link_base, s.watch_mark AS source_watch_mark, s.thumbs_server, 
													st.name AS source_type_name, st.default_favico_path  
												FROM ".TABLE_SOURCES." s 
													LEFT JOIN ".TABLE_SOURCES_TYPES." st ON st.id = s.type_id 
												WHERE s.id IN ({$sSourceIdList})
											", NULL, 'source_id');
			$DB->debug();
			for($i=0;$i<count($arrAlerts); $i++)
			{
				$iContentsWatchMark |= $arrAlerts[$i]['watch_mark'];
				if($arrAlerts[$i]['watch_mark'] > 0+@constant('WATCH_MARK_SUSPICIOUS')) $iContentsAbusiveCount++;
				$iSourcesWatchMark |= $arrContentSources[$arrAlerts[$i]['source_id']]['source_watch_mark'];
				if($arrContentSources[$arrAlerts[$i]['source_id']]['source_watch_mark'] > 0+@constant('WATCH_MARK_SUSPICIOUS') && !in_array($arrAlerts[$i]['source_id'], $arrSourcesAbusive)) $arrSourcesAbusive[] = $arrAlerts[$i]['source_id'];
								
				if(is_array($arrAlerts[$i]) && is_array($arrContentSources[$arrAlerts[$i]['source_id']])) $arrAlerts[$i] = array_merge($arrAlerts[$i], $arrContentSources[$arrAlerts[$i]['source_id']]);
			}
			$iSourcesAbusiveCount = count($arrSourcesAbusive);
		}
	}

	# handle abusive
	$iListingWatchMark = $iSourcesWatchMark | $iContentsWatchMark;
	if($iContentsAbusiveCount <= @constant('AD_CONTENTS_ABUSIVE_COUNT_THRESHOLD') && $iSourcesAbusiveCount <= @constant('AD_SOURCES_ABUSIVE_COUNT_THRESHOLD')) $iListingWatchMark = 0;

	$arrCacheSourceFavico = array();
	
	$BLOK_found_rows = count($arrAlerts);
	$i = 0;
	$j = 0;
	foreach($arrAlerts as $arrItem)
	{
		if(!empty($arrItem['source_hub_id']) && is_numeric($arrItem['source_hub_id']))
		{
			$arrHub = CHub::getItem($arrItem['source_hub_id']);
			$sHubUrl = CEngine::urlBuild(NULL, NULL, REF_SRC_ID_HUB, $arrItem['source_hub_id']);
		}else $sHubUrl = '';

		$sBody = CString::crop($arrItem['body'], $BLOK_cropbody);
		
		# prepare filenames and paths for fav icon
		$sFileName = $arrItem['source_id'] . "_favicon.ico";
		$sFilePathBySourceId = CSource::getUploadPath($arrItem['source_id']);
		
		$sFullFilePath = ARCHIV_PATH . $sFilePathBySourceId . '/' . $sFileName;
		$sFilePath = ARCHIV_SUBPATH . $sFilePathBySourceId . '/' . $sFileName;

		if(!isset($arrCacheSourceFavico[$arrItem['source_id']]['url']))
		{
			$sSourceFavicoUrl = ARCHIV_URL . $sFilePathBySourceId . '/' . $sFileName;
			if(!file_exists(ARCHIV_PATH . $sFilePathBySourceId . '/' . $sFileName) || empty($sFileName))
			{
				$sSourceFavicoUrl = SITE_URL . "/" . $arrItem['default_favico_path'];
			}
			$arrCacheSourceFavico[$arrItem['source_id']]['url'] = $sSourceFavicoUrl;
			$arrCacheSourceFavico[$arrItem['source_id']]['exists'] = 1;
		}
		
		if(0)
		{
			$sThumbUrl = CUrl::fixHost($arrItem['foreign_image_url'], $arrItem['source_link_base']);
		}else{
			$sThumbServerPath = trim($arrItem['thumbs_server']);
			if(empty($sThumbServerPath))
			{
				$sThumbServerUrl = SITE_URL;
				$sThumbServerPath = SITE_PATH;
			}
			$sThumbSubPath = ARCHIV_SUBPATH . CSource::getThumbPath($arrItem['source_id']);
			$sThumbPath = $sThumbServerPath . $sThumbSubPath . '/' . $arrItem['item_id'] . @constant('ITEM_THUMB_NAME_APPENDIX');
			$sThumbUrl = $sThumbServerUrl . $sThumbSubPath . '/' . $arrItem['item_id'] . @constant('ITEM_THUMB_NAME_APPENDIX');
			if(!empty($arrItem['foreign_image_url']))
			{
				if(!@file_exists($sThumbPath)) $sThumbUrl = CUrl::fixHost($arrItem['foreign_image_url'], $arrItem['source_link_base']);
			}
		}
		
		
				
		# item data for JS
		$arrItemData = array('foreign_image_url'=>$arrItem['foreign_image_url']);
		$sItemData = !empty($arrItemData) ? json_encode($arrItemData) : '';		
		
		$sClassNew = '';
		if($arrItem['added'] >= $_SESSION[SESNAME]['user']['user_last_visit'] && !empty($_SESSION[SESNAME]['user']['user_last_visit'])) $sClassNew = 'new'; //&& $_SESSION[SESNAME]['user']['logged']

		# prepend ad rect
		if($BLOK_rect_on && $j == $BLOK_rect_pos && !empty($RECT_DATA))
		{
			$BLOK_rect_on = 0;
			$OUTPUT .= mainParser(array('WATCH_MARK'=>$iListingWatchMark), $RECT_DATA);
		}
		
		$sClassBookmark = 'set';
						
		$tpldefs = array(	
							'PHRASE_ORIGINAL'				=> $arrItem['phrase_original'],
							'URL'							=> $arrItem['url'],
							'TITLE' 						=> $arrItem['title'],
							'TITLE_SAFE_HTML'				=> CString::getSafeStringForHtml($arrItem['title']),
							'BODY'							=> $sBody,
							'PUBDATE'						=> $arrItem['pubdate'],
							'THUMB_URL'						=> $sThumbUrl,
							'THUMB_EXISTS'					=> !empty($arrItem['foreign_image_url']) ? 1 : 0,
							'HUB_URL'						=> $sHubUrl,
							'HUB_URL_EXISTS'				=> !empty($sHubUrl)?1:0,
							'HUB_TITLE'						=> CString::getSafeStringForHtml(trim($arrHub['title'])),

							'THUMB_STYLE'					=> $sThumbStyle,
							'PUBDATE_STYLE'					=> $sPubDateStyle,
							'TITLE_STYLE'					=> $sTitleStyle,
							'BODY_STYLE'					=> $sBodyStyle,
							'FAVICON_STYLE'					=> '',
							
							'THUMB_CLASS'					=> $sThumbClass,
							'PUBDATE_CLASS'					=> $sPubDateClass,
							'TITLE_CLASS'					=> $sTitleClass,
							'BODY_CLASS'					=> $sBodyClass,

							'SOURCE_FAVICON_URL'			=> $arrCacheSourceFavico[$arrItem['source_id']]['url'],
							'SOURCE_FAVICON_EXISTS'			=> $arrCacheSourceFavico[$arrItem['source_id']]['exists'] ? 1 : 0,							
							'SOURCE_FAVICON_SUBPATH'		=> $sFilePath,
							'SOURCE_TITLE'					=> $arrItem['source_title'],
							'SOURCE_URL'					=> $arrItem['source_link_base'],

							'ITEM_CLASS_NEW'				=> $sClassNew,
							'ITEM_CLASS_BOOKMARK'			=> $sClassBookmark,
							'ITEM_ID'						=> $arrItem['item_id'],
							'ITEM_DATA'						=> $sItemData,
							'ITEM_DATA_EXISTS'				=> empty($sItemData) ? 0 : 1,
						);
	
		$template = CEngine::templateMatch($BLOK_template_arr, $i);
		if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
		$i++;
		$j++;

		# append ad rect
		if($BLOK_rect_on && $j>=$BLOK_found_rows && !empty($RECT_DATA))
		{
			$BLOK_rect_on = 0;
			$OUTPUT .= mainParser(array('WATCH_MARK'=>$iListingWatchMark), $RECT_DATA);
		}		
	}# foreach($arrContent

	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));

	return $OUTPUT;
}# ALERTS_LIST



#--------------------------------------------------------------------------------------------------------
#	ALERTS_DEFINITIONS
#--------------------------------------------------------------------------------------------------------
function ALERTS_DEFINITIONS($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
		
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	#param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';
	
	#param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;

	# param : count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = 100;
	$PARSERVALS['ALERTS_DEFINITIONS']['count'] = $BLOK_count;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $_SESSION[SESNAME]['user']['user_id'];
	$PARSERVALS['ALERTS_DEFINITIONS']['user'] = $BLOK_user;
	
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);
	$template = CEngine::templateMatch($BLOK_template_arr);

	$tpldefs = array(	
						'ITEM_LIST'						=> 'call ALERTS_DEFINITIONS_LIST',
						'PAGES'							=> 'call PAGES',
					);
	if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
	
	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));
	
	return $OUTPUT;
}# ALERTS_DEFINITIONS


#--------------------------------------------------------------------------------------------------------
#   ALERTS_DEFINITIONS_LIST
#--------------------------------------------------------------------------------------------------------	
function ALERTS_DEFINITIONS_LIST ($sParams = '')
{
	$arrParams = CCommon::parseParamString($sParams);
	global $PARSERVALS;
	global $pg;
	global $DB;
	
	$iTimeStart = CCommon::begin();
	$OUTPUT = '';

	# param : hide
	$BLOK_hide = $arrParams['hide'];
	if(!empty($BLOK_hide)) return '';

	# param : label
	$BLOK_label = $arrParams['label'];
	if (empty($BLOK_label)) $BLOK_label = 0;	
		
	# param count
	$BLOK_count = $arrParams['count'];
	if(empty($BLOK_count)) $BLOK_count = $PARSERVALS['ALERTS_DEFINITIONS']['count'];
	if(empty($BLOK_count)) $BLOK_count = 100;

	# param : user
	$BLOK_user = $arrParams['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $PARSERVALS['ALERTS_DEFINITIONS']['user'];
	if(empty($BLOK_user) || preg_match('#\bauto\b#i', $BLOK_user)) $BLOK_user = $_SESSION[SESNAME]['user']['user_id'];
	if(empty($BLOK_user) || !is_numeric($BLOK_user)) $BLOK_user = NULL;
	if(empty($BLOK_user)) return CLang::get('DESC_ERROR_NOT_LOGGED');
	$iUserId = $BLOK_user;
	
	# param :template
	$BLOK_template_arr = CEngine::templateParseParams($arrParams);

	# paging limits
	$LIMIT = "LIMIT ".(($pg-1)*$BLOK_count).','.$BLOK_count;
	if(preg_match('#\ball\b#i', $BLOK_count)) $LIMIT = '';
	
	$arrSet = $DB->getSet("	SELECT p.*, pxu.phrase_original, pxu.added AS pxu_added 
							FROM ".TABLE_ALERTS_PHRASES." p 
								JOIN ".TABLE_ALERTS_PHRASES_X_USERS." pxu ON pxu.phrase_id = p.id AND pxu.user_id = {$iUserId} 
							ORDER BY pxu.added DESC
						");
	$DB->debug();

	if(empty($arrSet))
	{
		# not found
		$template = $BLOK_template_arr['sub']['not_found'];
		if(!empty($template)) $OUTPUT .= mainParser(array(),$template);
		return $OUTPUT;
	}	

	$i = 0;
	foreach($arrSet as $arrItem)
	{
		
		$tpldefs = array(	
							'ID'							=> $arrItem['id'],
							'PHRASE' 						=> $arrItem['phrase_original'],
							'ALERTS_COUNT'					=> $arrItem['cnt_pid'],
							'USED'							=> $arrItem['used'],
							'ADDED'							=> $arrItem['pxu_added'],
							//'SOURCE_ARCHIVE'				=> CEngine::urlBuild(NULL, NULL, REF_SRC_ID_BLOCK_CONTENT_LIST, $arrItem['block_id'])."?filter_source=".$arrItem['id'],
						);
		$template = CEngine::templateMatch($BLOK_template_arr, $i);
		if(!empty($template)) $OUTPUT .= mainParser($tpldefs, $template);
		$i++;

	}# foreach($arrSet

	if(isset($_REQUEST['identify'])) identifyBlok(__FUNCTION__, $sParams, $OUTPUT, array('duration'=>CCommon::finish($iTimeStart)));

	return $OUTPUT;
}# ALERTS_DEFINITIONS_LIST
?>