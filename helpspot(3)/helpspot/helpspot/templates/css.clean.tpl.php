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

/* Import Inter font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');

body
{
	margin: 0;
	padding: 0;
	background: #fff;
	color: #505669;
	font-family:  'Inter', sans-serif;
    font-size: 16px;
}

a:link, a:active, a:visited { color: #3182ce; text-decoration: none;}

a:hover { color: #1e4a8b; text-decoration: underline;}

input,textarea,select{
    font-size:16px;
}

h1
{
	color: #31363c;
	margin: 10px 0 5px 0;
	font-size: 28px;
	line-height: 28px;
}

h2
{
	color: #31363c;
	font-size: 23px;
	padding: 0;
	margin: 10px 0;
}

h3
{
	color: #434951;
	font-size: 21px;
	padding: 0;
	margin: 0;
	margin-bottom: 2px;
}

h4
{
	color: #434951;
	font-size: 19px;
	padding: 0;
	margin: 0;
	margin-top: 10px;
}

h5 {
    font-size: 19px;
}

img { border: 0; }

.subheading{
	margin-bottom:  5px;
	line-height:	120%;
}

/* DIVs */

#container
{
    display: grid;
    min-height: 100vh;
    grid-template-columns: 30% 30% auto;
    grid-template-rows: 116px
                        1fr;
    grid-template-areas:
    "header header footer"
    "sidebar content content";

	max-width: 768px;
	padding: 0 0 0 0;
	margin-left: auto;
	margin-right: auto;
	margin-top: 0px;
}

@media only screen and (min-width: 1024px) {
    #container{
        grid-template-columns: 23% 37% auto;
        max-width: 1024px;
    }
}

#banner
{
    grid-area: header;
    display:flex;
    align-items: center;
    font-size: 24px;
    text-align: left;
    color: #161e2e;
    border-bottom: 2px solid #f4f5f7;
    margin-bottom: 1.5rem;
}

#leftSidebar
{
    grid-area: sidebar;
    padding-right: 1rem;
}

#content2col
{
    grid-area: content;
    line-height: 1.5rem;
    padding-left: 1rem;
}

#content2col li {
	line-height: 20px;
}

#content2col img {
    max-width: 100%;
}

#footer
{
    grid-area: footer;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #f4f5f7;
    align-items: center;
    display: flex;
}

#footer form,
.page-search form{
    width: 100%;
}

#footer form div p,
.page-search form div p{
    display: flex;
    justify-content: flex-end;
    margin: 0;
}

#footer form div p input[type="text"],
.page-search form div p input[type="text"]{
    width: 100%;
    padding: .25rem .50rem;
    line-height: 1.5rem;
    border-radius: .375rem;
    border-color: #d2d6dc;
    border-width: 1px;
    margin-right: 8px;
    background-color: #fff;
    border-style: solid;
}

.page-search form input[type="submit"]{
    margin: 5px 5px 5px 0;
}

#content2col p{
    margin-top:0;
}

#content2col form{
    margin-top: 20px;
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
	border:	3px solid #ccc;
    border-radius: 2px;
}

legend{
    color: #434951;
    font-weight: bold;

}

/* Navbars */
.navBar
{
	margin: 0;
	padding: 0;
}

.navBar li
{
	list-style-type: none;
    padding:0;
    margin:0;
    border-radius: .375rem;
}

.navBar li:hover{
    background-color: #f9fafb;
}

.navBar li:hover a{
    color: #161e2e;
}

.navBar a{
    display: flex;
    align-items: center;
    color: #4b5563;
    padding: .5rem;
    margin-top: .25rem;
    line-height: 1.25rem;
    font-size: .875rem;
    font-weight: 500;
    text-decoration: none;
    border-radius: .375rem;
}

.navBar a.navOn{
    color: #161e2e;
    background-color: #f4f5f7;
}

.navBar a.home{
    background-image: url(<?php echo $this->cf_primaryurl ?>/portal/images_clean/home.svg);
    background-repeat: no-repeat;
    background-size: 20px 20px;
    background-position: 8px center;
    padding-left: 36px;
}

.navBar a.request{
    background-image: url(<?php echo $this->cf_primaryurl ?>/portal/images_clean/question.svg);
    background-repeat: no-repeat;
    background-size: 20px 20px;
    background-position: 8px center;
    padding-left: 36px;
}

.navBar a.check{
    background-image: url(<?php echo $this->cf_primaryurl ?>/portal/images_clean/check.svg);
    background-repeat: no-repeat;
    background-size: 20px 20px;
    background-position: 8px center;
    padding-left: 36px;
}

.navBar a.book{
    background-image: url(<?php echo $this->cf_primaryurl ?>/portal/images_clean/book.svg);
    background-repeat: no-repeat;
    background-size: 20px 20px;
    background-position: 8px center;
    padding-left: 36px;
}

.navBar a.books{
    background-image: url(<?php echo $this->cf_primaryurl ?>/portal/images_clean/books.svg);
    background-repeat: no-repeat;
    background-size: 19px 19px;
    background-position: 8px center;
    padding-left: 36px;
}

/*Subnav*/
.subnavBar{
    margin: 0;
    padding: 0;
}

