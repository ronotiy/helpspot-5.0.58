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


#footer {
	height: 40px;
	_padding-top: 35px;
}

#footer input[type="submit"] { 
	margin-top: 0px;
}

#footer #area { 
    float: left;
    margin-top: 10px;
    width: 150px !important;
    margin-right: 10px;
}

#footer input { 
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
   margin-top: 20px;
   _margin-top: 0px;
}

#footer input#q { 
    color: #80bd36;
    font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif;
    font-size: 16px;
    height: 15px;
    margin: 0px 25px 0 15px;
    padding: 11px 7px;
    width: 290px !important;  
    float: left;  
    background: #fff;
} 

.button, input[type="submit"], input[type="button"]  { 
	margin-top: -21px;
	
}