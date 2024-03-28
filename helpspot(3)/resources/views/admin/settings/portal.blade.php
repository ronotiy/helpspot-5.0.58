<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_portal) }}">

	{!! renderPageheader(lg_admin_settings_portal) !!}

	<div class="card padded">

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_FORMFORMAT">{{ lg_admin_settings_formformat }}</label>
				<div class="info">{{ lg_admin_settings_formformatex }}</div>
			</div>
			<div class="control">
				<select name="cHD_PORTAL_FORMFORMAT">
					<option value="1" {{ selectionCheck('1', hs_setting('cHD_PORTAL_FORMFORMAT')) }}>{{ lg_admin_settings_detailedformat }}</option>
					<option value="0" {{ selectionCheck('0', hs_setting('cHD_PORTAL_FORMFORMAT')) }}>{{ lg_admin_settings_simpleformat }}</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox">
				<label for="cHD_PORTAL_AUTOREPLY">{{ lg_admin_settings_autoreply }}</label>
				<div class="info">{{ lg_admin_settings_autoreplyex }}</div>
			</div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_AUTOREPLY" id="cHD_PORTAL_AUTOREPLY" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_AUTOREPLY')) }}>
				<label for="cHD_PORTAL_AUTOREPLY" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox">
				<label for="cHD_PORTAL_REQUIRE_AUTH">{{ lg_admin_portals_require_auth }}</label>
				<div class="info">{{ lg_admin_portals_require_auth_ex }}</div>
			</div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_REQUIRE_AUTH" id="cHD_PORTAL_REQUIRE_AUTH" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_REQUIRE_AUTH')) }}>
				<label for="cHD_PORTAL_REQUIRE_AUTH" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_LOGIN_SEARCHONTYPE">{{ lg_admin_settings_portalsearchontype }}</label>
				<div class="info">{{ lg_admin_settings_portalsearchontypeex }}</div>
			</div>
			<div class="control">
				<select name="cHD_PORTAL_LOGIN_SEARCHONTYPE">
					<option value="1" {{ selectionCheck('1', hs_setting('cHD_PORTAL_LOGIN_SEARCHONTYPE')) }}>{{ lg_admin_settings_portalsearchontypeemail }}</option>
					<option value="2" {{ selectionCheck('2', hs_setting('cHD_PORTAL_LOGIN_SEARCHONTYPE')) }}>{{ lg_admin_settings_portalsearchontypeid }}</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_LOGIN_AUTHTYPE">{{ lg_admin_settings_portalauthtype }}</label>
				<div class="info">
					<p id="blackboxportal_note" style="display:none;font-weight:bold;">{{ lg_admin_settings_portalauthtypebbex }}</p>
					<p id="samlportal_note" style="display:none;font-weight:bold;">{{ lg_admin_settings_portalauthtypesamlex }}</p> {!! lg_admin_settings_portalauthtypeex !!}
				</div>
			</div>
			<div class="control">
				<select name="cHD_PORTAL_LOGIN_AUTHTYPE" id="cHD_PORTAL_LOGIN_AUTHTYPE" onchange="showPortalAuthNote();">
					<option value="internal" {{ selectionCheck('internal', hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE')) }}>{{ lg_admin_settings_portalauthtypeint }}</option>
					<option value="blackbox" {{ selectionCheck('blackbox', hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE')) }}>{{ lg_admin_settings_portalauthtypebb }}</option>
					@if( hs_setting('cAUTHTYPE', 'internal') == 'saml' )
					<option value="saml" {{ selectionCheck('saml', hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE')) }}>{{ lg_admin_settings_saml }}</option>
					@endif
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox"><label for="cHD_PORTAL_ALLOWUPLOADS">{{ lg_admin_settings_allowuploads }}</label></div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_ALLOWUPLOADS" id="cHD_PORTAL_ALLOWUPLOADS" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_ALLOWUPLOADS')) }}>
				<label for="cHD_PORTAL_ALLOWUPLOADS" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_EXCLUDEMIMETYPES">{{ lg_admin_settings_excludemimes }}</label>
				<div class="info">{{ lg_admin_settings_excludemimesex }}</div>
			</div>
			<div class="control">
				<input name="cHD_PORTAL_EXCLUDEMIMETYPES" id="cHD_PORTAL_EXCLUDEMIMETYPES" type="text" size="60" value="{{ formClean(hs_setting('cHD_PORTAL_EXCLUDEMIMETYPES')) }}">
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox"><label for="cHD_PORTAL_ALLOWCC">{{ lg_admin_settings_allow_cc }}</label></div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_ALLOWCC" id="cHD_PORTAL_ALLOWCC" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_ALLOWCC')) }}>
				<label for="cHD_PORTAL_ALLOWCC" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox"><label for="cHD_PORTAL_ALLOWSUBJECT">{{ lg_admin_settings_allow_subject }} </label></div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_ALLOWSUBJECT" id="cHD_PORTAL_ALLOWSUBJECT" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_ALLOWSUBJECT')) }}>
				<label for="cHD_PORTAL_ALLOWSUBJECT" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_PHONE">{{ lg_admin_settings_portalphone }}</label>
				<div class="info">{{ lg_admin_settings_portalphoneex }}</div>
			</div>
			<div class="control">
				<input name="cHD_PORTAL_PHONE" id="cHD_PORTAL_PHONE" type="text" size="50" value="{{ formClean(hs_setting('cHD_PORTAL_PHONE')) }}">
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label"><label for="cHD_PORTAL_MSG">{{ lg_admin_settings_portalmsg }}</label></div>
			<div class="control">
				<textarea name="cHD_PORTAL_MSG" rows="10">{!! formCleanHtml(hs_setting('cHD_PORTAL_MSG')) !!}</textarea>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_SPAM_LINK_CT">{{ lg_admin_settings_spamlinkct }}</label>
				<div class="info">{{ lg_admin_settings_spamlinkctdesc }}</div>
			</div>
			<div class="control">
				<input name="cHD_PORTAL_SPAM_LINK_CT" id="cHD_PORTAL_SPAM_LINK_CT" type="text" size="5" maxlength="10" value="{{ formClean(hs_setting('cHD_PORTAL_SPAM_LINK_CT')) }}">
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_SPAM_AUTODELETE">{{ lg_admin_settings_spamautodel }}</label>
				<div class="info">{{ lg_admin_settings_spamautodeldesc }}</div>
			</div>
			<div class="control">
				<input name="cHD_PORTAL_SPAM_AUTODELETE" id="cHD_PORTAL_SPAM_AUTODELETE" type="text" size="5" maxlength="10" value="{{ formClean(hs_setting('cHD_PORTAL_SPAM_AUTODELETE')) }}">
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox">
				<label for="cHD_PORTAL_SPAM_FORMVALID_ENABLED">{{ lg_admin_settings_spamtimeip }}</label>
				<div class="info">{{ lg_admin_settings_spamtimeipdesc }}</div>
			</div>
			<div class="control">
				<input type="checkbox" name="cHD_PORTAL_SPAM_FORMVALID_ENABLED" id="cHD_PORTAL_SPAM_FORMVALID_ENABLED" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_PORTAL_SPAM_FORMVALID_ENABLED')) }}>
				<label for="cHD_PORTAL_SPAM_FORMVALID_ENABLED" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_PORTAL_CAPTCHA">{{ lg_admin_settings_captcha }}</label>
				<div class="info">{{ lg_admin_settings_captchadesc }}</div>
			</div>
			<div class="control">
				<select name="cHD_PORTAL_CAPTCHA" id="cHD_PORTAL_CAPTCHA" onchange="captchaSwitch();">
					<option value="0" {{ selectionCheck('0', hs_setting('cHD_PORTAL_CAPTCHA')) }}>{{ lg_admin_settings_off }}</option>
					<option value="1" {{ selectionCheck('1', hs_setting('cHD_PORTAL_CAPTCHA')) }}>{{ lg_admin_settings_captchatext }}</option>
					<option value="2" {{ selectionCheck('2', hs_setting('cHD_PORTAL_CAPTCHA')) }}>{{ lg_admin_settings_captchare }}</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr" id="captcha_text_wrap">
			<div class="label">
				<label for="cHD_PORTAL_CAPTCHA_WORDS">{{ lg_admin_settings_captchawords }}</label>
				<div class="info">{{ lg_admin_settings_captchawordsdesc }}</div>
			</div>
			<div class="control">
				<textarea name="cHD_PORTAL_CAPTCHA_WORDS" rows="6">{{ formClean(hs_setting('cHD_PORTAL_CAPTCHA_WORDS')) }}</textarea>
			</div>
		</div>

		<div id="captcha_re_wrap">
			{!! displayContentBoxTop(lg_admin_settings_captchare, lg_admin_settings_captcharedesc) !!}
			<div class="fr">
				<div class="label"><label for="cHD_RECAPTCHA_PUBLICKEY">{{ lg_admin_settings_captcharepublic }}</label></div>
				<div class="control">
					<input name="cHD_RECAPTCHA_PUBLICKEY" id="cHD_RECAPTCHA_PUBLICKEY" type="text" size="50" value="{{ formClean(hs_setting('cHD_RECAPTCHA_PUBLICKEY')) }}">
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label"><label for="cHD_RECAPTCHA_PRIVATEKEY">{{ lg_admin_settings_captchareprivate }}</label></div>
				<div class="control">
					<input name="cHD_RECAPTCHA_PRIVATEKEY" id="cHD_RECAPTCHA_PRIVATEKEY" type="text" size="50" value="{{ formClean(cHD_RECAPTCHA_PRIVATEKEY) }}">
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label"><label for="cHD_RECAPTCHA_LANG">{{ lg_admin_settings_captcharelang }}</label></div>
				<div class="control">
					<select name="cHD_RECAPTCHA_LANG">
						<option value="en" {{ selectionCheck('en', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_en }}</option>
						<option value="nl" {{ selectionCheck('nl', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_nl }}</option>
						<option value="fr" {{ selectionCheck('fr', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_fr }}</option>
						<option value="de" {{ selectionCheck('de', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_de }}</option>
						<option value="pt" {{ selectionCheck('pt', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_pt }}</option>
						<option value="es" {{ selectionCheck('es', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_es }}</option>
						<option value="tr" {{ selectionCheck('tr', hs_setting('cHD_RECAPTCHA_LANG')) }}>{{ lg_admin_settings_captcharelang_tr }}</option>
					</select>
				</div>
			</div>
			{!! displayContentBoxBottom() !!}
		</div>

	</div>

	<div class="button-bar space">
		<button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
	</div>
</div>
