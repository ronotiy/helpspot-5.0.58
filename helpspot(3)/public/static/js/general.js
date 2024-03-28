var send = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.send = function(data) {
	this.setRequestHeader('X-CSRF-Token', window.HS.HS_CSRF_TOKEN);
	return send.apply(this, arguments);
};

// Disable caching of AJAX responses
$jq.ajaxSetup ({
    cache: false
});

//Pad function
$jq.strPad = function(i,l,s) {
	var o = i.toString();
	if (!s) { s = '0'; }
	while (o.length < l) {
		o = o + s;
	}
	return o;
};

//Numeric check
function IsNumeric(sText){

	var ValidChars = "0123456789.";
	var IsNumber=true;
	var Char;

	for (i = 0; i < sText.length && IsNumber == true; i++){
		Char = sText.charAt(i);
		if(ValidChars.indexOf(Char) == -1){
			IsNumber = false;
		}
	}

	return IsNumber;
}

//Display a notification
function hs_msg(msg,sticky,classname){
	sticky = sticky || false;
	classname = classname || false;

	if(classname) $jq("#hs_msg").addClass(classname);

	$jq("#hs_msg_inner").html(msg);

	//Show
	new Effect.Parallel([
		new Effect.Appear("hs_msg", { sync: true })
	], {
	  duration: 0.2
	});
	//Hide
	if(!sticky){
		setTimeout(function(){
			Effect.Fade('hs_msg', { duration: .2, onComplete: function(){
				//Reset class
				 if(classname) $jq("#hs_msg").removeClassName(classname);
			} });
		},3000);
	}
}

function updateCsrfTokens(token) {
    if(typeof window.HS != 'undefined') {
        window.HS.HS_CSRF_TOKEN = token;
    }

    if(typeof HS_CSRF_TOKEN != 'undefined') {
        HS_CSRF_TOKEN = token;
    }

    $jq('input[name="_token"]').val(token);
}

//Submit the login form
function login_form_sub(e){
    e.preventDefault();

    $jq.ajax({
        url: 'login',
        data: new FormData(e.currentTarget),
        processData: false,
        contentType: false,
        type: 'POST',
        headers: {
            accept: 'application/json'
        },
        success: function(data){
            Tips.hideAll();
            closeAllModals();
            did_re_login = true;

            updateCsrfTokens(data.csrf)
        }
    });

    return false;
}

//Is a var defined
function hs_isdefined(variable)
{
    return (typeof(window[variable]) == "undefined")?  false: true;
}

//Preload images
function preloadImages(){
    var cache = [];
    // Arguments are image paths relative to the current page.
      var args_len = arguments.length;
      for (var i = args_len; i--;) {
        var cacheImage = document.createElement('img');
        cacheImage.src = arguments[i];
        cache.push(cacheImage);
      }
  }

function is_wysiwyg(){
	return ( !(typeof(tinyMCE) == "undefined") && tinyMCE.activeEditor !== null );
}

function focus_note_body(field){
	if(! is_wysiwyg()){
		Field.focus(field);
	}
}

function get_note_body(field){
	if(is_wysiwyg()){
		// use jquery to see if it's empty. By default textbox has html tags that causes it not be blank.
		return ($jq("<div/>").html(tinyMCE.activeEditor.getContent()).text().trim() != "") ? tinyMCE.activeEditor.getContent() : "";
	}else{
		return ($F(field) ? $F(field) : "");
	}
}

function set_note_body(field,content){
	if(is_wysiwyg()){
		return tinyMCE.activeEditor.setContent(content)
	}else{
		return $(field).value = content;
	}
}

function append_wysiwyg_note_body(content)
{
	return tinyMCE.activeEditor.insertContent(content)
}

// send to page
function goPage(src){
	window.location.href = src;
}

//By using idle timers we prevent periodically executed events from barraging a server when the user is
//not actually using the system. This is especially helpful with users who use multiple browser tabs.
//Note, can't use window blur/focus due to wysiwyg & other irregularities
function hs_PeriodicalExecuter(name,user_function,timeout){
	//Global variable name
	var varname = 'helpspot_pe_' + name;

    // Run the function every 300 seconds
	window[varname] = setInterval(user_function, timeout * 1000);

	$jq(document).bind("idle.idleTimer", function(){
		clearInterval(window[varname]);
	});

	$jq(document).bind("active.idleTimer", function(){
		user_function(); //Cause actions to occur as soon as back active for responsiveness
		window[varname] = setInterval(user_function, timeout * 1000);
	});

	//If idle for 180 seconds then stop execution
	$jq.idleTimer(cHD_IDLE_TIMEOUT * 1000);
}

function closeAllModals(){
    if (modalIsOpen()) {
        mobiscroll.activeInstance.hide();
    }
}
function modalIsClosed() {
    return typeof(mobiscroll.activeInstance) === 'undefined';
}
function modalIsOpen() {
    return !modalIsClosed();
}
//Alert box
function hs_alert(txt,options){
	var options = Object.extend({
	  errorlist:	true,
	  title: 		lg_js_notification
	}, arguments[1] || { });
	var body;

	if(options.errorlist) {
		var list = txt.split(/(\r\n|[\r\n])/);
		body = '<ul class="alert-error-list">';
		for(i=0;i<list.length;i++){
			if(trim(list[i]) != "") body = body + '<li>'+list[i]+'</li>';
		}
		body = body + '</ul>';
	} else {
		body = txt.replace(/(\r\n|[\r\n])/g, "<br />");
	}
    var html = '<div class="alert-title">'+options.title+'</div><div class="alert-body">'+ body + '</div>';
    return hs_overlay({html: html});
}

// This is meant to be used has a onsubmit handler on forms
function hs_confirm_submit(ev, body, options) {
    ev.preventDefault()

    var options = Object.extend({
        title: 		lg_js_confirmation
    }, arguments[2] || { });

    closeAllModals();

    var popupHolder = $jq(".popup-holder");
    popupHolder.html('<div class="alert-title">'+options.title+'</div><div class="alert-body">'+ body + '</div>');

    var modal = popupHolder.mobiscroll().popup({
        display: "center",
        scrollLock: false,
        layout: "fixed",
        cssClass: "mbsc-no-padding md-content-scroll",
        onInit: function (event, inst) {
            $jq("body").addClass("mobi-open");
        },
        onBeforeShow: options.beforeOpen,
        onShow: options.onOpen,
        onClose: function (event, inst) {
            $jq("body").removeClass("mobi-open");
        },
        buttons: [{
            text: button_ok,
            handler: function(event, inst) {
                ev.target.submit()
            },
            icon: '',
            cssClass: 'mobi-close accent'
        }, {
            text: button_close,
            handler: 'cancel',
            icon: '',
            cssClass: 'mobi-close'
        }]
    }).mobiscroll("getInst");

    modal.show();

    return false;
}

