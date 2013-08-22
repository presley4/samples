if(typeof(Number.prototype.toRad) === "undefined") Number.prototype.toRad = function() { return this * Math.PI / 180; }
if(typeof(Number.prototype.toDeg) === "undefined") Number.prototype.toDeg = function() { return this * 180 / Math.PI; }
if(typeof(String.prototype.getPregMatch) === "undefined") String.prototype.getPregMatch = function(sPattern) {
	if(!sPattern) sPattern = /(.*)/;
	var arrMatches = sPattern.exec(this);
	if(arrMatches)
	{
		if(arrMatches.length == 2)
		{
			return arrMatches[1];
		}else if(arrMatches.length>2){
			return arrMatches;
		}
		return false;
	}
}
if(typeof(String.prototype.str_repeat) === "undefined") String.prototype.str_repeat = function(iNum) {var t = ""; for (var i=0; i<iNum; i++) t += this; return t;}
if(typeof String.prototype.trim !== 'function'){
	String.prototype.trim = function() {
		return this.replace(/^[\s\xA0\n\t\r]+|[\s\xA0\n\t\r]+$/g, ''); 
	}
}

/**
* Cookie
*
* @version		1.1
* @since		2011-05-21
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
*/
var Cookie = {

	set: function(cookiename, cookievalue){
		var argv = this.set.arguments;
		var argc = this.set.arguments.length;
		var expires = (argc > 2) ? argv[2] : null;
		var path = (argc > 3) ? argv[3] : null;
		var domain = (argc > 4) ? argv[4] : null;
		var secure = (argc > 5) ? argv[5] : false;
	
		document.cookie = cookiename + "=" + escape(cookievalue) +
		((expires == null) ? "" : ("; expires=" + expires.toGMTString())) +
		((path    == null) ? "" : ("; path="    + path)) +
		((domain  == null) ? "" : ("; domain="  + domain)) +
		((secure  == true) ? "; secure" : "");
		
		return;
	},

	get: function(name){
		var arg = name + "=";
		var alen = arg.length;
		var clen = document.cookie.length;
		var i = 0;
		
		while (i < clen)
		{
			var j = i + alen;
			if (document.cookie.substring(i, j) == arg) return this.getVal(j);
			i = document.cookie.indexOf(" ", i) + 1;
			if (i == 0) break; 
		}
		return null;
	},

	getVal: function(offset){	
		var endstr = document.cookie.indexOf(";", offset);
		if (("" + endstr) == "" || endstr == -1) endstr = document.cookie.length;
		return unescape(document.cookie.substring(offset, endstr));
	},

	'delete' : function(cookiename){		
		var exp = new Date();
		exp.setTime(exp.getTime() - 1);
		var cookieVal = this.get(cookiename);
		if (cookieVal != null) document.cookie = name + "=" + cookieVal + "; expires=" + exp.toGMTString();
		return;
	}
}//Cookie

/**
* SlideShow
*
* @version		1.0
* @since		2012-02-11
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
*/
var SlideShow = {

	oTimerSlideShow: null,
	oSettings: {'cookie_name':'slideShowInterval', 'slide_show_target':'slideShowTarget'},
	iCookieInterval: null,
	sLastNextUrl:'',
	execute: function(iInterval, sNextUrl){
		this.sLastNextUrl = sNextUrl;
		if(iInterval == null) iInterval = Cookie.get(this.oSettings.cookie_name);

		if(this.oTimerSlideShow) clearTimeout(this.oTimerSlideShow);
		iInterval = "" + iInterval;
		var timeout = 0;

		var arrUrl = window.location.href.split("#");
		var sAnchor = ''+arrUrl[1];

		if(sAnchor.match(/^swpause/))
		{
			var arr = sAnchor.split("=");
			var s = arr[1];
			if(!s) s = 60;
			s *= 1000;
			iInterval = "s";
		}
		else if(sAnchor.match(/^swstop/))
		{
			iInterval = '0';
		}

		var o = document.form_slideShowTimeOut.slideShowTimeOut;
		switch(iInterval)
		{
			case '0': timeout = 0; if(o) o[0].checked = true; break;
			case '1': timeout = 1000; if(o) o[1].checked = true; break;
			default:
			case '2': timeout = 7000; if(o) o[2].checked = true; break;
			case 's': timeout = s; iInterval = 2; break;
			
		}
		//set cookie (pass interval to the next url)
		var tm = new Date();
		tm.setHours(tm.getHours()+3);
		Cookie.set(this.oSettings.cookie_name, ''+iInterval, tm);	
		//execute
		var imgSlideShowTarget = document.getElementById(this.oSettings.slide_show_target);
		if(imgSlideShowTarget && imgSlideShowTarget.complete)
		{
			//image loaded
			if(timeout && sNextUrl != '' && sNextUrl != 'undefined') this.oTimerSlideShow = setTimeout("window.location = '" + sNextUrl + "'", timeout);
		}else{
			//image not loaded
			if(timeout) this.oTimerSlideShow = setTimeout( "SlideShow.execute(" + iInterval + ",'" + sNextUrl + "');", 1000);
		}
		return true;
	}
}//SlideShow

