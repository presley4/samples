/**
* Huborama Structure Object
*
* @version		0.9
* @since		2013-02-21
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
* @uses			jQuery
* @uses			oLang
*
*/
var CStructure = {
	STRUCTURE_VERIFY_EXTENDED : 1,
	STRUCTURE_RECURSIVE : 2,
	
	iNextStructureId : 1,
	oSettings : {},
	bIsDragged : false,
	
	init : function(oParams)
	{
		if(oParams.root) this.oSettings.root = typeof(oParams.root) != 'object' ? document.getElementById(oParams.root) : oParams.root;
		this._fixWidth();
		//this._fixVertSeparator();
		CHuborama.onResize();
		return true;
	}, //init
	//--------------------------------------------------------------------------------------------------------
	
	add : function(oParams)
	{
		var oResult = this._add(oParams);
		this._fixWidth();
		
		CHuborama.onResize();
		this._fixHeight();
		CHuborama.showTopInfoProgress();
		if(!oParams.no_save) CHuborama.save();
		
		return true;
	}, //add
	//--------------------------------------------------------------------------------------------------------
	
	
	
	'delete' : function(oClicked, oRef, iOptions)
	{
		var iLeft = parseInt($(oClicked).css('left'), 10);
		var iTop = parseInt($(oClicked).css('top'), 10);

		TINY.box.show({
			html:'<div class="header">'+oLang.LABEL_DELETE_CONFIRM+'</div><div class="yes">'+oLang.LABEL_YES+'</div><div class="no">'+oLang.LABEL_NO+'</div>',
			animate:false,
			close:false,
			mask:false,
			boxid:'popup_confirm_a',
			autohide:5,
			'fixed': false,
			left:iLeft - 170,
			top:iTop +5,
			openjs:function(){
				$('.no', $('#popup_confirm_a')).on('click', null, {clicked:oClicked, ref:oRef}, function(e){
					TINY.box.hide();
				});
				$('.yes', $('#popup_confirm_a')).on('click', null, {clicked:oClicked, ref:oRef}, function(e){
					TINY.box.hide();
					CStructure._delete(e.data.clicked, e.data.ref);
				});
			}
		});
		return;
	}, //delete
	//--------------------------------------------------------------------------------------------------------

	_delete : function(oClicked, oRef, iOptions)
	{
		if(!iOptions) iOptions = 0;
		if(typeof(oRef) == 'string') oRef = document.getElementById(oRef);
		if(!oRef) return false;

		var oParent = oRef.parentNode;
		oParent.removeChild(oRef);
		

		var $arrBlocks = $('div.block', $('#structure > div.structure_container'));
		if($arrBlocks.length == 0)
		{
			$('#structure > div.structure_container').html('');
			CHuborama.disableBlockControls();
			CStructure.add({ref:null, ref_parent:$('#structure > div.structure_container')[0], id:CStructure._generateId('new_'), 'no_save':1});
			CHuborama.enableBlockControls();			
		}
		this.fix(oParent);
		this._fixWidth();
		this._fixVertSeparator();

		CHuborama.onResize();

		CHuborama.showTopInfoProgress();
		CHuborama.save();

		return true;
	}, //_delete
	//--------------------------------------------------------------------------------------------------------

	compact : function(oNode, iLevel)
	{
		var oNode = oNode ? oNode : this.oSettings.root;
		var iLevel = iLevel && !isNaN(iLevel) ? iLevel : 0;
		if(iLevel == 0) var sStructure = '<?xml version="1.0" encoding="UTF-8"?>'; else var sStructure = '';
		
		var iChildNum = 0;
		for(i in oNode.childNodes)
		{
			var oChildNode = oNode.childNodes[i];
			var sNodeType = this._nodeType(oChildNode);
			var sNodeClass = ""+oChildNode.className;

			if(sNodeType) var sContents = this.compact(oChildNode, iLevel + 1);
			switch(sNodeClass.getPregMatch(/(block|structure_column|structure_container)/))
			{
				case 'block':
					sStructure += '<b id="' + oChildNode.id + '"/>';
					break;
				case 'structure_column':
					sStructure += '<v id="' + oChildNode.id + '" width="' + oChildNode.style.width + '">' + sContents + '</v>';
					break;
				case 'structure_container':
					sStructure += '<h id="' + oChildNode.id + '">' + sContents + '</h>';
					break;
			}			
		}
		
		return sStructure;
	}, //compact	
	//--------------------------------------------------------------------------------------------------------



	fix : function(oNode)
	{
		if(!oNode) return false;
		var oParent = oNode.parentNode;

		if(!(sNodeType = this._nodeType(oNode, this.STRUCTURE_VERIFY_EXTENDED))) return false;
		
		// fix orphaned or multiplied vertical separators
		for(i in oNode.childNodes)
		{
			var oChildNode = oNode.childNodes[i];
			if(this._nodeType(oChildNode, this.STRUCTURE_VERIFY_EXTENDED) == 'vs')
			{
				var bRemove = false;
				var oPrevSibling = this._previousSibling(oChildNode);
				var oNextSibling = this._nextSibling(oChildNode);
				if(oPrevSibling && oNextSibling)
				{
					var sPrevious = this._nodeType(oPrevSibling, this.STRUCTURE_VERIFY_EXTENDED);
					var sNext = this._nodeType(oNextSibling, this.STRUCTURE_VERIFY_EXTENDED);
					if( sPrevious != 'v' || sNext != 'v') bRemove = true;
				}else bRemove = true;
				if(bRemove)
				{
					oNode.removeChild(oChildNode);
					if(o = this._firstChild(oNode)) o.className += ' new';
				}
			}
		}
		
		// fix empty
		if(sNodeType != 'b' && this._childrenCount(oNode) == 0) //!oNode.hasChildNodes()
		{
			oParent.removeChild(oNode);
			for(i in oParent.childNodes) if(this._nodeType(oParent.childNodes[i])) oParent.childNodes[i].className += ' new';
			this.fix(oParent);
		}
	
		// fix nested single
		if(sNodeType != 'b' && this._childrenCount(oNode) == 1 &&  this._isOfType(oNode.childNodes.item(0), ['v','h']) ) // this._nodeType(oNode.childNodes.item(0)) != 'b')
		{
			while(this._childrenCount(oNode.childNodes.item(0))>0) oNode.appendChild(oNode.childNodes.item(0).childNodes.item(0));
			this.fix(oNode);
		}

	
		// fix contained
		if(sNodeType == 'h' && (this._nodeType(oNode.parentNode) == 'h' || this._nodeType(oNode.parentNode) == 'v'))
		{
			var bOnlyB = true;
			for(i in oNode.parentNode.childNodes) if(this._isOfType(oNode.parentNode.childNodes[i], ['v','h']) && oNode.parentNode.childNodes[1] === oNode) bOnlyB = false;
			if(bOnlyB)
			{
				for(i in oNode.childNodes) if(this._nodeType(oNode.childNodes[i]) == 'b') oNode.parentNode.insertBefore(oNode.childNodes[i], oNode);
				if(this._nodeType(oNode) != 'b' &&  this._childrenCount(oNode) == 0) // !oNode.hasChildNodes()
				{
					oParent.removeChild(oNode);
					this.fix(oParent);
				}
			}
			
		}

		// fix nested multi columns
		if(sNodeType == 'v' && this._nodeType(oNode.parentNode) == 'h')
		{
			var bFound = false;
			var oSet = {'b':true,'h':true}
			for(i in oNode.childNodes) if(bFound = oSet[this._nodeType(oNode.childNodes[i])]) break;
			if(!bFound)
			{
				while(this._childrenCount(oNode))
				{
					var o = oNode.parentNode.insertBefore(oNode.childNodes.item(0), oNode);
					if(o) o.className += ' new';
				}
				oNode.parentNode.removeChild(oNode);
				this.fix(oParent);
			}
		}

		// fix children
		if(this._childrenCount(oNode)>0) for(i in oNode.childNodes) if(this._nodeType(oNode.childNodes[i])) this.fix(oNode.childNodes[i]);
		
		return true;
	}, //fix
	//--------------------------------------------------------------------------------------------------------
	

	_add : function(oParams)
	{
		var oRef = typeof (oParams.ref) == 'object' ? oParams.ref : document.getElementById(oParams.ref);
		var oParent = oRef && typeof (oRef) == 'object' ? oRef.parentNode : null;
		var sParentId = oParent && typeof (oParent) == 'object' ?  oParent.id : null;

		var oNewB = null;
		
		if(oParams.to == 'bottom' || oParams.to == 'top' || !oParams.to)
		{
			if(oParams.ref_parent) oParent = oParams.ref_parent;
			if(!oParent || typeof (oParent) != 'object') return false;
			var oNewB = oParent.insertBefore(this._createNode('b', oParams.id), oParams.to == 'bottom' ? oRef.nextSibling : oRef);
			this._createNodeBExtras(oNewB);
		}
		else if(oParams.to == 'right' || oParams.to == 'left')
		{
			if(this._childrenCount(oParent) > 1)
			{
				var oNewH = oParent.insertBefore(this._createNode("h", this._generateId('h')), oRef);
				oNewH.appendChild(oRef);
				oParent = oRef.parentNode;
				sParentId = oParent.id;
			}
			if(this._nodeType(oParent) == 'v')
			{
				var oNewV = oParent.parentNode.insertBefore(this._createNode("v", this._generateId('v'), 'new'), oParams.to == 'right' ? oParent.nextSibling : oParent);
				var oNewB = oNewV.appendChild(this._createNode("b", oParams.id));
				this._createNodeBExtras(oNewB);
			}
			else if(this._nodeType(oParent) == 'h')
			{
				var oNewV = oParent.insertBefore(this._createNode("v", this._generateId('v'), 'new'), oParams.to == 'right' ? oRef.nextSibling : oRef);
				oNewV.appendChild(oRef);

				oNewV = oNewV.parentNode.insertBefore(this._createNode("v", this._generateId('v'), 'new'), oParams.to == 'right' ? oNewV.nextSibling : oNewV);
				var oNewB = oNewV.appendChild(this._createNode("b", oParams.id));
				this._createNodeBExtras(oNewB);
			}
		}
		else if(oParams.to == 'v' || oParams.to == 'v_first' || oParams.to == 'v_last')
		{
			if($(oRef).hasClass('block') && (oParams.to == 'v_first' || oParams.to == 'v_last'))
			{
				if(oParams.ref_parent) oParent = oParams.ref_parent;
				var oNewV = this._createNode("v", this._generateId('v'), 'new');
				for(i in oParent.childNodes) if(oParent.childNodes[i].nodeType == 1) oNewV.appendChild(oParent.childNodes[i]);
				oParent.insertBefore(oNewV, null);
				oRef = oNewV;
			}			
			switch(oParams.to)
			{
				case 'v_first': var oBefore = oRef; break;
				case 'v_last': var oBefore = null; break;
				case 'v': var oBefore = oRef.nextSibling; break;
				default: return {};
			}

			var oNewV = oParent.insertBefore(this._createNode("v", this._generateId('v'), 'new'), oBefore);
			var oNewB = oNewV.appendChild(this._createNode("b", oParams.id));
			this._createNodeBExtras(oNewB);			
		}		
				
		return {parent: oParent, ref: oRef, b:oNewB};
	}, //_add
	//--------------------------------------------------------------------------------------------------------



	_fixWidth : function(oParams)
	{
		if(!oParams) oParams = {}
		var oNode = oParams.context_node ? oParams.context_node : this.oSettings.root;
		
		var iColumnWidth = null;
		
		var sNodeType = this._nodeType(oNode);
		if(sNodeType && (oParams.new_children || oNode.updateWidth))
		{
			if(oNode.updateWidth) oNode.updateWidth = false;
			
			if(!oParams.child_num) oParams.child_num = 0;

			var iChildrenCount = oParams.children_count ? oParams.children_count : 0;
			var iWidthTotal = 100-(iChildrenCount-1);
			iColumnWidth = iChildrenCount>0 && sNodeType == 'v' ? Math.round((iWidthTotal / iChildrenCount) - 0) : 100;

			if(iChildrenCount == oParams.child_num) if(oParams.summed_width + iColumnWidth < iWidthTotal || oParams.summed_width + iColumnWidth > iWidthTotal) iColumnWidth = iWidthTotal - oParams.summed_width;

			//oNode.style.backgroundColor = '#'+Math.floor(Math.random()*16777215).toString(16);
			
			if(sNodeType == 'v' && oParams.child_num > 1 && this._nodeType(this._previousSibling(oNode), this.STRUCTURE_VERIFY_EXTENDED) == 'v')
			{
				var o = oNode.parentNode.insertBefore(this._createNode('vs'), oNode);
				o.style.height = "100px";
				this._addVertSeparatorEvents(o);
			}

			oNode.style.width = iColumnWidth + '%';
			oNode.className = oNode.className.replace(/\s?new/,'');
		}
		
		// check if new children were added
		var bNewChildren = false;
		for(var i in oNode.childNodes)
		{
			if(!this._nodeType(oNode.childNodes[i])) continue;
			if(this._hasClass(oNode.childNodes[i], 'new'))
			{
				bNewChildren = true;
				break;
			}
		}
		
		// parse children
		var iChildNum = 0;
		var iSummedColumnWidth = 0;

		var arrChildNodes = [];
		for(i in oNode.childNodes) if(this._nodeType(oNode.childNodes[i])) arrChildNodes[arrChildNodes.length] = oNode.childNodes[i];
		for(i in arrChildNodes)
		{
			iChildNum++;
			iSummedColumnWidth += this._fixWidth({context_node: arrChildNodes[i], children_count: arrChildNodes.length, child_num:iChildNum, new_children:bNewChildren, summed_width: iSummedColumnWidth});
		}
		
		return iColumnWidth;
	}, //_fixWidth
	//--------------------------------------------------------------------------------------------------------
	
	_fixVertSeparator : function(oParams)
	{
		if(!oParams) oParams = {}
		var oNode = oParams.context_node ? oParams.context_node : this.oSettings.root;
				
		var sNodeType = this._nodeType(oNode, this.STRUCTURE_VERIFY_EXTENDED);
		if(sNodeType == 'vs')
		{
			var iHeight = 0;
			var oPrev = $(oNode).prev();
			var oNext = $(oNode).next();
			if($(oPrev).prop('className') == 'structure_column') iHeight = Math.max(iHeight, $(oPrev).height());
			if($(oNext).prop('className') == 'structure_column') iHeight = Math.max(iHeight, $(oNext).height());
			if(iHeight>0 && !isNaN(iHeight)) $(oNode).height(iHeight+'px');
			if($(oNode).is('.ui-draggable'))
			{
				if(oPrev[0] && oNext[0]) $(oNode).draggable( "option", "containment", [oPrev.position().left + 100, 0, oNext.position().left + oNext.width() - 100, 0] );
			}else{
			 	this._addVertSeparatorEvents(oNode);
			}
		}
		// fix children
		if(oNode && typeof(oNode.childNodes) != 'undefined') for(var i=0; i<oNode.childNodes.length; i++) if(this._nodeType(oNode.childNodes[i],this.STRUCTURE_VERIFY_EXTENDED)) this._fixVertSeparator({context_node: oNode.childNodes[i]});
		
		return true;
	}, //_fixVertSeparator
	//--------------------------------------------------------------------------------------------------------
	
	_arrBlocksToFix : [],
	_fixHeight : function(oParams)
	{
		return;
		if(!oParams) oParams = {}
		if(!oParams.level)
		{
			oParams.level = 0;
			this._arrBlocksToFix = [];
		}

		this._fixHeightPrepare();
		this._arrBlocksToFix.sort(function(a,b){
			var a = a.height;
			var b = b.height; 
			return (a < b) ? 1 : (a > b ? -1 : 0);
		});

		for(var i in this._arrBlocksToFix)
		{
			var oItem = this._arrBlocksToFix[i];
			var oBlock = $('.block', $(oItem.o))[0];
			var iHeight = $(oBlock).height();
			var oPrev = $(oItem.o).prev();
			var oNext = $(oItem.o).next();
			if($(oPrev).hasClass('structure_vert_separator')) iHeight = Math.max(parseInt(iHeight), parseInt($(oPrev).height()));
			if($(oNext).hasClass('structure_vert_separator')) iHeight = Math.max(parseInt(iHeight), parseInt($(oNext).height()));			
			$(oBlock).height((iHeight-10)+'px');
			if($(oPrev).hasClass('structure_vert_separator')) $(oPrev).height(iHeight+'px');
			if($(oNext).hasClass('structure_vert_separator')) $(oNext).height(iHeight+'px');
		}

		this._fixVertSeparator();
		return true;
	}, //_fixHeight
	//--------------------------------------------------------------------------------------------------------
	_fixHeightPrepare : function(oParams)
	{
		if(!oParams) oParams = {}
		if(!oParams.level) oParams.level = 0;
		var oNode = oParams.context_node ? oParams.context_node : this.oSettings.root;

		for(var i in oNode.childNodes)
		{
			var oChild = oNode.childNodes[i];
			if(oChild.nodeType == 1) this._fixHeightPrepare({context_node:oChild, level:oParams.level+1});
			if(this._nodeType(oChild) != 'v') continue;
			var x = this._childrenCount(oChild, 'b', this.STRUCTURE_RECURSIVE);

			if(x == 1)
			{
				var oBlock = $('.block', $(oChild))[0];
				var iHeight = $(oBlock).height();
				var oPrev = $(oChild).prev();
				var oNext = $(oChild).next();
				if($(oPrev).hasClass('structure_vert_separator')) iHeight = Math.max(parseInt(iHeight), parseInt($(oPrev).height()));
				if($(oNext).hasClass('structure_vert_separator')) iHeight = Math.max(parseInt(iHeight), parseInt($(oNext).height()));
				this._arrBlocksToFix[this._arrBlocksToFix.length] = {o:oChild, height:iHeight}
			}
		}
		return true;
	}, //_fixHeightPrepare
	//--------------------------------------------------------------------------------------------------------
	
	_createNode : function (sNodeName, sNodeId, sNodeMiscClass)
	{
		if(!sNodeMiscClass) sNodeMiscClass = ''; else sNodeMiscClass = ' ' + sNodeMiscClass;
		if(sNodeName == '') return null;
		var o = document.createElement('div');
		switch(sNodeName)
		{
			default:
			case 'b':
				o.className = 'block'+sNodeMiscClass;
				break;
			case 'v':
				o.className = 'structure_column'+sNodeMiscClass;
				break;
			case 'h':
				o.className = 'structure_container'+sNodeMiscClass;
				break
			case 'vs':
				o.className = 'structure_vert_separator'+sNodeMiscClass;
				break				
		}
		if(sNodeId != '' && sNodeId) o.id = sNodeId;
		return o;
	}, //_createNode
	//--------------------------------------------------------------------------------------------------------


	
	_createNodeBExtras : function(oNode)
	{
		if(!oNode || !(sNodeType = this._nodeType(oNode)) || sNodeType != 'b') return false;
		
		oNode.className += " block_empty block_settings";
		
		var o = document.createElement('div');
		o.className = "btn_gear";
		o.innerHTML = oLang.LABEL_ADD_SOURCES.toLowerCase();
		oNode.appendChild(o);

		$(oNode).on('click', function(e){
			CHuborama.openSettings(e.target);
		});
		
		CHuborama.assignBlockExtras(oNode);

		return true;
	}, //_createNodeBExtras
	//--------------------------------------------------------------------------------------------------------

	_nodeType : function(o, iOptions)
	{
		if(!iOptions) iOptions = 0;

		if(typeof(o) != 'object' || !o) return null;
		if(!o.className) return null;

		if(o.className.match(/\bblock\b/)) return 'b';
		if(o.className.match(/\bstructure_column\b/)) return 'v';
		if(o.className.match(/\bstructure_container\b/)) return 'h';
		if(iOptions & this.STRUCTURE_VERIFY_EXTENDED) if(o.className.match(/\bstructure_vert_separator\b/)) return 'vs';
		return null;
	}, //_nodeType
	//--------------------------------------------------------------------------------------------------------

	_previousSibling : function(oNode)
	{
		oNode = oNode.previousSibling;
		while( !this._nodeType(oNode, this.STRUCTURE_VERIFY_EXTENDED) && oNode) { oNode = oNode.previousSibling; }
		return oNode;
	}, //_previousSibling
	//--------------------------------------------------------------------------------------------------------
	
	_nextSibling : function(oNode)
	{
		oNode = oNode.nextSibling;
		while( !this._nodeType(oNode, this.STRUCTURE_VERIFY_EXTENDED) && oNode) { oNode = oNode.nextSibling; }
		return oNode;		
	}, //_nextSibling
	//--------------------------------------------------------------------------------------------------------

	_firstChild : function(oNode)
	{
		oNode = oNode.firstChild;
		while(!this._nodeType(oNode, this.STRUCTURE_VERIFY_EXTENDED) && oNode) { oNode = oNode.nextSibling; }
		return oNode;
	}, //_firstChild
	//--------------------------------------------------------------------------------------------------------
	
	_isOfType : function(s, arr)
	{
		if(!s || !arr) return false;
		if(typeof(s) == 'object') s = this._nodeType(s);
		if(!s) return false;
		return arr.indexOf(s) >= 0 ? true : false;
	}, //_isOfType
	//--------------------------------------------------------------------------------------------------------
	
	_childrenCount : function(oNode, oNodeTypeToCount, iOptions)
	{
		if(!iOptions) iOptions = 0;
		if(!oNodeTypeToCount) oNodeTypeToCount = null;
		var iChildNum = 0;
		for(var i in oNode.childNodes)
		{
			if((oNodeTypeToCount ? this._nodeType(oNode.childNodes[i]) == oNodeTypeToCount : this._nodeType(oNode.childNodes[i]))) iChildNum++;
			if(iOptions & this.STRUCTURE_RECURSIVE && oNode.childNodes[i].nodeType == 1) iChildNum += this._childrenCount(oNode.childNodes[i], oNodeTypeToCount, iOptions);
		}
		
		return iChildNum;
	}, //_childrenCount
	//--------------------------------------------------------------------------------------------------------

	
	
	_generateId : function(sPrefix)
	{
		while(document.getElementById(sNextId = sPrefix + this.iNextStructureId++));
		return sNextId;
	}, //_generateId
	//--------------------------------------------------------------------------------------------------------
	
	_hasClass : function(o, sClassName)
	{
		if(typeof(o) != 'object') return false;
		return o.className.match(new RegExp(''+sClassName));
	},
	//--------------------------------------------------------------------------------------------------------
	
	
	_addVertSeparatorEvents : function(oNode)
	{
		if(!CHuborama.bIsOwner) return false;
		// set height
		var iHeight = 0;
		var oPrev = $(oNode).prev();
		var oNext = $(oNode).next();
		if($(oPrev).prop('className') == 'structure_column') iHeight = Math.max(iHeight, $(oPrev).height());
		if($(oNext).prop('className') == 'structure_column') iHeight = Math.max(iHeight, $(oNext).height());
		if(iHeight>0 && !isNaN(iHeight)) $(oNode).height(iHeight+'px');


		$(oNode).on('mouseenter', function(){
			if(!$('#vs_add_column')[0])
			{
				var o = document.createElement('div');
				o.className = "vs_add_column";
				o.id = "vs_add_column";
				o.title = oLang.LABEL_INSERT_COLUMN;
				document.body.appendChild(o);
			}
			
			$('#vs_add_column').appendTo(oNode.parentNode);
			$("#vs_add_column").data({parent:oNode});
			$('#vs_add_column').css({top: $(oNode).position().top +"px", left: $(oNode).position().left - $('#vs_add_column').width()/2 + $(oNode).width()/2 +"px"});
			$('#vs_add_column').show();

			$('#vs_add_column').unbind('click');
			$('#vs_add_column').on('click', function(){
				$('#vs_add_column').hide();
				CStructure.add({ref:$("#vs_add_column").data().parent, id:CStructure._generateId('new_'), to:'v'});
			});				
			$('#vs_add_column').on('mouseleave', function(e){
				if(!DMS.isMouseOver(e, $("#vs_add_column").data().parent))
				{
					if(!CStructure.bIsDragged) $('#vs_add_column').hide();
				}
			});				
		});
		$(oNode).on('mouseleave', function(e){
			if(!DMS.isMouseOver(e,$('#vs_add_column')))
			{
				if(!CStructure.bIsDragged) $('#vs_add_column').hide();
			}
		});
		
		$(oNode).draggable({
			axis: "x",
			containment: [	$(oNode).prev().position().left + 100, 0, $(oNode).next().position().left + $(oNode).next().width() - 100, 0],
			cursor: "w-resize",
			helper: "clone",
			stack: "#structure",
	
			start: function(e, ui){
				CStructure.bIsDragged = true;
				var element = e.target;
				this.prop = {
								helper: ui.helper,
								prev: $(element).prev(),
								next: $(element).next(),
								posStart: $(element).position(),
								posEnd: {},
								posDistance: {},
								posLast: $(element).position(),
								sizeStart: {
											prev: {width: $(element).prev().width()},
											next: {width: $(element).next().width()}
								},
				}
				$(ui.helper).css({visibility:'hidden'});
			},		
	
			drag: function(e, ui){
				this.prop.helper.offset({top: $(this).position().top});
				
				this.prop.posEnd = $(this.prop.helper).position();
				this.prop.posDistance = {left: this.prop.posEnd.left - this.prop.posStart.left};

				var iPrevWidth = this.prop.sizeStart.prev.width + this.prop.posDistance.left;
				var iNextWidth = this.prop.sizeStart.next.width - this.prop.posDistance.left;
	
				var widthPerc = this.prop.prev.prop('style').width.replace(/\%/,'');
				var widthPercPrev = widthPerc;
				var widthPix = this.prop.prev.width();
				var widthPixPrev = widthPix;
				var widthPrevPercNew = Math.round((iPrevWidth * widthPerc / widthPix));
	
				var widthPerc = this.prop.next.prop('style').width.replace(/\%/,'');
				var widthPix = this.prop.next.width();
				var iOverWidth = (widthPix + widthPixPrev) - (this.prop.sizeStart.prev.width+this.prop.sizeStart.next.width);
				if(iOverWidth>0) widthPix -= iOverWidth;
				var widthNextPercNew = parseFloat(widthPercPrev)+parseFloat(widthPerc) - widthPrevPercNew;
		
				this.prop.prev.width(widthPrevPercNew+'%');
				this.prop.next.width(widthNextPercNew+'%');
				
				var element = e.target;
				$('#vs_add_column').css({top: $(element).position().top +"px", left: $(element).position().left - $('#vs_add_column').width()/2 + $(element).width()/2 +"px"});
				
			},
			stop: function(e, ui){
				$(this).parent().children(".structure_vert_separator").each(function(i,element){
					if(! $(element)[0].className.match(/\bui-draggable-dragging\b/)){
						if($(element).prev()[0] && $(element).next()[0]) $(element).draggable( "option", "containment", [$(element).prev().position().left + 100, 0, $(element).next().position().left + $(element).next().width() - 100, 0] );
					}
				}); //each
				CStructure.bIsDragged = false;
				CHuborama.adaptItemsCount();
				CHuborama.onResize();
				$('#vs_add_column').hide();
			
				CHuborama.showTopInfoProgress();
				CHuborama.save();				
			}
		});
	} //_addVertSeparatorEvents
	//--------------------------------------------------------------------------------------------------------
}// CStructure
