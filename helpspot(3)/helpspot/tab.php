<?php
function widget_clean($string)
{
    return nl2br(hs_htmlspecialchars(strip_tags($string)));
}

//Handle POST's to the server with the question. Sanity checks done in client, not on server end so just insert data as request
if (! hs_setting('cHD_MAINTENANCE_MODE') && hs_setting('cHD_WIDGET_TAB_ACTIVE') && isset($_POST['tNote'])) {
    $data = [];

    //Set name values
    $name = parseName($_POST['sName']);
    $data['sFirstName'] = strip_tags($name['fname']);
    $data['sLastName'] = strip_tags($name['lname']);

    //Set email
    $data['sEmail'] = strip_tags($_POST['sEmail']);

    //Set note
    $data['tBody'] = widget_clean($_POST['tNote']);

    //Add meta info to note
    if (! empty($_POST['widget_page_url'])) {
        $data['tBody'] = $data['tBody'].'<br /><br />'.lg_widget_opened_from.':<br />'.widget_clean($_POST['widget_page_url']);
    }

    //Settings
    $data['mode'] = 'add';
    $data['fOpenedVia'] = 13;
    $data['fPublic'] = 1;
    $data['fNoteIsHTML'] = 1;
    $data['dtGMTOpened'] = date('U');

    //Add
    $reqResult = apiAddEditRequest($data, 1, __FILE__, __LINE__);

    if ($reqResult) {
        //Return OK
        return response();
    } else {
        //Return Error
        return abort(400);
    }
} elseif (! hs_setting('cHD_WIDGET_TAB_ACTIVE') && isset($_POST['tNote'])) {
    //Return Error
    return abort(400);
}
ob_start();
?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script type="text/javascript" src="<?php echo static_url() ?>/static/js/jquery.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                //Show powered by HS logo if other than IE6
                if($.browser.msie && $.browser.version < 7){
                    $('#poweredbyhelpspot_link').html('Powered by HelpSpot');
                    $('#poweredbyhelpspot_link').css({
                        color: '#4c4c4c',
                        'font-weight': 'bold',
                        'font-family': 'arial'
                    });
                }else{
                    $('#poweredbyhelpspot').show();
                }

                //Set focus to note box. Need extra time as ready seems to sometimes fire too early for focus.
                setTimeout(function(){$('#tNote').focus()},500);

                //Capture form submit
                $("#tab-form").submit(function(){
                    //Do validation
                    if($("#tNote").val() == ""){
                        alert("<?php echo widget_clean($_GET['text_note_er']) ?>");return false;
                    }else if($("#sName") && $("#sName").val() == ""){
                        alert("<?php echo widget_clean($_GET['text_name_er']) ?>");return false;
                    }else if($("#sEmail").val() == ""){
                        alert("<?php echo widget_clean($_GET['text_email_er']) ?>");return false;
                    }

                    //Post form
                    $.ajax({
                        type: "POST",
                        url: "tab.php",
                        cache: false,
                        data: $("#tab-form").serialize(),
                        success: function(){
                            $('#tab-form').hide();
                            $('#form-submit-success').fadeIn(1000);
                        },
                        error: function(){
                            $('#tab-form').hide();
                            $('#form-submit-error').fadeIn(1000);
                        }
                    });

                    return false;
                });
            });
        </script>

        <style type="text/css">
            body{
                font-family: "Lucida Grande", "Lucida Sans Unicode", sans-serif;
                font-size: 12px;
                margin: 0px;
            }

            .textfield{
                font-family: "Lucida Grande", "Lucida Sans Unicode", sans-serif;
                border:1px solid #A8A8A8;
                width: 97%;
                font-size: 14px;
                padding: 5px;
            }

            textarea{
                height: 125px;
            }

            .submit{
                width: 100%;
                font-size: 14px;
            }

            h1{
                margin-top: 0px;
                margin-bottom: 0px;
                font-size: 23px;
                font-weight: normal;
            }

            #wrapper{
                padding: 10px 20px;
                background-color: <?php echo ! empty($_GET['popup_background_color']) ? widget_clean($_GET['popup_background_color']) : 'transparent' ?>;
                border-width: <?php echo widget_clean($_GET['popup_border_size']) ?>;
                border-color: <?php echo widget_clean($_GET['popup_border_color']) ?>;
                border-style: solid;
            }

            .description{
                margin:0px;
                margin-top: 4px;
            }

            .special{
                padding: 5px;
                background-color: #90AA4A;
                color: #fff;
            }

            label{
                display: block;
                font-weight: bold;
                margin-bottom: 3px;
            }

            #poweredbyhelpspot_link{
                cursor: pointer;
            }

            /*
            .captcha_text{
                color: red;
            }
            */

            #submit_msg{
                font-size: 26px;
                padding: 10px;
                text-align: center;
            }

            #form-submit-error{
                padding: 10px;
            }

            #error_msg{
                font-size: 26px;
                color: red;
                text-align: center;
            }

            .error_link{
                text-align: center;
            }

            <?php
            //include the override CSS file if it is there
            $widgetTabCssFile = customCodePath('widget_tab.css');
            if (file_exists($widgetTabCssFile)) {
                include $widgetTabCssFile;
            }
            ?>
        </style>
    </head>
    <body>
    <div id="wrapper">
        <form name="tab-form" id="tab-form" method="post">
            <h1><?php echo widget_clean($_GET['text_header']) ?></h1>

            <?php if (! empty($_GET['text_intro'])): ?><p class="description"><?php echo widget_clean($_GET['text_intro']) ?></p><?php endif; ?>

            <?php if (! empty($_GET['text_special'])): ?><p class="special"><?php echo widget_clean($_GET['text_special']) ?></p><?php endif; ?>

            <p><label for="tNote"><?php echo widget_clean($_GET['text_note']) ?></label>
                <textarea id="tNote" name="tNote" class="textfield"><?php echo widget_clean($_GET['default_note']) ?></textarea>
            </p>

            <?php if (isset($_GET['use_field_name'])): ?>
                <p><label for="sName"><?php echo widget_clean($_GET['text_name']) ?></label>
                    <input type="text" value="<?php echo widget_clean($_GET['default_name']) ?>" name="sName" id="sName" class="textfield" />
                </p>
            <?php endif; ?>

            <p><label for="sEmail"><?php echo widget_clean($_GET['text_email']) ?></label>
                <input type="text" value="<?php echo widget_clean($_GET['default_email']) ?>" name="sEmail" id="sEmail" class="textfield" />
            </p>

            <?php
            /* hide this for now, can add it in later if needed
            <?php if(isset($_GET['field_captcha'])): ?>
                <?php if(cHD_RECAPTCHA_PUBLICKEY != '' && cHD_RECAPTCHA_PRIVATEKEY != ''): ?>

                    <?php
                    include_once(cBASEPATH.'/helpspot/lib/recaptcha/recaptchalib.php');

                    //Determine if we're using ssl
                    $ssl = substr(cHOST,0,5) == 'https' ? true : false;

                    //Find errors if any
                    $error = isset($GLOBALS['errors']['recaptcha']) ? $GLOBALS['errors']['recaptcha'] : null;
                    ?>

                    <script>
                    var RecaptchaOptions = {
                        theme : '<?php echo cHD_RECAPTCHA_THEME; ?>',
                        lang : '<?php echo cHD_RECAPTCHA_LANG; ?>'
                    };
                    </script>

                    <p><label for="recaptcha_response_field"><?php echo widget_clean($_GET['text_captcha']) ?> - <a href="javascript:Recaptcha.reload();"><?php echo widget_clean($_GET['text_captcha_change']) ?></a></label>
                        <?php echo recaptcha_get_html(cHD_RECAPTCHA_PUBLICKEY,$error,$ssl); ?>
                    </p>

                <?php else: ?>

                    <?php
                    //Save previous word from session for use in checking
                    $_SESSION['widget_captcha_lastword'] = $_SESSION['widget_captcha'];
                    //Get captcha words
                    $words = explode("\n",cHD_PORTAL_CAPTCHA_WORDS);
                    //Set word
                    $rand = array_rand($words,1);
                    $_SESSION['widget_captcha'] = trim($words[$rand]);
                    ?>

                    <p><label for="captcha"><?php echo widget_clean($_GET['text_captcha']) ?> - <b class="captcha_text"><?php echo $_SESSION['widget_captcha'] ?></b></label>
                        <input type="text" name="captcha" class="textfield" value="" />
                    </p>

                <?php endif; ?>
            <?php endif; ?>
            */ ?>

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr valign="middle">
                    <td>
                        <a href="https://www.helpspot.com" id="poweredbyhelpspot_link" target="_blank"><img src="images/poweredbyhelpspot.png" id="poweredbyhelpspot" style="display:none;" width="83" height="40" border="0" /></a>
                    </td>
                    <td width="50%">
                        <input type="submit" name="submit" class="submit" value="<?php echo widget_clean($_GET['text_submit']) ?>" />
                    </td>
                </tr>
            </table>

            <input type="hidden" name="widget_page_url" value="<?php echo widget_clean($_SERVER['HTTP_REFERER']) ?>" />
        </form>


        <div id="form-submit-success" style="display:none;">
            <div id="submit_msg"><?php echo widget_clean($_GET['text_msg_submit']) ?></div>
        </div>

        <div id="form-submit-error" style="display:none;">
            <div id="error_msg"><?php echo widget_clean($_GET['text_msg_submit_error']) ?></div>
            <?php if (! empty($_GET['text_msg_submit_error_url'])): ?><div class="error_link"><a href="<?php echo widget_clean($_GET['text_msg_submit_error_url']) ?>" target="_blank"><?php echo widget_clean($_GET['text_msg_submit_error_link']) ?></a></div><?php endif; ?>
        </div>
    </div>
    </body>
    </html>
<?php return ob_get_clean();