//Confirm box
function hs_confirm(body,action,options){
    var options = Object.extend({
        title: lg_js_confirmation,
        showCancelButton: true
    }, options || {});

    closeAllModals();

    var popupHolder = $jq(".popup-holder");
    popupHolder.html('<div class="alert-title">'+options.title+'</div><div class="alert-body">'+ body + '</div>');

    var buttons = [{
        text: button_ok,
        handler: function(event, inst) {
            if($jq.type(action) === "function"){
                action();
                closeAllModals();
            }else if($jq.type(action) === "string"){
                goPage(action);
            }
        },
        icon: '',
        cssClass: 'mobi-close accent'
    }];

    if (options.showCancelButton) {
        buttons.push({
            text: button_cancel,
            handler: 'cancel',
            icon: '',
            cssClass: 'mobi-close'
        });
    }

    var modal = popupHolder.mobiscroll().popup({
        display: "center",
        scrollLock: false,
        layout: "fixed",
        cssClass: "mbsc-no-padding md-content-scroll",
        onInit: function (event, inst) {
            $jq("body").addClass("mobi-open");
        },
        onBeforeShow: options.beforeOpen,
        onShow: options.onOpen,
        onClose: function (event, inst) {
            $jq("body").removeClass("mobi-open");
        },
        buttons: buttons
    }).mobiscroll("getInst");

    modal.show();

	return false;
}

function kbui(bookid){
	var url = "admin?pg=ajax_gateway&action=kbui&xBook=" + bookid + "&rand=" + ajaxRandomString();

	$jq.get(url, function (data) {
		$jq("#kbui_box").html(data);
		hs_overlay('kbui_box', {
            display: "top",
			onOpen: function () {
				kbui_gettoc(bookid);
				$jq("#new_group").focus();
			}
		});
	});
}

function kbui_showpage(pageid){
	$jq("#kbui-page").html(ajaxLoading());
	$jq("#kbui-page").load("admin?pg=ajax_gateway&action=kbui-page&xPage="+pageid+"&xPortal="+$jq("#xPortal").val());
}

function kbui_gettoc(bookid){
	$jq("#kbui-toc").load("admin?pg=ajax_gateway&action=kbui-toc&xBook="+bookid, function(){
		folderUI("kbui-toc");
		$jq("#kbui-toc .kbui:first").click(); //load first page in this toc
	});
}

function aKBL(link,text){
	//If the tPost box exists in forums use that
	if($jq("#tPost").length){
		var elem = "tPost";
		var docpath = document.postform.tPost;
	}else{
		var elem = "tBody";
		var docpath = document.requestform.tBody;
	}

	if(elem == 'tBody' && editor_type == "wysiwyg" && is_wysiwyg("tBody")){
		// tinyMCE.execInstanceCommand(elem,"mceInsertContent",false,"<a href=\""+link+"\">"+text+"</a>");
		append_wysiwyg_note_body("<a href=\""+link+"\">"+text+"</a>");
	}else if(elem == 'tBody' && editor_type == "markdown"){
		insertAtCursor(docpath, "["+text+"]("+link+")");
	}else{
		insertAtCursor(docpath, link);
	}

	closeAllModals();

	return false;
}

function folderUI(wrap){
	//Init array of open folders
	var sidebarOpenFolders = (getCookie("sidebarOpenFolders") ? getCookie("sidebarOpenFolders").split(",") : []);

	//Click handler for folders
	$$("#" + wrap + " .folder").each(function(folder){
		$(folder).observe("click", function(event){
			Event.stop(event);
			$$("."+this.id).each(function(elem){
				elem.toggle();
			});
			$$("#"+this.id+" span.arrow").each(function(elem){
				elem.toggleClassName("arrow-open");
			});

			//Remember state
			var loc = indexInArray(sidebarOpenFolders, this.id);
			if(loc == -1){
				sidebarOpenFolders.push(this.id);
			}else{
				sidebarOpenFolders.splice(loc, 1);
			}

			//Save state cookie
			var expire_date = new Date;
			expire_date.setFullYear(expire_date.getFullYear() + 10);
			setCookie("sidebarOpenFolders", sidebarOpenFolders.join(","), expire_date);
		});
	});
}

function toggleSidebarState(){
    var state = getCookie("sidebarState");

    if(state == 'closed'){
        state = 'open';
        $jq('.main-layout').removeClass('sidebar-closed');
    }else{
        state = 'closed';
        $jq('.main-layout').addClass('sidebar-closed');
    }

    var expire_date = new Date;
    expire_date.setFullYear(expire_date.getFullYear() + 10);
    setCookie("sidebarState", state, expire_date);
}

//Get the url hash
function getHash(){
  var hash = window.location.hash;
  return hash.substring(1); //remove the #
}

//Open new page in original win from popup
function targetopener(mylink, closeme, closeonly){
	if (! (window.focus && window.opener))return true;
	window.opener.focus();
	if (! closeonly)window.opener.location.href=mylink.href;
	if (closeme)window.close();
	return false;
}

//Open a window
//ex: <a href="javascript:openWin(\'admin?pg=user.image\',\'ImagePick\',\'height=500, width=500, scrollbars=yes, toolbar=no, location=no, status=yes\');">
function openWin(loc,name,options){
	window.open(loc, "", options);
}

function showPopWin(page,winh,winw,name){
	openWin(page,name,'height='+winh+',width='+winw+',scrollbars=yes,toolbar=no,location=no,status=no,resizable=yes');
}

//close window with delay
function hsCloseWin(time){
	if(!time){
		time = 2;
	}

	setTimeout("window.close()",time*1000);
}

//sets times when using quick time select function
function qtSet(id,from,to){
	times = Form.Element.getValue(id);
	if(times != ""){
		v = Form.Element.getValue(id).split("|");
		var inst = $jq("#"+from).mobiscroll('getInst');
		var fromDate = calendar_clean_date(v[0]);
		inst.setVal(fromDate, true);
		var inst = $jq("#"+to).mobiscroll('getInst');
		var toDate = calendar_clean_date(v[2]);
		inst.setVal(toDate, true);
		if(v[4]){
			//Only override grouping if it's currently a date type (and not a date agg type)
			if($jq("#graph_grouping").val().search("date_") != -1 && $jq("#graph_grouping").val().search("date_agg") == -1) setSelectToValue("graph_grouping",v[4]);
		}
	}
}

