<?php
/*
It's recommended that if you make changes to this template or any other template that you first copy the template into

/custom_templates

and make modifications on the copied version. HelpSpot will automatically use the copied version rather than the original.
This will protect your changes from being overwritten during an upgrade.
*/

//Send the HTTP header indicating that this is a stylesheet
header('Content-type: text/css');
header('Content-Disposition: inline; filename="ie.style.css"');
?>

.rowOn td
{
    border-top: 1px solid #DADADA;
    display: block;
    padding: 8px 0 8px 10px;
   	height: 18px;
}

.rowOff td
{
    border-top: 1px solid #DADADA;
    display: block;
    padding: 8px 0 8px 10px;
   	height: 18px;
}

.page-home .rowOn td
{
    border-top: 1px solid #DADADA;
    display: block;
    padding: 8px 0 8px 30px;
	background: url("<?php echo $this->cf_primaryurl ?>/portal/custom/arrow.png") no-repeat scroll 4px 13px transparent;
   	height: 18px;
}

.page-home .rowOff td
{
    border-top: 1px solid #DADADA;
    display: block;
    padding: 8px 0 8px 30px;
   	background: url("<?php echo $this->cf_primaryurl ?>/portal/custom/arrow.png") no-repeat scroll 4px 13px transparent;
   	height: 18px;
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
   vertical-align: middle;
   font-weight: normal;
   cursor: pointer;
   font-style: italic;
   margin-top: 0;
   }

.page-request-check #accesskey-btn { margin-top: -22px; }
   
.button:hover, .button:active, input[type="submit"]:hover, input[type="button"]:hover {
   background: #5c5c5c;
   }

#footer {
	padding: 20px 0 0;
	_padding: 20px 0;
}

#footer #area { 
    float: left;
    margin-top: 25px;
    width: 250px !important;
    margin-right: 10px;
}

#footer input { 
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
   font-style: italic;
   float: left;
   margin: 0px 0 0 10px;
}

#footer input#q { 
    color: #7D8390;
    font-family: Georgia,Times New Roman,Times,Serif;
    font-size: 16px;
    height: 15px;
    margin: 0px 25px 0 15px;
    padding: 11px 7px;
    width: 390px !important;  
    float: left;  
    background: #fff;
} 

.subnavBar li
{
	font: 12px/20px 'Helvetica Neue', Helvetica, Arial, sans-serif;
    margin: 4px 20px 4px 0;
    padding: 4px 10px;
	display: block;
	color: #3163ce;
	background: #262626;
	list-style-type: none;
}   

.page-forums-posts .rowOn:hover, .page-forums-posts .rowOff:hover { 
	border: none !important;
}

.page-forums-posts .rowOn td, .page-forums-posts .rowOff td, { 
	border-top: none !important;
	border-bottom: none !important;
	padding: 20px 10px;
}

.page-forums-posts .rowOff { 
	padding: 5px 0;
}

.forumtable { 
	margin: 0 0;

}

#leftSidebar {
	_outline: none;
	_margin-right: -4px;
}
