<div class="settings-box bizhours" id="box_id_{{  md5(lg_admin_settings_bizhours)  }}">

    {!! renderPageheader(lg_admin_settings_bizhours) !!}

    <div class="card padded">

        <label for="cHD_BUSINESS_HOURS"></label> <!-- here for scrollto from search -->
        <table width="100%">
            <tr>
                <td></td>
                <th>{{ lg_admin_settings_bizhours_nohours }}</th>
                <th>{{ lg_admin_settings_bizhours_start }}</th>
                <th>{{ lg_admin_settings_bizhours_end }}</th>
            </tr>

            @foreach ([1=>'monday', 2=>'tuesday', 3=>'wednesday', 4=>'thursday', 5=>'friday', 6=>'saturday', 0=>'sunday'] as $k=>$v)
            <tr>
                <td width="300"><label>{{ constant("lg_admin_settings_$v") }}</label></td>
                <td align="center" width="100"><input value="1" type="checkbox" name="bh_{{ $v }}_nohours" id="bh_{{ $v }}_nohours" onclick="bizhours_nohours();" {{ checkboxCheck(1, ($bh->bizhours[$k] === false)) }} /></td>
                <td align="center"><select name="bh_{{ $v }}_start" id="bh_{{ $v }}_start">{!! hs_ShowBizHours($bh->bizhours[$k]['start']) !!}</select></td>
                <td align="center"><select name="bh_{{ $v }}_end" id="bh_{{ $v }}_end">{!! hs_ShowBizHours($bh->bizhours[$k]['end']) !!}</select></td>
            </tr>
            @endforeach
        </table>


        {!! displayContentBoxTop(lg_admin_settings_bizhours_holidays_label, lg_admin_settings_bizhours_holidays_desc) !!}

        <table width="100%">
            <tr>
                <th width="300">{{ lg_admin_settings_bizhours_date }}</th>
                <th width="100">{{ lg_admin_settings_bizhours_nohours }}</th>
                <th>{{ lg_admin_settings_bizhours_start }}</th>
                <th>{{ lg_admin_settings_bizhours_end }}</th>
                <th width="16"></th>
            </tr>
            <tr>
                <td>
                    <select name="bh_newholiday_iMonth" id="bh_newholiday_iMonth">{!! hs_ShowMonth('') !!}</select>&middot;
                    <select name="bh_newholiday_iDay" id="bh_newholiday_iDay">{!! hs_ShowDay() !!}</select>&middot;
                    <select name="bh_newholiday_iYear" id="bh_newholiday_iYear">{!! hs_ShowYear(false, (date('Y') - date('Y', $GLOBALS['license']['CreatedOn']))) !!}</select>
                </td>
                <td align="center"><input value="1" type="checkbox" name="bh_newholiday_nohours" id="bh_newholiday_nohours" onclick="bizhours_nohours();" /></td>
                <td align="center"><select name="bh_newholiday_start" id="bh_newholiday_start">{!! hs_ShowBizHours(9) !!}</select></td>
                <td align="center"><select name="bh_newholiday_end" id="bh_newholiday_end">{!! hs_ShowBizHours(17) !!}</select></td>
                <td><a href="" onclick="bh_holiday(true);return false;"><img src="{{ static_url() }}/static/img5/add-circle.svg" class="hand svg28" border="0" /></td>
            </tr>
        </table>

        <div id="bh_holidays"></div>

        <script type="text/javascript">
            function bh_delete(date) {
                //Loading
                $("bh_holidays").update("{{ lg_loading }}");

                //Delete Holidays
                new Ajax.Request(
                    "{!! route('admin', ['pg' => 'ajax_gateway', 'action' => 'bizhours_delete_holiday']) !!}", {
                        method: "post",
                        parameters: {
                            bh_date: date
                        },
                        onComplete: function() {
                            bh_holiday()
                        }
                    });
            }

            function bh_holiday(create) {
                //Loading
                $("bh_holidays").update("{{ lg_loading }}");

                //Create/Get Holidays
                new Ajax.Request(
                    "{!! route('admin', ['pg' => 'ajax_gateway', 'action' => 'bizhours_create_holiday']) !!}", {
                        method: "post",
                        parameters: {
                            bh_newholiday_iMonth: $F("bh_newholiday_iMonth"),
                            bh_newholiday_iDay: $F("bh_newholiday_iDay"),
                            bh_newholiday_iYear: $F("bh_newholiday_iYear"),
                            bh_newholiday_nohours: $F("bh_newholiday_nohours"),
                            bh_newholiday_start: $F("bh_newholiday_start"),
                            bh_newholiday_end: $F("bh_newholiday_end"),
                            bh_create: (create ? 1 : 0)
                        },
                        onComplete: function() {
                            $("bh_holidays").update(arguments[0].responseText)
                        }
                    });
            }
            bh_holiday();
        </script>

        {!! displayContentBoxBottom() !!}

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
    </div>
</div>
