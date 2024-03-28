<table border="0" cellpadding="0" cellspacing="0" class="tablebody no_borders" id="rsgroup_1" width="100%">
    <tbody>
        <tr class="tabletop">
            <td class="tabletopcell" colspan="6">
                <table class="tabletop-inner">
                    <tbody>
                        <tr>
                            <td>
                                <div style="position:relative;">
                                    <span class="count count-big">{{{ $number_results }}}</span>
                                    &nbsp;<span class="table-title">{{{ lg_request_sidebar }}}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>

        <tr class="tableheaders" valign="bottom">
            <td id="1_table_header_xRequest" scope="col">{{{ lg_request_reqid }}}</td>

            <td id="1_table_header_fOpen" scope="col">{{{ lg_historysearch_openclose }}}</td>

            <td id="1_table_header_fullname" scope="col">
                <a class="tableheaderlink" href=
                "&amp;sortby=fullname&amp;sortord=DESC">{{{ lg_request_customer }}}</a>
            </td>

            <td id="1_table_header_dtGMTOpened" scope="col">{{{ lg_historysearch_date }}}</td>

            <td id="1_table_header_tNote" scope="col">{{{ lg_historysearch_tnote }}}</td>
        </tr>

        @foreach( $results as $result )
        <!-- result tablerowoff tablerowon -->
        <tr class="tablerowoff">
            <td class="tcell" width="80">
                <a href="admin?pg=request&amp;reqid={{{ $result['id'] }}}">{{{ $result['id'] }}}</a>&nbsp;<a href="admin?pg=request&amp;reqid={{{ $result['id'] }}}" target="_blank"><img border="0" src="{{ static_url() }}/static/img5/external.svg" style="margin-bottom:-2px;margin-left: 2px;height: 14px;"></a>
            </td>

            <td class="tcell" width="40">{{{ $result['status'] }}}</td>

            <td class="tcell" style="white-space: nowrap;" width="110">{{{ $result['from'] }}}</td>

            <td class="tcell" width="180">{{{ $result['date'] }}}</td>

            <td class="tcell" width="">
                <table class="hideflow-table hand">
                    <tbody>
                        <tr>
                            <td class="js-request" onclick="showOverflow({{{ $result['id'] }}});"><span class="initsubject">{{{ $result['title'] }}}</span> {{{ $result['note'] }}}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <!-- end result -->
        @endforeach

        <tr>
            <td class="tablefooter" colspan="6"></td>
        </tr>
    </tbody>
</table>