function sidebarSearchAction(type){
	if(type == "request"){
		var q = trim($jq("#sidebar-q").val());
		//If just a number, use as reqid else search
		if(IsNumeric(q)){
			goPage("admin?pg=request&reqid=" + q);
		}else{
			// Load a spinner before actually performing the search.
			var spinner = '<table class="tablebody no_borders" id="rsgroup_1" width="750px" height="500px" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="js-request">'+ajaxLoading()+'</td></tr></tbody></table>';
			hs_overlay({html:spinner});
			if(q.search(/@/) > 0){
				var url = "admin?pg=ajax_gateway&action=sidebarsearch&search_type=2&q="+eq(q)+"&anyall=all&sEmail="+eq(q)+"&rand="+ajaxRandomString();
			}else{
				var url = "admin?pg=ajax_gateway&action=sidebarsearch&search_type=9&q="+eq(q)+"&anyall=any&rand="+ajaxRandomString();
			}
			// Find the results and replace out the spinner animation.
			$jq.get(url, function (data) {
				$jq(".popup-holder").html(data);
			});
		}
	}
}

//shortcut flag
var shortcutsON = true;

function hs_shortcutsOff(){
	shortcutsON = false;
}

function hs_shortcutsOn(){
	shortcutsON = true;
}

//Set Cookie
function setCookie(name,value,expires,path,domain,secure) {
    var cookieString = name + "=" +escape(value) +
       ( (expires) ? ";expires=" + expires.toUTCString() : "") +
       ( (path) ? ";path=" + path : "") +
       ( (domain) ? ";domain=" + domain : "") +
       ( (secure) ? ";secure" : "");
    document.cookie = cookieString;
}

//Get Cookie
function getCookie(cookie_name) {
		var str     = document.cookie;
        var cookies = str.split(';');
        for (var i = cookies.length; i--;)
        {
          var parts = cookies[i].split('=');
          if (parts[0].substring(0,1) == ' ')
              parts[0] = parts[0].substring(1,parts[0].length);

          if (unescape(parts[0]) == cookie_name){
             return unescape(parts[1]);
             }
        }
        return '';
}

//Trim extra whitespace
function trim(stringToTrim) {
	return $jq.trim(stringToTrim);
	//return stringToTrim.replace(/^\s+|\s+$/g,"");
}

//Set focus to field
function setFieldFocus(fld){
	if(fld){
		fld.focus();
	}
}

//Clear the filler text in a form element
function clearFocusFill(obj,text,newclass){
	if(obj.value == text) obj.value = "";
	if(newclass) Element.addClassName(obj.id,newclass);
}

//Checkbox row highlight
function checkRowHighlight(rid){
	if(document.getElementById(rid+"_box")){
		if(document.getElementById(rid+"_box").checked){
			document.getElementById(rid).className = "boldcheckboxrow";
		}else{
			document.getElementById(rid).className = "";
		}
	}
}

//Loop over checkbox sets and make lines bold. use with above.
function onloadRowHighlight(pre,len)
{
	for(var i=0; i<len; i++){
		checkRowHighlight(pre + i);
	}
}

// Check any rows that are already checked. See #364
function checkRows() {
  $jq.each($jq(".checkbox-menu").find("input:checked"), function() {
    rowChecked(this.value);
  });
}
//Highlight a table row
function rowChecked(reqid){
	$jq("#batch_action_buttons").show();

	row = $("tr-"+reqid);
	box = $(reqid + "_checkbox");

	if(box.checked){
		Element.addClassName(box,"checkedfilterrow");
		Element.addClassName(row,"checkedfilterrow");
	}else{
		Element.removeClassName(box,"checkedfilterrow");
		Element.removeClassName(row,"checkedfilterrow");
	}

	//Make sure any hidden buttons are shown
	$("batch_action_buttons").removeClassName("thin-disabled");
}

//Sort ordering
function reorder_call(id,type){
	var url = "admin";
	var pars = "pg=ajax_gateway&action="+type+"&" + Sortable.serialize(id,{name:"sortorder"}) + "&rand=" + ajaxRandomString();

	var call = new Ajax.Request(
		url,
		{
			method: 	 "get",
			parameters:  pars,
			onComplete:  function(){ $$("#tablesort .sortable").each(function(elem){ new Effect.Highlight(elem,{duration:1.0,startcolor:"#DCE8D8",keepBackgroundImage:true}); }); }
		});
}

//Given a value go through a form and find the ID
//Fields is an array for form elements (usually) from Form.getInputs("rsform_1", "checkbox");
function findIDbyValue(fields,value){
	for(i=0;i < fields.length;i++){
		if(fields[i].value == value) return fields[i].id;
	}
}

//Find the currently checked value in a radio button group
function getCheckedRadioButton(buttonGroup){
	for(var i = 0; i < buttonGroup.length; i++){
		if(buttonGroup[i].checked){
			return i;
		}
	}
}

//Find the currently selected option
function getSelectVal(elem){
	return $(elem).options[$(elem).selectedIndex].value;
}

// Return the index of a particular value
function hs_indexOf(thearray,item) {
    for (var i = 0; i < thearray.length; i++) {
        if (thearray[i] == item) {
            return i;
        }
    }
    return -1;
}

// Return true if the value is in an array
function hs_inArray (value)
// Returns true if the passed value is found in the
// array.  Returns false if it is not.
{
	var i;
	for (i=0; i < this.length; i++) {
		// Matches identical (===), not just similar (==).
		if (this[i] === value) {
			return true;
		}
	}
	return false;
};

//Find index in an array
function indexInArray(theArray, theValue){
	var arLength = theArray.length;

	for(var i=0; i < arLength; i++){
		if(theArray[i] == theValue){
			return i;
		}
	}

	return -1;
}

// Add ability to pad a string
function hs_pad(str, chr, num, prepend) {
  for (var i=str.length; i<num; i++) {
    str = ((prepend) ? (chr + str) : (str + chr));
  }
  return str;
}

// Use this function in textbox to prevent enter from submitting the form, optionally do a click event
function noenter(e, click_elem) {
	if (!e) var e = window.event
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;

	if(click_elem){
		if(code == 13){
			$(click_elem).onclick();
			return false;
		}else{
			return true;
		}
	}else{
		return !(code == 13);
	}
}

