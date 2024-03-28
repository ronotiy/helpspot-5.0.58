<?php
/*
It's recommended that if you make changes to this template or any other template that you first copy the template into

/custom_templates

and make modifications on the copied version. HelpSpot will automatically use the copied version rather than the original.
This will protect your changes from being overwritten during an upgrade.
*/

//Send the HTTP header indicating that this is a stylesheet
header('Content-type: text/css');
header('Content-Disposition: inline; filename="style.css"');
?>
/* Import styles for calendar used in date/datetime custom fields */
@import "<?php echo $this->cf_primaryurl ?>/static/js/datetimepicker/css/mobiscroll.jquery.min.css";
@import "<?php echo $this->cf_primaryurl ?>/static/js/popup/css/mobiscroll.jquery.min.css";

body
{
	margin: 0;
	padding: 0;
	text-align:center;
	background: #d8dddf url(<?php echo $this->cf_primaryurl ?>/portal/images_grey/bg.png) 0 0 repeat-x;
	color: #484848;
	font-family:  'Helvetica Neue', Helvetica, Arial, sans-serif;
}

a:link, a:active, a:visited { color: #105ea1; text-decoration: none;}

a:hover { color: #0679da; text-decoration: none;}

p {
	font: 13px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

h1
{
	color: #2E2E2E;
	margin: 10px 0 5px 0;
	font: bold 18px/22px 'Helvetica Neue',Helvetica,Arial,sans-serif;
	line-height: 28px;
}

h2
{
	color: #2E2E2E;
	font: bold 16px/18px 'Helvetica Neue',Helvetica,Arial,sans-serif;
	padding: 0;
	margin: 10px 0 0;
}

h3
{
	color: #494949;
	font: bold 14px/12px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
}

h4
{
	color: #666666;
	font: bold 12px/12px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 0;
	margin-top: 10px;
}

h5 { font: bold 12px/12px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	margin-bottom: -10px;
	}
/*
a:link
{
	color: #f57935;
	text-decoration: none;
}

a:visited
{
	color: #666;
	text-decoration: none;
}

a:hover
{
	color: #ff8000;
	text-decoration: none;
}
*/
img { border: 0; }

.subheading{
	margin-bottom:  15px;
	line-height:	120%;
}

/* DIVs */

#container
{
	width: 960px;
	padding: 0 0 0 0;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0px;
	background-color: transparent;
	text-align:left;
}

#banner
{
    background-color: transparent;
    color: #FFFFFF;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 24px;
    font-weight: normal;
    margin-bottom: 0;
    padding: 26px 2px 20px 8px;
    text-align: left;
    text-shadow: 0 1px 0 #292929;
	}


#leftSidebar
{
	float: left;
	width:  190px;
	\width: 190px;
	w\idth: 190px;
	margin: 40px 0 40px 0;
	padding: 20px 0;
	/* height: 100%; */
	display: inline;
	background: #2e2e2e;
	outline: 4px solid rgba(46, 46, 46, .2);
	}

#content2col
{
    background: none repeat scroll 0 0 #F7F8F8;
    font: 12px/18px 'Helvetica Neue',Helvetica,Arial,sans-serif;
    margin: 20px 20px 0 165px;
    overflow: auto;
    padding: 20px 40px;
    position: relative;
    z-index: 999;
    width: 660px;
    border-top: 15px solid #daddde;
    min-height: 600px;
	}

#content2col li {
	line-height: 20px;
}


#footer
{
    background: #2f3029 url(<?php echo $this->cf_primaryurl ?>/portal/images_grey/footer-bg.png) 0 0 repeat-x;
    clear: both;
    color: #999999;
    font: 12px/14px 'Helvetica Neue',Helvetica,Arial,sans-serif;
    margin: 40px 0 20px 0;
    padding: 10px;
    text-align: center;
    width: 903px;
    -webkit-border-radius: 4px; -khtml-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px;
    border: 4px solid #BCC0C2;
    min-height: 60px;
	}

#footer a{
	color: #fff;
	text-decoration: underline;
}

#footer strong {
    color: #FFFFFF;
    font-size: 11px;
    font-weight: normal;
    }

