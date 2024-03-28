<?php

//Make sure nothing goes out until the end of the script
function cleanOutputBuffer($buffer){
	return trim($buffer);
}

//Output buffer so no extra line breaks go out
ob_start("cleanOutputBuffer");

include(cBASEPATH.'/helpspot/lib/class.api.base.php');

if(isset($_REQUEST['method']) && substr($_REQUEST['method'], 0, 7) == 'private'){
	include(cBASEPATH.'/helpspot/lib/class.api.private.php');
	$api = new api_private();
}else{
	include(cBASEPATH.'/helpspot/lib/class.api.public.php');
	$api = new api_public();
}

if( ! $api->disabled )
{
	$api->process();
}

return ob_get_clean();
