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
	background: #fff url(<?php echo $this->cf_primaryurl ?>/portal/images_blue/bluebar.jpg) 0 0 repeat-x;
	color: #505669;
	font-family:  'Helvetica Neue', Helvetica, Arial, sans-serif;
}

a:link, a:active, a:visited { color: #1e4a8b; text-decoration: none;}

a:hover { color: #1e4a8b; text-decoration: underline;}

p {
	font: 13px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

h1
{
	color: #31363c;
	margin: 10px 0 5px 0;
	font: bold 22px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	line-height: 28px;
}

h2
{
	color: #31363c;
	font: bold 18px/18px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 10px 0;
}

h3
{
	color: #434951;
	font: bold 15px/13px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
}

h4
{
	color: #434951;
	font: bold 13px/13px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	margin: 0;
	margin-top: 10px;
}

h5 { font: bold 13px/16px 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
	background-color: transparent;
	text-align:left;
}

#banner
{
    background-color: transparent;
    color: #FFFFFF;
    font-size: 24px;
    margin-bottom: 0;
    padding: 26px 2px 20px 8px;
    text-align: left;
    text-shadow: 1px 1px 0 #374265;
	}


#leftSidebar
{
	float: left;
	width:  160px;
	\width: 160px;
	w\idth: 160px;
	margin: 20px 0 40px 0;
	padding: 0;
	padding-right: 10px;
	background-color: transparent;
	/* height: 100%; */
	display: inline;
	border-right: 1px dotted #D7DBDE;
	}

#content2col
{
	font: 12px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	padding: 0;
	padding-left: 20px;
	margin: 20px 20px 0 165px;
	background-color: transparent;
	line-height: 18px;
	}

#content2col li {
	line-height: 20px;
}


#footer
{
    background-color: #1E4A8B;
    clear: both;
    color: #999999;
    font: 12px/14px 'Helvetica Neue',Helvetica,Arial,sans-serif;
    margin: 40px 0 20px 0;
    padding: 10px 20px;
    text-align: center;
    width: 700px;
    -webkit-border-radius: 3px; -khtml-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;
	}

#footer a{
	color: #fff;
	text-decoration: underline;
}

#footer strong {
    color: #FFFFFF;
    font-size: 11px;
    font-weight: normal;}

#footer input[type="text"] {
    color: #7D8390;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 16px;
    height: 15px;
    margin-right: 15px;
    padding: 7px;
    width: 310px !important;
}

