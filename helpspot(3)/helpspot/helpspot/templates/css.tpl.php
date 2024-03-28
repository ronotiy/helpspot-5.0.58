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
}

p {
	font: 12px/16px Arial, Helvetica, sans-serif;
}

h1
{
	color: #39399c;
	margin: 10px 0 2px 0;
	font: bold 18px/18px Arial, Helvetica, sans-serif;
}

h2
{
	color: black;
	font: bold 15px/18px Arial, Helvetica, sans-serif;
	padding: 0;
	margin: 0;
	margin-top: 10px;
}

h3
{
	color: #373c55;
	font: bold 11px/13px Arial, Helvetica, sans-serif;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
}

h4
{
	color: #000;
	font: bold 11px/13px Arial, Helvetica, sans-serif;
	padding: 0;
	margin: 0;
	margin-top: 10px;
}

h5 { font: bold 13px/16px Arial, Helvetica, sans-serif;
	margin-bottom: -10px;
	}
/*
a:link
{
	color: #3366cc;
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
	margin-bottom:  5px;
	line-height:	120%;
}

/* DIVs */

#container
{
	width: 760px;
	padding: 0 0 0 0;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0px;
	background-color: #fff;
	border-left: 1px #ccc solid;
	border-right: 1px #ccc solid;
	border-bottom: 1px #ccc solid;
	text-align:left;
}

#banner
{
	padding: 2px;
	margin-bottom: 0;
	padding-top: 24px;
	padding-left: 8px;
	background-color: #5169b3;
	color: #fff;
	text-align: left;
	font-size:	26px;
	font-weight: bold;
	border-bottom: 1px solid #ccc;
	}


#leftSidebar
{
	float: left;
	width:  160px;
	\width: 160px;
	w\idth: 160px;
	margin: 20px 0 0 0;
	padding: 0;
	padding-right: 10px;
	background-color: transparent;
	/* height: 100%; */
	display: inline;
	border-right: 1px #ccc solid;
	}

#content2col
{
	font: 12px Arial, Helvetica, sans-serif;
	padding: 0;
	padding-left: 20px;
	margin: 20px 20px 0 165px;
	background-color: transparent;
	}

#footer
{
	padding: 0;
	margin: 15px 0 15px 165px;
	background-color: transparent;
	clear: both;
	font: 12px/14px Arial, Helvetica, sans-serif;
	color: #999;
	width: 575px;
	padding-top: 5px;
	text-align: center;
	}

#footer a{
	color: #999;
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
	padding: 4px;
	border:	1px solid #ccc;
}

legend{
	padding-left:		4px;
	padding-right:		4px;
	font-weight:		bold;
	color:				#000;

}

/* Navbars */
.navBar
{
	margin: 0 0 10px 20px;
	padding: 0;
	font-family: Arial, Helvetica, sans-serif;
}

.navBar li
{
	font: 12px/20px Arial, Helvetica, sans-serif;
	margin: 0;
	padding: 0;
	display: block;
	color: #3163ce;
	list-style-type: none;
}

.navBar a:link, .navBar a:visited
{
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #3163ce;
}

.navBar a:link.navOn, .navBar a:visited.navOn, .navBar a:hover.navOn
{
	font-weight: bold;
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #39399c;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images/blue-tri.gif) no-repeat left top;
}

.navBar a:hover
{
	padding: 0 0 6px 10px;
	text-decoration: underline;
	color: #39399c
}