#footer input[type="text"] {
    color: #7D8390;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 16px;
    height: 15px;
    margin: 0 25px 0 15px;
    padding: 7px;
    width: 430px !important;
    float: left;
}

#footer select#area {
    float: left;
    margin-top: 8px;
    width: 250px !important;
    margin-right: 10px;
}

#footer input[type="submit"] {
   background: #484848;
   padding: 6px 30px;
   -webkit-border-radius: 3px;
   -moz-border-radius: 3px;
   border: none;
   border-radius: 3px;
   color: #ffffff;
   font-size: 18px;
   font-family: Georgia,Times New Roman,Times,Serif;
   text-decoration: none;
   vertical-align: middle;
   font-weight: normal;
   cursor: pointer;
   margin-left: 15px;
   font-style: italic;
   float: left;
   margin-top: 0px;
}

#footer input[type="submit"]:hover {
   background: #5c5c5c;
   }

#helpspot-link {
	font-size: 13px;
	margin-bottom: 40px;
	font-weight: normal;
	text-shadow: 0px 1px #fff;
	padding-bottom: 40px;
}

.hilite2col
	{
	background: #fffecc;
	border: 1px solid #cecece;
	width: 100%;
	/*
	display: block;
	float: left;
	clear: left;
	*/
	margin: 0 0 10px 0;
	}

.hilite2col p
{
	margin: 3px 14px 3px 14px;
	}

.fieldset{
	padding: 10px;
	border:	1px solid #ccc;
}

legend{
    color: #434951;
    font-size: 14px;
    font-weight: bold;

}