#footer input[type="submit"] {
   border-top: 1px solid #81be37;
   background: #80bd36;
   background: -webkit-gradient(linear, left top, left bottom, from(#95cb4a), to(#80bd36));
   background: -moz-linear-gradient(top, #95cb4a, #80bd36);
   padding: 6px 30px;
   -webkit-border-radius: 3px;
   -moz-border-radius: 3px;
   border: none;
   border-radius: 3px;
   -webkit-box-shadow: 0 1px 0 #0f2f5d;
   -moz-box-shadow:  0 1px 0 #0f2f5d;
   box-shadow:  0 1px 0 #0f2f5d;
   text-shadow: 1px 1px 0 #ADE261;
   color: #4b8112;
   font-size: 18px;
   font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
   text-decoration: none;
   vertical-align: middle;
   font-weight: bold;
   cursor: pointer;
   margin-left: 15px;
}

#footer input[type="submit"]:hover {
   border-top-color: #9bcf4c;
   background: #9bcf4c;
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
	margin: 0 0 10px 5px;
	padding: 0;
	font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.navBar li
{
	font: 12px/20px 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
	color: #1e4a8b;
}

.navBar a:link.navOn, .navBar a:visited.navOn, .navBar a:hover.navOn
{
	font-weight: bold;
	padding: 0 0 6px 10px;
	text-decoration: none;
	color: #1e4a8b;
}

.navBar a:hover
{
	padding: 0 0 6px 10px;
	text-decoration: underline;
	color: #1e4a8b;
}

.navBar a:visited { color: #1e4a8b; }

/*Subnav*/
.subnavBar
{
	margin: 0 0 0 12px;
	padding: 0;
	font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.subnavBar li
{
	font: 12px/20px 'Helvetica Neue', Helvetica, Arial, sans-serif;
	margin: 0;
	padding: 0;
	display: block;
	color: #3163ce;
	background: none;
	list-style-type: none;
}

.subnavBar a:link, .subnavBar a:visited
{
	padding: 0 0 6px 15px;
	text-decoration: none;
	color: #1e4a8b;
}

.subnavBar a:link.navOff, .subnavBar a:visited.navOff
{
	font-weight: normal;
	padding: 0 0 6px 15px;
	text-decoration: none;
	color: #1e4a8b;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images_blue/bullet.jpg) no-repeat left 5px;
}

.subnavBar a:hover.navOff
{
	font-weight: normal;
	padding: 0 0 6px 15px;
	text-decoration: underline;
	color: #1e4a8b;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images_blue/bullet.jpg) no-repeat left 5px;
}

.subnavBar a:link.navOn, .subnavBar a:visited.navOn, .subnavBar a:hover.navOn
{
	font-weight: bold;
	padding: 0 0 6px 15px;
	text-decoration: none;
	color: #1E4A8B;
	background: url(<?php echo $this->cf_primaryurl ?>/portal/images_blue/bullet.jpg) no-repeat left 5px;

}

.subnavBar a:hover
{
	padding: 0 0 6px 15px;
	text-decoration: underline;
	color: #1E4A8B;
}

.subnavBar a:visited { color: #1E4A8B; }

/* Phone Nav */
.phonenavBar
{
    border-top: 1px dotted #D7DBDE;
    margin: 0 0 10px 15px;
    padding: 10px 0 0;
}

.phonenavBar li
{
    color: #31363C;
    display: block;
    font-size: 13px;
    font-weight: bold;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

.phoneNum
{
	color: #505669;
	font-weight: normal;
}

.phonenavBar .subnavBar {
	margin: 0;
}

/* Row Data */
.page-home h2{
	margin: 0px;
}

.rowOn
{
	background-color: #eff2f6;
	padding: 3px;
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
	background-color: #eff2f6;
	padding: 10px;
	border-top: solid 1px #ebeff5;
	border-bottom: solid 1px #ebeff5;
}

.page-kb .rowOff
{
	background-color: #fff;
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
	font-size: 			13px;
	float:				right;
	margin-bottom: 20px;
}

.forumtable td {
	padding: 8px;
}

.forumpost{
	padding:			3px;
	padding-left: 		14px;
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
	width: 100%;
	padding: 10px 0 5px;
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
	padding:			20px;
}

.requestpad img{
	max-width: 100%;
	height:auto;
}
.error {
	color: #A51B12;
}

.required {
	color: #486F14;
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
	border: 			1px solid #e9eef5;
	overflow: 			auto;
	overflow-x: 		auto;
	width: 				90%;
	padding: 			1em 1em 1em 1em;
	background: 		#EFF2F6;
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

.page-request-history td {
	padding: 8px;
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

hr {
border: none;
border-bottom: 1px dotted #D7DBDE;
height: 1px;
width: 100%;
}

.button, input[type="submit"], input[type="button"]  {
   border-top: 1px solid #81be37;
   background: #80bd36;
   background: -webkit-gradient(linear, left top, left bottom, from(#95cb4a), to(#80bd36));
   background: -moz-linear-gradient(top, #95cb4a, #80bd36);
   padding: 7px 16px;
   -webkit-border-radius: 3px;
   -moz-border-radius: 3px;
   border: none;
   border-radius: 3px;
   text-shadow: 1px 1px 0 #ADE261;
   color: #4b8112;
   font-size: 14px;
   font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
   text-decoration: none;
   vertical-align: middle;
   font-weight: bold;
   cursor: pointer;
   }

.button:hover, .button:active, input[type="submit"]:hover, input[type="button"]:hover {
   border-top-color: #9bcf4c;
   background: #9bcf4c;
   }

#content2col input[type="text"], textarea, input[type="password"] {
    background: none repeat scroll 0 0 #F8F9FB;
    border: 1px solid #CDD2DE;
   	padding: 10px;
   	font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
   	font-size: 13px;
	margin: 5px 5px 5px 0;
}

#helpspot-link {
	margin: 15px auto 40px;
   	font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
   	font-size: 13px;
   	padding-bottom: 10px;
}

.tag-cloud-homepage .tag-cloud-td{
	padding: 0px;
}

.tag-block-home{
	background-color: #EFF2F6;
	padding: 8px;
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
	padding: 8px; /* not for grey design */
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