.navBar a:visited { color: #3163ce; }

/*Subnav*/
.subnavBar
{
	margin: 0 0 0 12px;
	padding: 0;
	font-family: Arial, Helvetica, sans-serif;
}

.subnavBar li
{
	font: 12px/20px Arial, Helvetica, sans-serif;
	margin: 0;
	padding: 0;
	display: block;
	color: #3163ce;
	background: none;
	list-style-type: none;
}

.subnavBar a:link, .subnavBar a:visited
{
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #3163ce;
}

.subnavBar a:link.navOff, .subnavBar a:visited.navOff
{
	font-weight: normal;
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #3163ce;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images/blue-dot.gif) no-repeat left top;
}

.subnavBar a:hover.navOff
{
	font-weight: normal;
	padding: 0 0 6px 10px;
	text-decoration: underline;
	color: #39399c;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images/blue-dot.gif) no-repeat left top;
}

.subnavBar a:link.navOn, .subnavBar a:visited.navOn, .subnavBar a:hover.navOn
{
	font-weight: bold;
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #39399c;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images/blue-tri.gif) no-repeat left top;
}

.subnavBar a:hover
{
	padding: 0 0 6px 10px;
	text-decoration: underline;
	color: #39399c;
}

.subnavBar a:visited { color: #3163ce; }

/* Phone Nav */
.phonenavBar
{
	margin: 0 0 10px 30px;
	padding: 0;
	font-family: Arial, Helvetica, sans-serif;
}

.phonenavBar li
{
	font: 12px/20px Arial, Helvetica, sans-serif;
	font-weight: bold;
	margin: 0;
	padding: 0;
	display: block;
	color: #999;
	list-style-type: none;
}

.phoneNum
{
	color: #000;
	font-weight: normal;
}

/* Row Data */
.rowOn
{
	background-color: #f8f8f8;
	padding: 3px;
	border-top: 1px solid #ddd;
	border-bottom: 1px solid #ddd;
}

.rowOff
{
	background-color: #fff;
	padding: 3px;
}

/* KB */
.kbtoc{
	font-size:			14px;
	line-height:		120%;
	list-style-type: 	none;
	padding-left:		0px;
	margin-left:		0px;
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

/* */
div.datarow
{
	margin: 0px;
	padding: 0px;
	margin-top: 10px;
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

.helpful
{
	text-align: right;
	padding: 15px;
}

.sticky
{
	font-size: 10px;
	color: red;
}

.formbuttondiv{
	border-top: 		2px solid #dab631;
	background:			#FEE587;
	margin-top:			10px;
	padding:			10px;
	clear: 				both;
}

.formbox{
	padding-top: 		20px;
}

.forumlabel {
	color:				#060;
	font-weight:		bold;
}

.forumtable{
	font-size: 			12px;
	float:				right;
}

.forumpost{
	padding:			3px;
	padding-left: 		14px;
}

.datalabel {
	color: 				#555;
	font-size:			11px;
	line-height: 		120%;
	font-weight:		bold;
}

.captcha_label{
	color:				#000;
	font-size:			14px;
	border: 			1px solid red;
	padding:			3px 10px 3px 10px;
}

.forumoption{
	color: #000;
	font: bold 14px/14px Arial, Helvetica, sans-serif;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
	border-bottom: 1px #000 solid;
	width: 100%;
}

.forumform{
	padding-top: 20px;
	clear: both;
	width: 75%;
}

.requestwrap{
	width: 75%;
}

.requestpad{
	padding:			3px;
	padding-left: 		14px;
}

.requestpad img{
	max-width: 100%;
	height:auto;
}

.error {
	color: red;
}

.required {
	color: #b05050;
}

.bold{
	font-weight: bold;
}

.namedate{
	color: #39399c;
	font-weight: bold;
}

.score {
	text-align: right;
	font-weight: bold;
	padding-right: 10px;
}

pre{
	font: 				100% courier,monospace;
	border: 			1px solid #ccc;
	overflow: 			auto;
	overflow-x: 		scroll;
	width: 				90%;
	padding: 			1em 1em 1em 1em;
	background: 		#fff7f0;
	color: 				#000
}

.initsubject{
	color: 				#7F7F7F;
}

.request_summary{
	display:			block;
	overflow:			hidden;
	height:				14px;
	word-break:         break-all;
	padding-right:      20px;
}

.feedback_box_error{
	border:				1px solid red;
	padding:			10px;
	color:				red;
	font-weight:		bold;
}

.feedback_box_positive{
	border:				1px solid green;
	padding:			10px;
	color:				green;
	font-weight:		bold;
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

.tag-block-home{

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
	color: #ccc;
}

.tag-table{
    font-size: 13px;
    width: 100%;
    margin: 15px 0 20px 0;
 	border-bottom: 1px solid #DADADA;
}

.tag-table td {
	padding: 8px; /* not for grey design */
}

.tag-header{
	margin-bottom: 30px;
}

#helpspot-link{
	font-size: 12px;
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