/**
* RTR
* image rotator
*
* @version		1.0
* @since		2013-05-01
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
* @uses		jQuery
*
* RTR.create({
*	type:"bg", 
*	target:$('div.input_bgrepeat_container'), 
*	receiver:$('input#opt_bgrepeat'),
*	set:[
*			{x:"0",y:"-168px",v:0,label:oLang.DESC_SETTINGS_BGREPEAT_DISABLED},
*			{x:"-24px",y:"-168px",v:1,label:oLang.DESC_SETTINGS_BGREPEAT_VERT},
*			{x:"-48px",y:"-168px",v:2,label:oLang.DESC_SETTINGS_BGREPEAT_HORIZ},
*			{x:"-72px",y:"-168px",v:3,label:oLang.DESC_SETTINGS_BGREPEAT_ENABLED}
*		]
* });
*
*/
var RTR = {
	inst : [],

	create : function(oParams)
	{
		var iCurrentInst = this.inst.length;
		this.inst[iCurrentInst] = {}
		if(!oParams) oParams = {}
		this.inst[iCurrentInst].set = oParams.set;
		if(oParams.type) this.inst[iCurrentInst].type = oParams.type;
		this.inst[iCurrentInst].target = oParams.target;
		this.inst[iCurrentInst].receiver = oParams.receiver;
		if(this.inst[iCurrentInst].receiver) this.inst[iCurrentInst].current = $(this.inst[iCurrentInst].receiver).val();
		this.inst[iCurrentInst].current = oParams.current ? oParams.current : (this.inst[iCurrentInst].current ? this.inst[iCurrentInst].current : 0);		
		$(this.inst[iCurrentInst].target)
			.off('click')
			.on('click', this.inst[iCurrentInst], function(e){
				var iCount = e.data.set.length;
				if(++e.data.current>=iCount) e.data.current = 0;
				RTR._set(iCurrentInst, e.data.current);
			});
		RTR._set(iCurrentInst, this.inst[iCurrentInst].current);
		return iCurrentInst;
	},// create
	
	_set : function(iCurrentInst, i)
	{
		switch(this.inst[iCurrentInst].type)
		{
			case "bg":
				var mPosX = this.inst[iCurrentInst].set[i].x;
				var mPosY = this.inst[iCurrentInst].set[i].y;
				$(this.inst[iCurrentInst].target).css("background-position", mPosX + " " + mPosY);
				$(this.inst[iCurrentInst].target).prop('title', this.inst[iCurrentInst].set[i].label);
				if(this.inst[iCurrentInst].receiver) $(this.inst[iCurrentInst].receiver).val(this.inst[iCurrentInst].set[i].v);
				break;
		}
	},// set
	
	destroy : function(i)
	{
	}// destroy
}//RTR

/**
* CustomRadio
* manages radio button set
*
* @version		1.1
* @since		2013-07-01
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
* @uses		jQuery
*
* CustomRadio.set({
*	elements:[
*				{element : $('#export_opml'), default:1, no_action_on_init:0},
*				{element : $('#export_text')}
*			],
*	U_style : '', //TODO
*	U_class : '',
*	U_action : function(element){},
*	D_style : '', //TODO
*	D_class : '',
*	D_action : function(e, element){}
*	type : 0 | 1-multi with limit 
* });
*/

