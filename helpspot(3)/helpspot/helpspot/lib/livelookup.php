<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

function apiLiveLookup($searchdata, $output = 'lookup')
{
    global $user;

    include_once cBASEPATH.'/helpspot/lib/class.livelookup.php';

    $feedbackArea = '';
    $results = '';

    //check for xml parser
    if (function_exists('xml_parser_create')) {
        $fm['source_id'] = isset($searchdata['source_id']) ? $searchdata['source_id'] : 0;
        $fm['customer_id'] = isset($searchdata['customer_id']) ? $searchdata['customer_id'] : '';
        $fm['first_name'] = isset($searchdata['first_name']) ? $searchdata['first_name'] : '';
        $fm['last_name'] = isset($searchdata['last_name']) ? $searchdata['last_name'] : '';
        $fm['email'] = isset($searchdata['email']) ? $searchdata['email'] : '';
        $fm['phone'] = isset($searchdata['phone']) ? $searchdata['phone'] : '';

        $ll_sources = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));
        $ll_source = $ll_sources[$fm['source_id']];

        //Add info of person who's performing action
        $searchdata['acting_person_xperson'] = $user['xPerson'];
        $searchdata['acting_person_fusertype'] = $user['fUserType'];
        $searchdata['acting_person_fname'] = $user['sFname'];
        $searchdata['acting_person_lname'] = $user['sLname'];
        $searchdata['acting_person_email'] = $user['sEmail'];

        //Build query string

        $restricted_parameters = [
            'tEmailHeaders',
            'tNote',
            'tBody',
            'note_content',
        ];

        foreach ($searchdata as $k=>$v) {
            if (in_array($k, $restricted_parameters)) {
                unset($searchdata[$k]);
            }
        }

        if ($ll_source['type'] == 'http' || $ll_source['type'] == 'https') {
            $llQueryString = [];
            $urlInfo = parse_url($ll_source['path']);

            if (isset($urlInfo['query']) && ! empty($urlInfo['query'])) {
                // If there is a query string in provided URI
                // Parse it, remove it, and finally add it
                // to the query string used in request
                parse_str($urlInfo['query'], $llQueryString);

                // Remove query string from URI
                $ll_source['path'] = substr(
                    $ll_source['path'], // String to cut off
                    0, // Start from very beginning of string
                    strpos($ll_source['path'], $urlInfo['query']) - 1 // Cut off from query string on (end at this strpos), going back one position to delete "?"
                );
            }

            $searchdata = array_merge($searchdata, $llQueryString);
        }

        $querystring = http_build_query($searchdata);

        /*****************************************
        PERFORM ACTIONS
        *****************************************/

        //get XML file
        if ($ll_source['type'] == 'cmdline') {
            //if shell_exec is not available show an error
            if (! function_exists('shell_exec')) {
                $feedbackArea = '<p class="red" style="padding:6px;"><b>'.lg_livelookup_shellexec.'</b></p>';
            }

            $cmdpath = ' '.(! empty($fm['customer_id']) ? escapeshellarg($fm['customer_id']) : "''").' '.(! empty($fm['first_name']) ? escapeshellarg($fm['first_name']) : "''").' '.(! empty($fm['last_name']) ? escapeshellarg($fm['last_name']) : "''").' '.(! empty($fm['email']) ? escapeshellarg($fm['email']) : "''").' '.(! empty($fm['phone']) ? escapeshellarg($fm['phone']) : "''").' ';
            //Add custom fields
            ksort($GLOBALS['customFields']);
            if (isset($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $k=>$v) {
                    $cmdpath .= (isset($searchdata['Custom'.$k]) && $searchdata['Custom'.$k] != '' ? ' '.escapeshellarg($searchdata['Custom'.$k]) : " ''");
                }
            }
            $xmlFile = shell_exec($ll_source['path'].$cmdpath);
        } elseif ($ll_source['type'] == 'http') {
            $xmlFile = hsHTTP($ll_source['path'].'?'.$querystring);
        } elseif ($ll_source['type'] == 'http-post') {
            $options = ['type'=>'http-post'];
            $xmlFile = hsHTTP($ll_source['path'].'?'.$querystring, $options);
        } elseif ($searchdata['is_sample'] == 'sample') {
            $xmlFile = '
<?xml version="1.0" encoding="utf-8"?>
<livelookup version="1.0" columns="customer_id,organization">
	<customer>
		<first_name>'.($fm['first_name'] ? $fm['first_name'] : 'Terry').'</first_name>
		<last_name>'.($fm['last_name'] ? $fm['last_name'] : 'Smith').'</last_name>
		<email>'.($fm['email'] ? $fm['email'] : 'fake@example.com').'</email>
		<phone>(555) 555-1212</phone>
		<customer_id>AB1234</customer_id>
		<department>Systems</department>
        <organization>Nike</organization>
		<links>
			<![CDATA[
			<a href="http://www.helpspot.com" target="_blank">Link to HR</a><br />
			<a href="http://www.helpspot.com" target="_blank">Link to eCommerce</a>
			]]>
		</links>
		<lookup_example>
			<![CDATA[
			<strong style="color:red;">'.lg_livelookup_livelookupnotsetup1.'</strong>
			<br /><br />
			'.lg_livelookup_livelookupnotsetup2.'
			]]>
		</lookup_example>
	</customer>
</livelookup>
			';
        } else {
            //something has gone wrong
            return liveLookupBuildSelector($fm['source_id'], false) .'<div class="table-no-results" style="margin-top:20px;">'.lg_livelookup_xmlerror.'</div>';
            // exit();
        }

        //parse xml and always use UTF-8.
        $xmlParser = xml_parser_create('UTF-8');
        $llParser = new livelookup('UTF-8');

        xml_set_object($xmlParser, $llParser);
        xml_set_element_handler($xmlParser, 'start_element', 'end_element');
        xml_set_character_data_handler($xmlParser, 'data');
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parse($xmlParser, trim($xmlFile));

        if ($output == 'lookup') {

            //handle errors
            if (xml_get_error_code($xmlParser)) {
                errorLog(xml_error_string(xml_get_error_code($xmlParser)), 'XML Parser', __FILE__, __LINE__);
                //Create error feedback
                $feedbackArea = '
					<ul>
						<li><span class="red">'.lg_livelookup_xmlerror.'</span></li>
						<li>'.lg_livelookup_xmlparsermsg.': '.xml_error_string(xml_get_error_code($xmlParser)).' ('.xml_get_error_code($xmlParser).')</li>
						<li>'.lg_livelookup_sourcepath.': '.$ll_source['path'].'</li>
						<li>'.lg_livelookup_sourcetype.': '.$ll_source['type'].'</li>
						<li><a href="" onclick="hs_overlay(\'livelookup_xml_error\',{title:\''.lg_livelookup_xmlview.'\'});return false;">'.lg_livelookup_xmlview.'</a></li>
					</ul>
					<pre id="livelookup_xml_error" style="display:none;overflow:auto;width:700px;height:500px;padding:5px;">'.hs_htmlspecialchars(trim($xmlFile)).'</pre>
				';
                $customers = [];
            } else {
                $customers = $llParser->getCustomers();
                $columns = $llParser->getColumns();
                $customlabels = $llParser->getLabels();

                //setup columns
                if (empty($columns)) {	//use defaults
                     $tablecolumns[] = ['type'=>'link', 'label'=>lg_request_custid, 'sort'=>0, 'code'=>'<a href="javascript:ll_popup(\'%s\');">%s</a>',
                                                'fields'=>'customer_id', 'width'=>80, 'linkfields'=>['ll_index', 'customer_id'], ];
                    $tablecolumns[] = ['type'=>'string', 'label'=>lg_request_fname, 'width'=>70, 'sort'=>0, 'fields'=>'first_name'];
                    $tablecolumns[] = ['type'=>'string', 'label'=>lg_request_lname, 'width'=>120, 'sort'=>0, 'fields'=>'last_name'];
                    $tablecolumns[] = ['type'=>'string', 'label'=>lg_request_email, 'sort'=>0, 'fields'=>'email'];
                //$tablecolumns[] = array('type'=>'string','label'=>lg_request_phone,'width'=>80,'sort'=>0,'fields'=>'phone');
                } else {
                    $tablecolumns[] = ['type'=>'link', 'label'=>(isset($customlabels['customer_id']) ? $customlabels['customer_id'] : lg_request_custid), 'sort'=>0, 'code'=>'<a href="javascript:ll_popup(\'%s\');">%s</a>',
                                                'fields'=>'customer_id', 'width'=>'100', 'linkfields'=>['ll_index', 'customer_id'], ];
                    //handle columns
                    $tempcols = explode(',', $columns);
                    $colcount = count($tempcols);
                    //$width	  = round((85/$colcount));
                    $width = '';
                    foreach ($tempcols as $colname) {
                        $colname = trim($colname);

                        if (isset($customlabels[$colname])) {
                            $label = $customlabels[$colname];
                        } elseif (utf8_strpos($colname, 'Custom') === false) {
                            $label = utf8_ucfirst(str_replace('_', ' ', $colname));
                        } else {
                            $label = utf8_ucfirst($GLOBALS['customFields'][utf8_substr($colname, 6)]['fieldName']);
                        }

                        if ($colname != 'customer_id') {
                            $tablecolumns[] = ['type'=>'html', 'label'=>$label, 'width'=>$width.'%', 'sort'=>0, 'fields'=>$colname];
                        }
                    }
                }
            }

            xml_parser_free($xmlParser);

            //build page - just one customer
            if (is_array($customers) && count($customers) == 1) {
                $results .= '<table style="margin-bottom:10px;" width="100%">';
                foreach ($customers[0] as $k=>$v) {
                    if (isset($customlabels[$k])) {
                        $label = $customlabels[$k];
                    } elseif (utf8_strpos($k, 'Custom') === false) {
                        $label = utf8_ucfirst(str_replace('_', ' ', $k));
                    } else {
                        $label = utf8_ucfirst($GLOBALS['customFields'][utf8_substr($k, 6)]['fieldName']);
                    }
                    $results .= '
                        <tr valign="top">
                            <td style="padding-top:8px;white-space: nowrap;padding-right: 15px;text-align: right;"><label class="datalabel">'.$label.'</label></td>
                            <td style="padding-top:8px;">'.cfDrillDownFormat($v).'</td>
                        </tr>';
                }
                $results .= '</table><br />';

                $insert_data = [];

                if (isset($customers[0]['customer_id'])) {
                    $insert_data['customer_id'] = $customers[0]['customer_id'];
                }
                if (isset($customers[0]['first_name'])) {
                    $insert_data['first_name'] = $customers[0]['first_name'];
                }
                if (isset($customers[0]['last_name'])) {
                    $insert_data['last_name'] = $customers[0]['last_name'];
                }
                if (isset($customers[0]['email'])) {
                    $insert_data['email'] = $customers[0]['email'];
                }
                if (isset($customers[0]['phone'])) {
                    $insert_data['phone'] = $customers[0]['phone'];
                }

                //pull in custom field information if available
                if (isset($GLOBALS['customFields'])) {
                    foreach ($GLOBALS['customFields'] as $k=>$v) {
                        if (isset($customers[0]['Custom'.$k])) {
                            $insert_data['Custom'.$k] = $customers[0]['Custom'.$k];
                        }

                        //Handle drill downs
                        if (isset($customers[0]['Custom'.$k]) && $GLOBALS['customFields'][$k]['fieldType'] == 'drilldown') {
                            $drill = explode('#-#', $customers[0]['Custom'.$k]);
                            for ($z = 0; $z < count($drill); $z++) {
                                $insert_data['Custom'.$k.'_'.($z + 1)] = $drill[$z];
                            }
                        }
                    }
                }

                $insert_string = 'var llresult=new Array();';
                foreach ($insert_data as $k=>$v) {
                    $insert_string .= "llresult['".hs_jshtmlentities($k)."']='".hs_jshtmlentities(utf8RawUrlEncode($v))."';";
                }

                $results = liveLookupBuildSelector($fm['source_id'], true, $insert_string) . $results;

            } elseif (is_array($customers) && count($customers) > 1) {
                /*
                foreach($customers AS $id=>$custarray){
                    foreach($custarray AS $k=>$v){
                        $results .= '<label class="datalabel">'.str_replace('_',' ',$k).'</label><br>'.$v.'<br><br>';
                    }
                }
                */
                //loop over customer array to build toggle data field
                $i = 0;
                foreach ($customers as $k=>$arr) {
                    $insert_data = [];

                    $customers[$i]['ll_index'] = $i;


                    if (isset($customers[$i]['customer_id'])) {
                        $insert_data['customer_id'] = $customers[$i]['customer_id'];
                    }
                    if (isset($customers[$i]['first_name'])) {
                        $insert_data['first_name'] = $customers[$i]['first_name'];
                    }
                    if (isset($customers[$i]['last_name'])) {
                        $insert_data['last_name'] = $customers[$i]['last_name'];
                    }
                    if (isset($customers[$i]['email'])) {
                        $insert_data['email'] = $customers[$i]['email'];
                    }
                    if (isset($customers[$i]['phone'])) {
                        $insert_data['phone'] = $customers[$i]['phone'];
                    }

                    //pull in custom field information if available
                    if (isset($GLOBALS['customFields'])) {
                        foreach ($GLOBALS['customFields'] as $k=>$v) {
                            if (isset($customers[$i]['Custom'.$k])) {
                                $insert_data['Custom'.$k] = $customers[$i]['Custom'.$k];
                            }

                            //Handle drill downs
                            if (isset($customers[$i]['Custom'.$k]) && $GLOBALS['customFields'][$k]['fieldType'] == 'drilldown') {
                                $drill = explode('#-#', $customers[$i]['Custom'.$k]);
                                for ($z = 0; $z < count($drill); $z++) {
                                    $insert_data['Custom'.$k.'_'.($z + 1)] = $drill[$z];
                                }
                            }
                        }
                    }

                    $insert_string = 'var llresult=new Array();';
                    foreach ($insert_data as $k=>$v) {
                        $insert_string .= "llresult['".hs_jshtmlentities($k)."']='".hs_jshtmlentities(utf8RawUrlEncode($v))."';";
                    }

                    $customers[$i]['hiddenData'] = '<a href="" onclick="ll_popup('.$i.');return false;" id="ll_popup_'.$i. '"><div class="ll_popup"></div></a><div style="display:none;padding:20px;" id="ll_popup_content_' . $i . '"><div>';

                    $customers[$i]['hiddenData'] .= '<div style="margin-bottom:16px;display:flex;justify-content:space-between;">
                                                        <div>
                                                            <button class="btn inline-action" onclick="ll_popup_move('.($i - 1).',\''.hs_jshtmlentities(lg_streamview_end).'\');">&laquo; '.lg_prev. '</button>
                                                            <button class="btn inline-action" onclick="ll_popup_move('.($i + 1).',\''.hs_jshtmlentities(lg_streamview_end).'\');" style="margin-right: 10px;">'.lg_next.' &raquo;</button>
                                                        </div>
                                                        <button type="button" class="btn inline-action accent" onClick="'.$insert_string.'insertCusData(llresult);">'.lg_livelookup_insertdata.'</div>
                                                     </div>';

                    //$customers[$i]['hiddenData'] .= displayContentBoxTop('','','','100%','box-no-top-margin','box-no-padding','',false);
                    $customers[$i]['hiddenData'] .= '<table class="">';
                    if (is_array($arr)) {
                        foreach ($arr as $k=>$v) {
                            if (isset($customlabels[$k])) {
                                $label = $customlabels[$k];
                            } elseif (utf8_strpos($k, 'Custom') === false) {
                                $label = utf8_ucfirst(str_replace('_', ' ', $k));
                            } else {
                                $label = utf8_ucfirst($GLOBALS['customFields'][utf8_substr($k, 6)]['fieldName']);
                            }
                            $customers[$i]['hiddenData'] .= '
                                <tr valign="top">
                                    <td style="padding-top:8px;white-space: nowrap;padding-right: 15px;text-align: right;"><label class="datalabel">'.$label.'</label></td>
                                    <td style="padding-top:8px;" style="padding-top:5px;">'.cfDrillDownFormat($v).'</td>
                                </tr>';
                        }
                    }
                    $customers[$i]['hiddenData'] .= '</table>';

                    $customers[$i]['hiddenData'] .= '</div>';

                    $i++;
                }

                //Reset array before creating recordset
                reset($customers);

                //Add in toggle column
                array_unshift($tablecolumns, ['type'=>'html', 'label'=>'', 'sort'=>0, 'fields'=>'hiddenData', 'width'=>16]);

                $data = new array2recordset;
                $data->init($customers);

                $results = liveLookupBuildSelector($fm['source_id'], false) . recordSetTable($data, $tablecolumns, ['width'=>'100%']);

            //$results = toggleTable($data,$tablecolumns,array('title'=>sprintf(lg_livelookup_table,count($customers)),'width'=>'100%','no_table_borders'=>true));
                /*
                $results = recordSetTable($data, $tablecolumns,
                                                //options
                                                array( 'title'	=>$boxtitle,
                                                       'width'	=>'100%',
                                                       'toggle' =>true));
                */
            } else {
                //nobody found
                if (empty($feedbackArea)) {
                    $results .= liveLookupBuildSelector($fm['source_id'], false) .'<div class="table-no-results" style="margin-top:20px;">'.lg_livelookup_notfound.'</div>';
                }
            }

            return $feedbackArea.$results;
        } elseif ($output == 'addressbook') {
            $customers = $llParser->getCustomers();

            //Put into the people array formate the address book needs
            $ppl = [];

            foreach ($customers as $k=>$person) {
                if ($person['email']) {
                    $highlight = ($person['highlight'] ? 'a' : 'b'); //for ordering
                    $xContact = count($ppl) + 1;
                    $key = $highlight.' '.trim(utf8_strtolower($person['last_name'])).' '.trim(utf8_strtolower($person['first_name'].' '.$xContact));
                    $ppl[$key]['xContact'] = count($ppl) + 1;
                    $ppl[$key]['sFirstName'] = trim($person['first_name'] ? $person['first_name'] : '');
                    $ppl[$key]['sLastName'] = trim($person['last_name'] ? $person['last_name'] : '');
                    $ppl[$key]['sEmail'] = trim($person['email'] ? $person['email'] : '');
                    $ppl[$key]['sTitle'] = trim($person['title'] ? $person['title'] : '');
                    $ppl[$key]['sDescription'] = trim($person['description'] ? $person['description'] : '');
                    $ppl[$key]['fHighlight'] = ($person['highlight'] ? $person['highlight'] : 0);
                }
            }

            //Set order
            ksort($ppl);

            //Reset array before creating recordset
            reset($ppl);

            //Make into RS
            $rs = new array2recordset($ppl);

            xml_parser_free($xmlParser);

            return $rs;
        } elseif ($output == 'raw') {
            $customers = $llParser->getCustomers();
            xml_parser_free($xmlParser);

            return $customers;
        }
    } else {
        $results .= '<p>'.lg_livelookup_noxml.'</p>';
    }
}

function liveLookupBuildSelector($source_id, $button=true, $insert_string=''){

    $livelookup_sources = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));
    $livelookupSelect = '';
    if (is_array($livelookup_sources) && ! empty($livelookup_sources)) {
        foreach ($livelookup_sources as $key=>$value) {
            if ($source_id == $key) {
                $livelookupSelect .= '<option value="'.$key.'" selected="selected">'.$value['name'].'</option>';
            } else {
                $livelookupSelect .= '<option value="'.$key.'">'.$value['name'].'</option>';
            }
        }
    }

    return '
        <div style="display:flex;margin: 8px 0;align-items:center;">
            <label class="datalabel">'.lg_request_searchtype.'</label>
            <select style="flex:1;margin: 0 8px;" id="live_lookup_search_source" onchange="doLiveLookup($F(this));" style="width="100%">
                '.$livelookupSelect.'
            </select>
            '.($button ? '<button type="button" class="btn inline-action accent" onClick="'.$insert_string.' insertCusData(llresult);">'.lg_livelookup_insertdata.'</div>' : '').'
        </div>
    ';
}