//Set a select list to a value if the value exists
function setSelectToValue(selectname,v,reset){
	list = $(selectname).options;

	if(typeof(reset) != 'undefined') list.selectedIndex = 0;

	if(list && v){
		for(i=0; i < list.length; i++){
			if(list[i].value == v){
				list.selectedIndex = i;
				break;
			}
		}
	}
}

//Set a select list to a value if the text exists
function setSelectByText(selectname,v){
	list = $(selectname).options;

	if(list && v){
		for(i=0; i < list.length; i++){
			if(list[i].text == v){
				list.selectedIndex = i;
				break;
			}
		}
	}
}

function help_toggle(page,from){
	var from = (from ? from : "");
	new Ajax.Updater('help_toggle_body', "admin?pg=ajax_gateway&action=help&page="+page+"&from="+from+"&rand=" + ajaxRandomString());
}

// CHECK RECORDSET ALL
function checkUncheckRsAll(id) {
	$jq("#batch_action_buttons").show();

	$$("input[name^=checktable]").each(function(elem){
		$(elem).checked = $(elem).checked ? false : true;
		$(elem).onclick();
	});

	return true;
}

// END CHECK ALL

//CHECK ONLY A GROUP OF REQUESTS
function checkUncheckRequestGroup(grouping){
	$jq("#batch_action_buttons").show();

	var boxes = $$('input.'+grouping);
	if($('groupcheckbox_'+grouping).checked){
		boxes.each(function(box){
			if(box.checked == false) box.click();
		});
	}else{
		boxes.each(function(box){
			if(box.checked == true) box.click();
		});
	}
}

//Create the stream view
function streamViewPrev(end){
	//Find the type we're dealing with (link- or takeit-)
	if($jq("[id^=link-]").length){
		var s = 'link-';
	}else{
		var s = 'takeit-';
	}

	//Find all links
	var prev = $jq("[id^="+s+"]").first().attr("id").replace(s,"");
	$jq("[id^="+s+"]").each(function(index){
		//At end
		if(prev == currentReqidPopup){
			hs_alert(end);
			return false;
		}

		//Loop over and find previous link
		if($jq(this).attr("id") == s+currentReqidPopup){
			showOverflow(prev.replace(s,""));
			return false;
		}
		prev = $jq(this).attr("id");
	});
}

function streamViewNext(end){
	//Find the type we're dealing with (link- or takeit-)
	if($jq("[id^=link-]").length){
		var s = 'link-';
	}else{
		var s = 'takeit-';
	}

	//Find all links
	var next = $jq("[id^="+s+"]").last().attr("id").replace(s,"");
	$jq($jq("[id^="+s+"]").get().reverse()).each(function(index){
		//At end
		if(next == currentReqidPopup){
			return hs_alert(end);
		}

		//Loop over and find next link
		if($jq(this).attr("id") == s+currentReqidPopup){
			return showOverflow(next.replace(s,""));
		}
		next = $jq(this).attr("id");
	});
}

//show the note menu
function showNoteItemMenu(){
	if($jq(".note-stream-item-menubtn").length){
		$jq(".note-stream-item-menubtn").each(function(index){
			new Tip(this.id, $jq("#"+this.id+"-content").html(), {
					title: "",
					border: 0,
					radius: 0,
					delay: 0,
					className: "hstinytip autoclose",
					stem: "topMiddle",
					showOn: "click",
					hideOn: false,
					hideAfter: 1,
					width: "auto",
					hook: { target: "bottomMiddle", tip: "topMiddle" },
					offset: { x: 0, y: 0 }
				});
		});
	}
}

function ms_select(id,value,fieldname){
	var field = id + "-hidden";

	//Set class
	$(id).toggleClassName('select-multiple-selected');

	//Insert hidden field
	if(!$(field)){
		$(id).insert({after:'<input type="hidden" id="'+field+'" name="'+fieldname+'[]" value="'+ value + '" />'});
	}else{
		$(field).remove();
	}
}

function ms_select_all(prefix){
	$$("."+prefix+"-select-multiple a").each(function(e){
		if(!$(e.id + "-hidden")){
			e.onclick();
		}
	});
}

function ms_expand(prefix){
	$$("."+prefix+"-select-multiple")[0].setStyle({height:"500px"});
}

function yes_no_btn(type,id,val){
	if(type == "yes"){
		$(id+"-yes").addClassName("btn-selected");
		$(id+"-yes").removeClassName("btn-yes-no");
		$(id+"-no").addClassName("btn-yes-no");
		$(id+"-no").removeClassName("btn-selected");
	}else{
		$(id+"-no").addClassName("btn-selected");
		$(id+"-no").removeClassName("btn-yes-no");
		$(id+"-yes").addClassName("btn-yes-no");
		$(id+"-yes").removeClassName("btn-selected");
	}

	$(id).setValue(val);
}

function addFolder(def,slist){
	var modal = initModal({
		footer: true,
	    closeMethods: ['overlay', 'button', 'escape'],
        href: "admin?pg=ajax_gateway&action=addfolder&default="+eq(def),
        buttons: [{
            text: button_save,
            handler: function(event, inst) {
                add_folder_action($jq('#new_folder').val(), slist);
            },
            icon: '',
            cssClass: 'mobi-close accent'
        }, {
            text: button_close,
            handler: 'cancel',
            icon: '',
            cssClass: 'mobi-close'
        }]
	});

	// $jq.get("admin?pg=ajax_gateway&action=addfolder&default="+eq(def), function(data){
	// 	modal.setContent(data);
	// });

	// modal.addFooterBtn(button_save, 'btn accent inline-action tingle-btn-right', function() {
	//     add_folder_action($jq('#new_folder').val(), slist);
	//     modal.close();
	// });

	// modal.open();
}

function add_folder_action(val,slist){
	foldername = val;
	folders   = $(slist).options;
	folderlen = folders.length;
	newoption = folderlen;

	$(slist).options[newoption]= new Option(foldername,foldername);
	$(slist).selectedIndex = newoption;

	closeAllModals();
}

function ttm_tip(id, txt){
	new Tip(id, txt, {
			title: "",
			className: "hstinytip autoclose",
			stem: "topMiddle",
			border: 0,
			radius: 0,
			showOn: "mouseover",
			hideOn: "mouseout",
			width: "auto",
			hook: { target: "bottomMiddle", tip: "topMiddle" }
		});
}