.subnavBar a
{
	padding-left: 1.5rem;
}

.forumtable{
    width: 100%;
    margin-bottom: 40px;
}

/* Phone Nav */
.phonenavBar
{
    margin: 20px 0 10px 8px;
    padding: 0;
}

.phonenavBar li
{
    color: #31363C;
    display: block;
    font-weight: bold;
    list-style-type: none;
    margin: 0;
    margin-bottom: 4px;
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
	background-color: #f9fafb;
	padding: 3px;
}

.rowOff
{
	padding: 3px;
}

.rowOn td, .rowOff td{
    padding: 12px;
}

/* KB */
.kbtoc{
	line-height:		120%;
	list-style-type: 	none;
	padding-left:		0px;
	margin-left:		0px;
}

.kbtoc a{
    display: inline-block;
    padding: 6px 8px;
    border-radius: 3px;
    margin: 2px 0;
}

.kbtoc > li > a{
    display: block;
    border-bottom: 2px solid #f4f5f7;
}

.kbtocpage{
	list-style-type: 	none;
}

.kbhighlight{
	background-color:	#f7f78f;
}

.kbextralist{
	margin:				3px;
	list-style-type:	none;
}

.kbextralist li{
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.kbextralist li div{
    margin-right: 12px;
}

.page-kb .rowOn
{
	border-bottom: 2px solid #f4f5f7;
    background-color: #fff;
    padding: 10px 0;
    min-height: 48px;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    justify-content: center;
}

.page-kb .rowOff
{
    border-bottom: 2px solid #f4f5f7;
	background-color: #fff;
	padding: 10px 0;
    min-height: 48px;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    justify-content: center;
}

.page-kb .rowOn, .page-kb .rowOff{
    font-size: 18px;
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

}

.helpful {
    padding: 15px;
    text-align: right;
}

.sticky
{
	color: #DB504A;
}

.formbuttondiv{
    clear: both;
    margin-top: 20px;
    padding: 0;
}

.formbox{
	padding-top: 		20px;
}

.datalabel {
	color: 				#31363C;
	line-height: 		120%;
	font-weight:		bold;
}

.captcha_label{
	color:				#000;
	font-size:			14px;
	border: 			1px solid #A51B12;
	padding:			3px 10px 3px 10px;
}

.requestwrap{
	width: 75%;
}

.requestwrap .forumoption{
    display: none;
}

.requestwrap select{
    background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M7 7l3-3 3 3m0 6l-3 3-3-3' stroke='%239fa6b2' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
    background-repeat: no-repeat;
    background-color: #fff;
    border-color: #d2d6dc;
    border-width: 1px;
    border-radius: .375rem;
    padding: .25rem .50rem;
    padding-right: 2rem;
    font-size: 1rem;
    line-height: 1.5;
    background-position: right .5rem center;
    background-size: 1.5em 1.5em;
}

.requestwrap div[id*="Custom"]{
    margin-bottom: 16px;
}

.requestwrap select[id*="Custom"]{
    margin-bottom: 4px;
}

.requestwrap input[type="checkbox"]{
    width: 18px;
    height: 18px;
}

.requestpad{
	padding:			20px;
    margin-bottom: 20px;
}

.requestpad img{
	max-width: 100%;
	height:auto;
}
.error {
	color: #A51B12;
}

.required {
	color: #DB504A;
}

.bold{
	font-weight: bold;
}

.namedate{
	color: #87A330;
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
    border-radius: 2px;
    margin-bottom: 20px;
}

.feedback_box_positive{
    background: none repeat scroll 0 0 #EBF5E1;
    border: 1px solid #B7D29C;
    color: #61872F;
    font-weight: bold;
    padding: 10px;
    border-radius: 2px;
    margin-bottom: 20px;
}

.sending_note{
	color: #DB504A;
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
border-bottom: 2px solid #f4f5f7;
height: 1px;
width: 100%;
}

.button, input[type="submit"], input[type="button"]  {
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition-property: background-color,border-color,color,fill,stroke,opacity,box-shadow,transform;
    transition-timing-function: cubic-bezier(.4,0,.2,1);
    transition-duration: .15s;
    color: #fff;
    padding: .25rem 1rem;
    line-height: 1.5rem;
    font-weight: 500;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #3182ce;
}

.button:hover, .button:active, input[type="submit"]:hover, input[type="button"]:hover {
   background-color: #4299e1;
}

#content2col input[type="text"], textarea, input[type="password"] {
    padding: .25rem .50rem;
    line-height: 1.5rem;
    border-radius: .375rem;
    border-color: #d2d6dc;
    border-width: 1px;
    margin-right: 8px;
    background-color: #fff;
    border-style: solid;
	margin: 5px 5px 5px 0;
}

#helpspot-link {
    display: flex;
    justify-content: center;
    width: 100%;
    font-size: 13px;
    padding-bottom: 30px;
    margin-top: 50px;
}

.tag-cloud-homepage .tag-cloud-td{
	padding: 0px;
}

.tag-block-home{
    background-color: #f9fafb;
    padding: 12px;
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
	color: #111;
}

.tag-table{
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