var CustomRadio = {
	CR_USE_LIMITS : 1,
	inst : [],

	set : function(oParams)
	{
		if(!oParams) oParams = {}
		var iCurrentInst = this.inst.length;
		this.inst[iCurrentInst] = {}

		this.inst[iCurrentInst].options = 0;
		if(oParams.options) this.inst[iCurrentInst].options |= oParams.options;
		this.inst[iCurrentInst].selected_count = 0;
		this.inst[iCurrentInst].elements = oParams.elements;
		this.inst[iCurrentInst].U_class = oParams.U_class;
		this.inst[iCurrentInst].U_action = oParams.U_action;
		this.inst[iCurrentInst].D_class = oParams.D_class;
		this.inst[iCurrentInst].D_action = oParams.D_action;
		this.inst[iCurrentInst].limit_U = !oParams.limit_U ? (oParams.elements.length>0 ? oParams.elements.length : 0) : oParams.limit_U;
		this.inst[iCurrentInst].limit_D = !oParams.limit_D ? (oParams.elements.length>0 ? oParams.elements.length : 0) : oParams.limit_D;
		
		this.reset(iCurrentInst);
		for(i in this.inst[iCurrentInst].elements)
		{
			if(this.inst[iCurrentInst].elements[i].default) CustomRadio.select(iCurrentInst, i, null, (this.inst[iCurrentInst].elements[i].no_action_on_init ? 1 : 0));
			this.inst[iCurrentInst].elements[i].element
				.off('click.custom_radio')
				.on('click.custom_radio',  null, {current_instance_index:iCurrentInst, element_index:i}, function(e){
					var inst = CustomRadio.inst[e.data.current_instance_index];
					var selected = inst.elements[e.data.element_index].selected;

					if(inst.options & CustomRadio.CR_USE_LIMITS)
					{
						if(selected && inst.elements.length - inst.selected_count < inst.limit_U) CustomRadio.reset(e.data.current_instance_index, e.data.element_index);
						if(!selected && inst.selected_count < inst.limit_D) CustomRadio.select(e.data.current_instance_index, e.data.element_index, e);
					}else{
						if(!selected)
						{
							CustomRadio.reset(e.data.current_instance_index);
							CustomRadio.select(e.data.current_instance_index, e.data.element_index, e);
						}
					}
				});
		}
	},// set
	
	select : function(iInstance, iElementIndex, e, bNoAction)
	{
		if(!bNoAction) bNoAction = false;
		var inst = this.inst[iInstance];
		if(!bNoAction)
		{
			if(typeof(inst.D_action) == 'function') inst.D_action(e, inst.elements[iElementIndex].element);
			if(typeof(inst.elements[iElementIndex].D_action) == "function") inst.elements[iElementIndex].D_action(e, inst.elements[iElementIndex].element);
		}
		if(typeof(inst.D_class) == 'string') inst.elements[iElementIndex].element.removeClass(inst.D_class+' '+inst.U_class).addClass(inst.D_class);
		inst.elements[iElementIndex].selected = 1;
		inst.selected_count++;		
	},//select
	
	reset : function(iInstance, iElementIndex)
	{
		var arrElements = this.inst[iInstance].elements;
		if(typeof(iElementIndex) != 'undefined') arrElements = [arrElements[iElementIndex]];
		for(i in arrElements)
		{
			if(typeof(this.inst[iInstance].U_action) == 'function') this.inst[iInstance].U_action(arrElements[i].element);
			if(typeof(this.inst[iInstance].U_class) == 'string') arrElements[i].element.removeClass(this.inst[iInstance].D_class + ' ' + this.inst[iInstance].U_class).addClass(this.inst[iInstance].U_class);
			arrElements[i].selected = 0;
			this.inst[iInstance].selected_count--;
		}
		if(this.inst[iInstance].selected_count<0) this.inst[iInstance].selected_count = 0;
	},// reset
	
	destroy : function(i)
	{
	}// destroy
}// CustomRadio


/**
* CF
* confirm object
*
* @version		1.22
* @since		2012-12-20
* @package 		dms
* @author 		Piotr Kulikiewicz
* @copyright 	DMS
*
* @uses		jQuery
*
* CF.add({
*	starter: $('.confirm_delete', '#source_manager'),
*	accept: $('.confirm_yes', '#source_manager'),
*	cancel: $('.confirm_no', '#source_manager'),
*	onAccept: function(){},
*	onCancel: function(){},
* });
*/
var CF = {
	oSettings: {starter: '.confirm_delete', accept: 'confirm_yes', cancel:'confirm_no', accept_button: null, cancel_button: null},
	add: function(oSettings){
		for(prop in oSettings){this.oSettings[prop] = oSettings[prop]}		
		
		$(CF.oSettings.starter).each(function(i, element){
			$(element).on('click', function(e){
				$(this).css({visibility:'hidden', display:'none'});
				$(this).siblings().each(function(){ 
					$(this).css({visibility:'visible',display:'block'}); 
					
					if($(this).hasClass(CF.oSettings.accept)) 
					{
						CF.oSettings.accept_button = $(this);
						if(CF.oSettings.accept_button)
						{
							
							CF.oSettings.accept_button.on('click', function(e){
								if(CF.oSettings.onAccept)
								{
									var mAction = CF.oSettings.onAccept;
									if(typeof(mAction) === 'object')
									{
										if(mAction.location)
										{
											window.location = mAction.location;
										}//TODO for ajax with post and some others
									}
									else
									{
										if(typeof(mAction) === 'function') mAction();
									}
								}else{
									// default code here
									$(CF.oSettings.starter).siblings().css({visibility:'hidden',display:'none'});
									$(CF.oSettings.starter).css({visibility:'visible',display:'block'});									
								}
								$(CF.oSettings.accept_button).unbind('click');
								$(CF.oSettings.cancel_button).unbind('click');
							});
						}
					}//accept
					
					if($(this).hasClass(CF.oSettings.cancel))
					{
						CF.oSettings.cancel_button = $(this);
						if(CF.oSettings.cancel_button)
						{
							CF.oSettings.cancel_button.on('click', function(e){
								if(CF.oSettings.onCancel)
								{
									CF.oSettings.onCancel;
								}else{
									// default code here
									$(CF.oSettings.starter).siblings().css({visibility:'hidden',display:'none'});
									$(CF.oSettings.starter).css({visibility:'visible',display:'block'});
								}
								$(CF.oSettings.accept_button).unbind('click');
								$(CF.oSettings.cancel_button).unbind('click');
							});
						}
					} //cancel
					
				}); //$(this).siblings()
			}); //$(element).on
		}); //$(CF.oSettings.starter).each
	} //add
}// CF