function ttm_tip_fat(id, txt){
	new Tip(id, txt, {
			title: "",
			className: "hstinytipfat autoclose",
			stem: "topMiddle",
			border: 0,
			radius: 0,
			showOn: "mouseover",
			hideOn: "mouseout",
			width: "160px",
			hook: { target: "bottomMiddle", tip: "topMiddle" }
		});
}

function initModal(setup){
	return hs_overlay(setup);
}

currentReqidPopup = 0;
function showOverflow(reqid){
	currentReqidPopup = reqid;
	var url = "admin?pg=request.static&from_streamview=1&reqid=" + reqid + "&rand=" + ajaxRandomString();
	var prevNext = '\
		<button class="btn inline-action" onclick="streamViewPrev(\''+ lg_streamview_end + '\')" style="margin-left:10px;">' + lg_prev + '</button> \
		<button class="btn inline-action" onclick="streamViewNext(\''+ lg_streamview_end + '\')">' + lg_next + '</button> \
	';
	var footer = '\
		<div style="display:flex;flex-grow:1;justify-content:space-between;">\
			<div style=""> \
				<input type="checkbox" value="1" class="form-checkbox js-select-request" style="width: 30px;height: 30px;" /> \
				<a href="/admin?pg=request&reqid='+ reqid + '" class="btn inline-action" style="margin-left:10px;font-wight:bold;">' + reqid + '</a> \
				'+ prevNext + ' \
			</div>\
			<div style=""> \
 				<a href="" class="btn accent inline-action tingle-btn-right" onclick="closeAllModals();return false;">'+ button_close + '</a> \
 			</div>\
		</div>\
	';
	$jq.get(url, function (data) {
		if (modalIsOpen()) {
			$jq(".popup-holder").html(data + " " + footer);
			$jq(".prevNext").html(prevNext);
		} else {
			closeAllModals();
			overflowOpen = true;
			modal = hs_overlay({
				html: data,
				footer: false,
				footerHtml: footer,
				stickyFooter: true,
				width: "900px",
				close: false,
				buttons: [],
				onOpen: function () {
					if ($jq("#" + parseInt(reqid) + "_checkbox").is(':checked')) {
						$jq(".js-select-request").prop("checked", true);
					} else {
						$jq(".js-select-request").prop("checked", false);
					}
					$jq(".js-select-request").on("click", function (e) {
						$jq("#" + parseInt(reqid) + "_checkbox").trigger("click");
					});
					$jq(".prevNext").html(prevNext);
				},
			});
		}
	});
}

function showHistoryEmailAndHeaders(reqhistid, field, title){
	if(field == "emailheaders"){
		var url = "admin?pg=ajax_gateway&action=emailheaders&reqhisid=" + reqhistid + "&rand=" + ajaxRandomString();
	}else{
		var url = "admin?pg=ajax_gateway&action=emailsource&reqhisid=" + reqhistid + "&rand=" + ajaxRandomString();
	}

	//Show content overlay
	hs_overlay({href:url,title:title});
}

function simplemenu_action(action, id, mode){
	mode = typeof(mode) != 'undefined' ? mode : "menu";

	//Hide tips & quick menu icon
	if(mode != "triage") document.fire("hs_overlay:closed");

	//Perform action
	if(action == 'unread'){

		$("replied_img_"+id).onclick();

		new Effect.Highlight("tr-"+id,{duration:2.0, startcolor:'#ffffff', endcolor:'#ffff99'});

	}else if(action == 'trash'){

		$("tr-"+id).addClassName("tablerow-trash");

		new Ajax.Request(
		'admin?pg=ajax_gateway&action=simplemenu_trash',
		{
			method: 	 "post",
			parameters:  {reqid:id},
			onComplete: function(r){
				if(r.responseText != ""){
					hs_alert(r.responseText);
				}else{
					new Effect.Highlight("tr-"+id,{duration:1.0, startcolor:'#FDDFD7', endcolor:'#D8B3AE', afterFinish:function(){$("tr-"+id).remove();}});

					//Move triage to next
					if(mode == "triage") triage_next();
				}
			}
		});

	}else if(action == 'spam'){

		$("tr-"+id).addClassName("tablerow-spam");

		new Ajax.Request(
		'admin?pg=ajax_gateway&action=simplemenu_spam',
		{
			method: 	 "post",
			parameters:  {reqid:id},
			onComplete: function(r){
				if(r.responseText != ""){
					hs_alert(r.responseText);
					$("tr-"+id).removeClassName("tablerow-spam");
				}else{
					new Effect.Highlight("tr-"+id,{duration:1.0, startcolor:'#FDDFD7', endcolor:'#D8B3AE', afterFinish:function(){$("tr-"+id).remove();}});

					//Move triage to next
					if(mode == "triage") triage_next();
				}
			}
		});

	}
}

person_status_update_details_flag=false;
function person_status_update_details(xperson,spage,ftype,sdetails){
	if(!person_status_update_details_flag){
		var pars = "xPersonStatus=" + eq(xperson) + "&sPage=" + eq(spage) + "&fType=" + eq(ftype) + "&sDetails=" + eq(sdetails) + "&rand=" + ajaxRandomString();

		var call = new Ajax.Request(
			'admin?pg=ajax_gateway&action=person_status_details',
			{
				method: 	 "post",
				parameters:  pars
			});

		person_status_update_details_flag = true;
	}
}

function showPersonStatusWorkspace(id,reqid){

	var call = new Ajax.Request(
		'admin?pg=ajax_gateway&action=person_status_workspace',
		{
			method: 	 "get",
			parameters:  {rand:ajaxRandomString(),reqid:reqid},
			onComplete:  function(){

							new Tip(id, arguments[0].responseText,{
									title: reqid,
									border: 0,
									radius: 0,
									className: "hstinytip autoclose",
									stem: "leftMiddle",
									showOn: "click",
                                    hideOn: { element: 'closeButton', event: 'click' },
									hideOthers: true,
									width: 250,
									hook: { target: "rightMiddle", tip: "leftMiddle" },
									offset: { x: -5, y: 0 }
								});

							$(id).prototip.show();
						 }
		});

}