/* Navbars */
.navBar
{
	margin: 0 0 10px 0px;
	padding: 0;
	font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.navBar li
{
    color: #ECEBE2;
    display: block;
    font: italic 14px/20px Georgia,Times New Roman,Times,Serif;
    list-style-type: none;
    margin: 0 0 0 20px;
    padding: 2px 0;
}

.navBar a:link, .navBar a:visited
{
	padding: 0 0 6px 0px;
	text-decoration: none;
	color: #ECEBE2;
}

.navBar a:link.navOn, .navBar a:visited.navOn, .navBar a:hover.navOn
{
	padding: 0 0 6px 0;
	text-decoration: none;
	color: #ECEBE2;
}

.navBar a:hover
{
	padding: 0 0 6px 0px;
	text-decoration: none;
	color: #fd9155 !important;
}

.navBar a:visited { color: #ECEBE2; }

/*Subnav*/
.subnavBar
{
	margin: 0 0 0 0;
	padding: 0;
	font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

ul.navBar {
	background: transparent url(<?php echo $this->cf_primaryurl ?>/portal/images_grey/inset.png) bottom center repeat-x;
	padding-bottom: 15px;
}

.subnavBar li
{
	font: 12px/20px 'Helvetica Neue', Helvetica, Arial, sans-serif;
    margin: 4px 20px;
    padding: 4px 10px;
	display: block;
	color: #3163ce;
	background: #262626;
	list-style-type: none;
}

.subnavBar a:link, .subnavBar a:visited
{
	padding: 0;
	text-decoration: none;
	color: #f57935;
}

.subnavBar a:link.navOff, .subnavBar a:visited.navOff
{
	font-weight: normal;
	padding: 0;
	text-decoration: none;
	color: #f57935;
}

.subnavBar a:hover.navOff
{
	font-weight: normal;
	padding: 0;
	text-decoration: none;
	color: #fff;
}

.subnavBar a:link.navOn, .subnavBar a:visited.navOn, .subnavBar a:hover.navOn
{
	font-weight: normal;
	padding: 0;
	text-decoration: none;
	color: #fff;

}

.subnavBar a:hover
{
	padding: 0;
	text-decoration: none;
	color: #fff;
}

.subnavBar a:visited { color: #f57935; }

/* Phone Nav */
.phonenavBar
{
    margin: 0 0 10px 0;
    padding: 10px 0 0;
}

.phonenavBar li
{
    color: #ecebe2;
    display: block;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 18px;
    font-style: italic;
    font-weight: normal;
    list-style-type: none;
    margin: 0 0 0 20px;
    padding: 0;
    background: none;
}

.phoneNum
{
	color: #ecebe2;
	font: 14px 'Helvetica Neue',Helvetica,Arial,sans-serif;
	margin: 10px 5px;
	display: block;
}

.phonenavBar .subnavBar {
	margin: 0;
}

.forumtable tr {
	min-height: 18px;
	clear: both;
}

/* Row Data */
tr.rowOn td:first-child
{
	background: url("<?php echo $this->cf_primaryurl ?>/portal/images_grey/arrow.png") no-repeat scroll 4px 13px transparent;
}

tr.rowOff td:first-child
{
   	background: url("<?php echo $this->cf_primaryurl ?>/portal/images_grey/arrow.png") no-repeat scroll 4px 13px transparent;
}

tr.rowOn:hover td:first-child, tr.rowOff:hover td:first-child {
   	background: #e8edf0 url("<?php echo $this->cf_primaryurl ?>/portal/images_grey/arrow-blue.png") no-repeat scroll 6px 13px;
}

tr.rowOn td
{
    border-top: 1px solid #DADADA;
    padding: 8px 0 8px 30px;
   	height: 18px;
}

tr.rowOff td
{
    border-top: 1px solid #DADADA;
    padding: 8px 0 8px 30px;
   	height: 18px;
}


/* KB */
.kbtoc{
	font-size:			14px;
	line-height:		120%;
	list-style-type: 	none;
	padding-left:		0px;
	margin-left:		0px;
}

.kbtoc li {
	padding: 2px;
}

.kbtocpage{
	font-size:			14px;
	line-height:		140%;
	list-style-type: 	none;
}

.kbhighlight{
	background-color:	#ffff99;
}

.kbextralist{
	margin:				3px;
	list-style-type:	none;
	line-height:		160%;
}

.page-kb .rowOn
{
	background: none;
	padding: 10px;
}

.page-kb .rowOff
{
	background: none;
	padding: 10px;
}

/* */
div.datarow
{
    display: block;
    margin: 10px 0 40px;
    overflow: auto;
    padding: 0;
}

div.datarow span.left
{
  float: left;
  text-align: left;
  width: 49%;
 }

div.datarow span.right
{
  float: right;
  text-align: right;
  width: 49%;
 }

.nextprev
{
	font-size: 12px;
}

.helpful {
    padding: 15px;
    text-align: right;
}

.sticky
{
	font-size: 10px;
	color: red;
}

.formbuttondiv{
    clear: both;
    margin-top: 20px;
    padding: 0;
}

.formbox{
	padding-top: 		20px;
}

.forumlabel {
	color:				#060;
	font-weight:		bold;
}

.forumtable{
    float: right;
    font-size: 13px;
    margin-bottom: 20px;
    width: 660px;
 	border-bottom: 1px solid #DADADA;
}

.forumtable td {
	padding: 0;
}

.forumpost{
	padding:			3px;
	padding-left: 		14px;
}

.page-forums-posts .rowOn {
	position: relative;
	padding: 20px 10px 25px;
}

.page-forums-posts .rowOff {
	position: relative;
	border: none;
	padding: 5px 10px 5px;

}

.page-forums-topics .rowOn:last-child td:first-child{
	background: none;
}

.page-forums-topics .rowOff:last-child td:first-child{
	background: none;
}

.page-forums-posts td {
	width: 650px;
}

.datalabel {
	color: 				#31363C;
	font-size:			13px;
	line-height: 		120%;
	font-weight:		bold;
}

.captcha_label{
	color:				#000;
	font-size:			14px;
	border: 			1px solid #A51B12;
	padding:			3px 10px 3px 10px;
}

.forumoption{
	color: #434951;
	font: bold 14px/14px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
	border-bottom: 1px dotted #D7DBDE;
	width: 660px;
	padding: 10px 0 5px;
}

.forumform{
	padding-top: 20px;
	clear: both;
	width: 75%;
}

.requestwrap{
	width: 638px;
}

.requestpad{
	padding:			8px 0 8px 10px;
}

.requestpad img{
	max-width: 100%;
	height:auto;
}
.error {
	color: #A51B12;
}

.required {
	color: #95481e;
}

.bold{
	font-weight: bold;
}

.namedate{
	color: #111;
	font-weight: bold;
	font-size: 14px;
}

.score {
	text-align: right;
	font-weight: bold;
	padding-right: 10px;
}

pre{
	font: 				100% courier,monospace;
	overflow: 			auto;
	overflow-x: 		auto;
	width: 				90%;
	padding: 			1em 1em 1em 1em;
	color: 				#000
    background: none repeat scroll 0 0 #ECF0F1;
    border: 1px solid #E0E3E4;
    }

.initsubject{
	color: 				#7F7F7F;
}

.request_summary{
	display:			block;
	word-break:         break-all;
	padding-right:      20px;
}

.page-request-history td {
	padding: 8px;
	vertical-align: top;
}

.feedback_box_error{
    background: none repeat scroll 0 0 #F5E2E1;
    border: 1px solid #DBB4B2;
    color: #A51B12;
    font-weight: bold;
    padding: 10px;
}

.feedback_box_positive{
    background: none repeat scroll 0 0 #EBF5E1;
    border: 1px solid #B7D29C;
    color: #61872F;
    font-weight: bold;
    padding: 10px;
}

.sending_note{
	color:				red;
}

.calendar_input{
	padding-right: 10px;
	padding-top: 5px;
	padding-bottom: 5px;
	position: relative;
	text-align:	bottom;
	cursor: pointer;
	padding-left: 34px;
	border: 1px solid #BBBBBB;
	background-color: #fff;
}

.calendar_btn{
	position: absolute;
	top: 1px;
	left: 2px;
	height: 24px;
	width: 24px;
	background: transparent url(<?php echo $this->cf_primaryurl ?>/portal/images/calendar.png) no-repeat left top;
}

.page-forums .rowOn, .page-forums .rowOff {
	background: none;
	padding: 10px;
}

hr {
	border: none;
	border-bottom: 1px dotted #D7DBDE;
	height: 1px;
	width: 100%;
}

.button, input[type="submit"], input[type="button"]  {
   background: #484848;
   padding: 6px 30px;
   -webkit-border-radius: 3px;
   -moz-border-radius: 3px;
   border: none;
   border-radius: 3px;
   color: #ffffff;
   font-size: 18px;
   font-family: Georgia,Times New Roman,Times,Serif;
   text-decoration: none;
   vertical-align: top;
   font-weight: normal;
   cursor: pointer;
   font-style: italic;
   margin-top: 7px;
   }

.button:hover, .button:active, input[type="submit"]:hover, input[type="button"]:hover {
   background: #5c5c5c;
   }

#content2col input[type="text"], textarea, input[type="password"] {
	background: #fbfdff url(<?php echo $this->cf_primaryurl ?>/portal/images_grey/form-bg.png) top left repeat-x;
    border: 1px solid #c9d2d7;
   	padding: 10px;
   	font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
   	font-size: 13px;
	margin: 5px 5px 5px 0;
}

tr.rowOn td.forum-name, tr.rowOff td.forum-name {
    display: block;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 12px;
    text-align: right;
    width: auto;
    background: none;
}

.page-search input[type="text"] {
	width: 200px !important;
}

.page-search #area {
	margin: 0 15px 0 5px;
}

.tag-block-home{
	border-top: 1px solid #DADADA;
}

.tag-block-page{
	margin: 0px;
	padding-left: 42px;
}

.tag-block a{
	display: inline-block;
	height: 26px;
	line-height: 26px;
}

.tag-sep{
	font-size: 11px;
	color: #111;
}

.tag-table{
    font-size: 13px;
    width: 100%;
    margin: 15px 0 20px 0;
 	border-bottom: 1px solid #DADADA;
}

.tag-table td {
	padding: 0;
}

.tag-header{
	margin-bottom: 30px;
}


.file-extension {
    display: inline-block;
    margin: 5px 0;
}
.file-name {
    display: inline-block;
    padding: 0 10px;
    margin-bottom: 5px;
}
