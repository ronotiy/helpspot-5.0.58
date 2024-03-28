<table cellpadding="0" cellspacing="0" border="0" class="tablebody" width="100%" id="rsgroup_1">
    <tr class="tabletop">
        <td class="tabletopcell" colspan="3">
            <table class="tabletop-inner">
                <tr>
                    <td>
                        <div style="position:relative;">
                            {{-- Bring this back when pagination exists --}}
                            {{-- <span class="count count-big">{{{ $number_results_displayed }}} of {{{ $number_results }}}</span> &nbsp; --}}
                            <span class="count count-big">{{{ $number_results_displayed }}}</span> &nbsp;
                            <span class="table-title">Search Results</span>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr class="tableheaders" valign="bottom">
        <td scope="col" id="1_table_header_title"></td>

        <td scope="col" id="1_table_header_date"></td>
    </tr>

    <!-- result -->
    @foreach( $results as $result )
    <tr class="tablerowoff">
        <td class="tcell" width=""><b><a href="?pg=request&amp;reqid={{{ $result['request']['id'] }}}">Request: {{{ $result['request']['id'] }}}
                    * {{{ $result['request']['status'] }}} * {{{ $result['request']['from'] }}}</a></b><br />
            {{{ $result['note'] }}}</td>

        <td class="tcell" width="180">{{{ $result['request']['date'] }}}</td>
    </tr>
    @endforeach
    <!-- end result -->

    <tr>
        <td class="tablefooter" colspan="3"></td>
    </tr>
</table>
<div style="display:none;" id="menubtn_tmpl">
    <ul class="tooltip-menu">
        <li><a href="" onclick="showOverflow('#{id}');return false;" title="" class=
            "tooltip-menu-img-base tooltip-menu-img-reqview"><span class="tooltip-menu-maintext">View Request</span></a></li>
    </ul>
</div>