//Overlay window
function hs_overlay(){
	//Accept 1 arg with just options array or 2 with a body element ID plus options
	if($jq.type(arguments[0]) == "string"){
		var popupHolder = $jq("#"+arguments[0]);
		var options = arguments[1];
	}else{
        var popupHolder = $jq(".popup-holder");
		var options = arguments[0];
	}

	var options = Object.extend({
		href: false,
        html: false,
		footer:	false,
        footerHtml:	'',
		stickyFooter: false,
		onOpen:	false,
		onClose: false,
		beforeOpen: false,
		beforeClose: false,
		cssClass: [],
        width: false,
        height: false,
        display: "center",
		close: button_close,
        cssClass: "mbsc-no-padding md-content-scroll",
		buttons: [{
			text: button_close,
			handler: 'cancel',
			icon: '',
			cssClass: 'mobi-close'
		}]
	}, options || { });

	if (options.width) {
        popupHolder.css("width", options.width);
    }

	scrollable = popupHolder.mobiscroll().popup({
		display: options.display,
		scrollLock: false,
		layout: "fixed",
		cssClass: options.cssClass,
		onInit: function (event, inst) {
			$jq("body").addClass("mobi-open");
		},
		onBeforeShow: options.beforeOpen,
		onShow: options.onOpen,
		onClose: function (event, inst) {
			$jq("body").removeClass("mobi-open");
		},
		buttons: options.buttons
	}).mobiscroll("getInst");

    if(options.href) {
        $jq.get(options.href, function (data) {
            popupHolder.html(data +" "+ options.footerHtml);
            scrollable.show();
        });
    } else if (options.html) {
        popupHolder.html(options.html +" "+ options.footerHtml);
        scrollable.show();
    } else {
        scrollable.show();
    }

	return false;
}

function insertTemplates(tpl){
	//text
	if($("ta_"+tpl)) $(tpl).value = $F("ta_"+tpl);

	//html
	if($("ta_"+tpl+"_html")) $(tpl+"_html").value = $F("ta_"+tpl+"_html");
	if($("ta_"+tpl+"_HTML")) $(tpl+"_HTML").value = $F("ta_"+tpl+"_HTML"); //AUTO RESPONSE TEMPLATE

	//subject
	if($("ta_"+tpl+"_subject")) $(tpl+"_subject").value = $F("ta_"+tpl+"_subject");

	//Show save message
	$(tpl+"_savemsg").show();

	closeAllModals();
}

function hs_hover(element,newclass){
	if(Element.hasClassName(element,newclass)){
		//Swap to old class
		Element.removeClassName(element,newclass);
	}else{
		//Swap to new class
		Element.addClassName(element,newclass);
	}
}

function getElementPosition(elemID) {
    var offsetTrail = document.getElementById(elemID);
    var offsetLeft = 0;
    var offsetTop = 0;
    while (offsetTrail) {
        offsetLeft += offsetTrail.offsetLeft;
        offsetTop += offsetTrail.offsetTop;
        offsetTrail = offsetTrail.offsetParent;
    }
    if (navigator.userAgent.indexOf("Mac") != -1 &&
        typeof document.body.leftMargin != "undefined") {
        offsetLeft += document.body.leftMargin;
        offsetTop += document.body.topMargin;
    }
    return {left:offsetLeft, top:offsetTop};
}

function innerWinSize() {
  var myWidth = 0, myHeight = 0;
  if( typeof( window.innerWidth ) == 'number' ) {
    //Non-IE
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if( document.documentElement &&
      ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  return {width:myWidth, height:myHeight};

}

// Do lookup for ajax custom fields
function custom_ajax_field_lookup(div,url,calling,loading){
	//Get request fields
	query = getRequestFields();

	//Adding calling field id
	query.set('callingField', calling);

    var pars = "&url=" + eq(url)+ "&" + query.toQueryString() + "&rand=" + ajaxRandomString();

	$(div).innerHTML = loading;
	$(div).show();

	var call = new Ajax.Request(
        'admin?pg=ajax_gateway&action=ajax_field_lookup',
		{
			method: 	 "post",
			parameters:  pars,
			onComplete:  function(){
							$(div).innerHTML = arguments[0].responseText;
						 }
		});
}

//Popup a LL result
function ll_popup(id){
	closeAllModals();
	hs_overlay('ll_popup_content_'+id, {buttons: []});
}

function ll_popup_move(num,end){
	if($jq('#ll_popup_'+num).length){
		$jq('#ll_popup_'+num).click();
	}else{
		hs_alert(end);
	}
}

// Get field values for all fields on the request page
function getRequestFields(){
	var req = $H();

	//Find any requests fields that may be available and send along, if no customer ID present we"re in an automation rule so send back empty
	if($("sUserId")){
		var req = $H({sUserId: eq($F("sUserId")),
				   sFirstName: eq($F("sFirstName")),
				   sLastName: eq($F("sLastName")),
				   sEmail: eq($F("sEmail")),
				   sPhone: eq($F("sPhone")),
				   xStatus: eq($F("xStatus")),
				   xCategory: eq($F("xCategory")),
				   xPersonAssignedTo: eq($F("xPersonAssignedTo"))});

		//Check for custom fields and send in available
		var forms = document.getElementsByTagName("form");
		for(i=0;i < forms.length;i++){
			var f = Form.getElements(document.forms[i]);
			for(e=0;e < f.length;e++){
				if(f[e].id.indexOf("Custom") !== -1 && f[e].id.indexOf("_") === -1){
					req.set(f[e].id, eq($F(f[e].id)) );
				}
			}
		}
	}

	return req;
}

// HELPSPOT SPECIAL EFFECTS
HS_Effects = {
	RsSetOrder: function(table,sortdiv){
		Element.hide(table);
		Element.show(sortdiv);
	},

	RsReturnSetOrder: function(url){
		goPage(url);
	}
};
// HELPSPOT SPECIAL EFFECTS

// AJAX HELPERS
function ajaxError(){

}

function ajaxLoadingImg(){
	return '<span class="spinner spin"></span>'
}

function ajaxLoading(){
	return '<div style="display: flex;justify-content: center;"><div class="inline_loading">'+ajaxLoadingImg()+'</div></div>';
}

//IE seems to cache ajax calls, this makes the URL unique
function ajaxRandomString(){
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var string_length = 8;
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
			var rnum = Math.floor(Math.random() * chars.length);
			randomstring += chars.substring(rnum,rnum+1);
	}

	return randomstring.toString();
}

//Add a selected item from a drop down list to a sortable div list
function addSortableColumn(select_list,sortable_vars,hidden_var,editable,colorselect){
	var listObj = $(select_list);

	if(listObj.options){
		if(listObj.selectedIndex != 0){
			var txt = listObj.options[listObj.selectedIndex].text;
			//Sometimes a width value is passed in as well
			if(listObj.options[listObj.selectedIndex].value.indexOf('@@@') !== -1){
				var item = listObj.options[listObj.selectedIndex].value.split('@@@');
				var val = item[0];
				var width = (item[1] ? item[1] : "");
				var hideflow = (item[2] == 'hideflow' ? 'overflowMsg' : 'setColumnWidth');
			}else{
				var val = $F(select_list);
			}
			var selected = listObj.selectedIndex;

			listObj.selectedIndex = 0;
			listObj.options[selected] = null;
		}
	}else{
		var txt = listObj.value;
		var val = eq(listObj.value); //URL encode for placement in hidden field
	}

	var colorid = Math.floor(Math.random() * 2000000000);

	var newDiv = Builder.node("div", {id: select_list + "_" + val, className: "sortable_filter", style: "display:none;"},
								[Builder.node("img", {src: static_path+"/img5/grip-lines-regular.svg", className: "drag_handle", style: "vertical-align: middle;cursor:move;width:16px;height:16px;margin-right:6px;"}),
								" ",
								(editable ? Builder.node("img", {src: static_path+"/img/space.gif", width:"16px", height:"16px", style: "vertical-align: middle;cursor:pointer;"}) : ""),
								" ",
								Builder.node("span", {id: select_list + "_" + val + "_text"}, txt),
								(typeof(width) !== "undefined" ? Builder.node("span", {id: "column_width_" + val, className: "hand filter_width_text", onclick: hideflow+"('column_width_"+val+"');"}, (width !== "" ? width : Builder.node("img", {src:static_path+"/img5/arrows-h-solid.svg"}))) : "" ),
								(typeof(width) !== "undefined" ? Builder.node("input", {type: "hidden", name: "column_width_" + val + "_value", id: "column_width_" + val + "_value", value: width}) : ""),
								Builder.node("input", {type: "hidden", name: hidden_var, value: val}),
								(typeof(colorselect) !== "undefined" ? Builder.node("input", {"class": "jscolor jscolor-small", "data-jscolor": "{required:false,hash:true}", name: "sListItemsColors[]", value: "", id:colorid}) : ""), //jscolor accepts values in the class attribute
								Builder.node("img", {src: static_path+"/img5/remove.svg", onClick: "return confirmRemove(\'"+select_list+"_"+val+"\', confirmListDelete);", style: "vertical-align: middle;cursor:pointer;width:16px;height:16px;"}),
								]
							);
	$(sortable_vars).appendChild(newDiv);
	Effect.Appear(newDiv.id);

	//Setup JSColor if needed
	if(typeof(colorselect) !== "undefined"){
		var picker = new jscolor(document.getElementById(colorid),{required:false,hash:true,value:null});
	}

	//Destroy original sortable and rebuild with new element
	Sortable.destroy(sortable_vars);
	Sortable.create(sortable_vars ,{tag:"div", constraint: "vertical"});
}

//Helper for column width JS
function insertColumnWidth(id){
	var value = trim($F(id + "_textbox"));

	$(id).update( (value == "" ? "<img src=\""+static_path+"/img5/arrows-h-solid.svg\" />" : value) );
	$(id + "_value").value = value;

	$(id).prototip.remove();
}

//Fix safari bug where hidden form fields are not sent in reordered order
//Only for use in onsubmit
function safari_order_fix(fieldname, form){
	//Safari only
	if(navigator.appVersion.match(/Konqueror|Safari|KHTML/)){

		//Prototype knows correct order of hidden fields so get and create new set of fields
		var inputs = Form.getInputs(form, "hidden", fieldname);
		for(i=0;i<inputs.length;i++){
			new_fields = Builder.node("input", {type: "hidden", name: fieldname, value: inputs[i].value});
			$(form).appendChild(new_fields);
		}

		//Find and remove all hidden form fields
		for(i=0;i<inputs.length;i++){
			Element.remove(inputs[i]);
		}
	}

	return false;
}

//Stop a form entry if a particular form elment is not empty
function stopFormEnter(field){
	if($F(field) == ''){
		return true;
	}else{
		document.getElementById(field).focus();
		return false;
	}
}

//Encode string
function eq(string){
	return encodeURIComponent(string);
}

function confirmRemove(id, text){
	return hs_confirm(text, function(){
		Element.remove(id);
	});
}

//Insert in text area at cursor position - Copyright Alex King - Licensed LGPL
function insertAtCursor(myField, myValue) {
//IE support
if (document.selection) {
myField.focus();
sel = document.selection.createRange();
sel.text = myValue;
}
//MOZILLA/NETSCAPE support
else if (myField.selectionStart || myField.selectionStart == '0') {
var startPos = myField.selectionStart;
var endPos = myField.selectionEnd;
myField.value = myField.value.substring(0, startPos)
+ myValue
+ myField.value.substring(endPos, myField.value.length);
} else {
myField.value += myValue;
}
}

//Auto grow text areas
function resize_all_textareas(){
	//If iPhone or iPod Touch then don't resize
	if(!RegExp("iPhone").test(navigator.userAgent) && !RegExp("iPod").test(navigator.userAgent)){
		var textareas=document.getElementsByTagName('textarea');
		for (var i=0;i<textareas.length;i++)
		{
			//Apply resizing to all areas except the tbody type
			if(textareas[i].id != 'tBody') new ResizeableTextarea(textareas[i]);
		}
	}
}

ResizeableTextarea = Class.create();
ResizeableTextarea.prototype = {
    initialize: function(element, options) {
        this.element = $(element);
        this.size = parseFloat(this.element.getStyle('height') || '100');
        this.options = Object.extend({
            inScreen: true,
            resizeStep: 10,
            minHeight: this.size
        }, options || {});
        Event.observe(this.element, "keyup", this.resize.bindAsEventListener(this));
        if ( !this.options.inScreen ) {
            this.element.style.overflow = 'hidden';
        }
        this.element.setAttribute("wrap","virtual");
        this.resize();
    },
    resize : function(){
        this.shrink();
        this.grow();
    },
    shrink : function(){
        if ( this.size <= this.options.minHeight ){
            return;
        }
        if ( this.element.scrollHeight <= this.element.clientHeight) {
            this.size -= this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.shrink();
        }
    },
    grow : function(){
        if ( this.element.scrollHeight > this.element.clientHeight ) {
            if ( this.options.inScreen && (20 + this.element.offsetTop + this.element.clientHeight) > document.body.clientHeight ) {
                return;
            }
            this.size += (this.element.scrollHeight - this.element.clientHeight) + this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.grow();
        }
    }
}

//Cleans the timestamp for date (not datetime) fields
function calendar_clean_date(timestamp){
	var odate = new Date(timestamp * 1000);
	return new Date(odate.getFullYear(),odate.getMonth(),odate.getDate(),12,0,0);
}

//Show/hide a date field
function showhide_datefield(rowid){
	if($F(rowid + "_2") == "is" || $F(rowid + "_2") == "less_than" || $F(rowid + "_2") == "greater_than"){
		if($(rowid + "_3")) {
		    $(rowid + "_3").show();
            $(rowid + "_3_show_calendar").show();
        }
	}else{
		if($(rowid + "_3")) {
		    $(rowid + "_3").hide();
		    $(rowid + "_3_show_calendar").hide();
        }
	}
}

function showhide_thermostatfield(rowid) {
	var $row2 = $jq('#' + rowid + "_2");
	var $row3_tf = $jq('#' + rowid + "_3_tf");
	var $row3_sf = $jq('#' + rowid + "_3_sf");

	// Sanity: We need a $row2
	if( $row2.length )
	{
		// If $row2 is a value that should have a textbox, hide the select, show the textbox
		if( $row2.val() == 'is' || $row2.val() == 'less_than' || $row2.val() == 'greater_than' )
		{
			if( $row3_sf.length )
			{
				$row3_sf.hide();
				$row3_sf.attr('name', rowid + '_3_off');
			}

			if( $row3_tf.length )
			{
				$row3_tf.attr('name', rowid + '_3');
				$row3_tf.show();
			}
		} else {
			// If $row2 is a value that should have a select, hide the textbox, show the select
			if( $row3_tf.length )
			{
				$row3_tf.hide();
				$row3_tf.attr('name', rowid + '_3_off');
			}

			if( $row3_sf.length )
			{
				$row3_sf.attr('name', rowid + '_3');
				$row3_sf.show();
			}
		}
	}
}

var calc_row;
function do_min_calc() {
	var min;
	min=$("calc_days").value*60*24;
	min=min+($("calc_hours").value*60);
	$(calc_row).value=min;
}

//Note options table - request and merge request
var note_option_string = '<div id="@tabid"><table cellpadding="0" cellspacing="0" border="0" class="noteoptiontable"><tr class="noteoptionrow"><td class="noteoptiontab" width="145">@tabtext</td><td class="noteoptiontabexp" align="right">@tabexp</td></tr></table><table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:0px;width: 100%;"><tr><td colspan="2" class="noteoptioninner" id="noteoptioninner_@tabid">@bodytext</td></tr></table></div>';

function validate_email(email) {
  // Regex from parsley.js
  var regExp = /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i;
  if ( ! email) {
    return false;
  } else {
    return regExp.test(email);
  }
}

function showViewers(){

    $jq.get('admin?pg=ajax_gateway&action=get_request_viewers', function(data){
        var viewers = [];

        // list of all dom ID's with viewers
        $jq.each(data, function(index, value){
            viewers.push('viewing-'+index);
        });

        $jq.each(data, function(index, value){
            $jq("#viewing-"+index)
                .addClass('viewing-'+value.fType)
                .attr('onclick',"showPersonStatusWorkspace('viewing-"+index+"',"+index+");");
        });

        // Remove classes from any that weren't in result set
        $jq(".viewing").each(function(){
            if($jq.inArray(this.id,viewers) == -1){
                $jq(this).removeClass('viewing-2 viewing-1').removeAttr('onclick');
            }
        });
    },"json");
}

$jq(document).ready(function(){
	var checked = ($jq("input.canCheck:checked").length == $jq("input.canCheck").length);
	$jq(".check-all").prop("checked", checked);
	$jq(".check-all").on("change", function(e){
		$jq(".canCheck").prop("checked", $jq(this).prop("checked"));
	});
	$jq(".canCheck").on("change", function(e) {
		var checked = ($jq(".canCheck:checked").length == $jq(".canCheck").length);
		$jq(".check-all").prop("checked", checked);
	});
	$jq(".js-check-all").on("click", function(e){
		var checked = ! ($jq(".canCheck:checked").length == $jq(".canCheck").length);
		$jq(".canCheck").prop("checked", checked);
		return false;
	});
	$jq('.color-label').contrastColor();

	// Setup notifications
});

// Request Pinning
$jq(document).on("click", "a.note-stream-item-pin", function(e){
	var id = $jq(this).data("id");
	$jq.ajax({
		method: "GET",
		url: "admin?pg=ajax_gateway&action=request_history_pin",
		data: { "xRequestHistory": id }
	})
		.done(function( msg ) {
			initRequestHistory();
		});
});

$jq.fn.contrastColor = function() {
	return this.each(function() {
		var bg = $jq(this).css('background-color');
		//use first opaque parent bg if element is transparent
		if(bg == 'transparent' || bg == 'rgba(0, 0, 0, 0)') {
			$jq(this).parents().each(function(){
				bg = $jq(this).css('background-color')
				if(bg != 'transparent' && bg != 'rgba(0, 0, 0, 0)') return false;
			});
			//exit if all parents are transparent
			if(bg == 'transparent' || bg == 'rgba(0, 0, 0, 0)') return false;
		}
		//get r,g,b and decide
		var rgb = bg.replace(/^(rgb|rgba)\(/,'').replace(/\)$/,'').replace(/\s/g,'').split(',');
		var yiq = ((rgb[0]*299)+(rgb[1]*587)+(rgb[2]*114))/1000;
		if(yiq >= 128) $jq(this).removeClass('light-color');
		else $jq(this).addClass('light-color');
	});
};

function addSlashes(str)
{
	return str.replace(/'/g, "\\'");
}
function removeSlashes(str)
{
	return str.replace(/\'/g, "'");
}

function dismissNotification(e) {
    e.preventDefault();
    var notification = e.currentTarget.dataset.notification;

    if(typeof window.HS.user.notifications[notification] != 'undefined' ) {
        delete window.HS.user.notifications[notification]
    }

    $jq('#notification-'+notification).remove();

    if( $jq.isEmptyObject(window.HS.user.notifications) ) {
        $jq('#hs_notification_window').remove()
    }

    $jq.post(window.chost+'/notifications/'+notification , {
        '_method': 'DELETE'
    });

    console.log($jq('#hs_notification_window').length);

    if($jq('#hs_notification_window').length == 0){
    	$jq('.hdsystembox').remove();
    }

    return false;
}

function dismissAllNotifications(e) {
    e.preventDefault();

    $jq.post('/notifications/all' , {
        '_method': 'DELETE'
    })

    $jq('.hdsystembox').remove();

    return false;
}
