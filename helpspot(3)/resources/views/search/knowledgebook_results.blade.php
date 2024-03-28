<table cellpadding="0" cellspacing="0" border="0" class="tablebody" width="100%" id=
"rsgroup_1">
    <tr class="tabletop">
        <td class="tabletopcell" colspan="3">
            <table class="tabletop-inner">
                <tr>
                    <td>
                        <div style="position:relative;">
                            {{-- Bring this back when pagination exists --}}
                            {{-- <span class="count count-big">{{{ $number_results_displayed }}} of {{{ $number_results }}}</span> &nbsp; --}}
                            <span class="count count-big">{{{ $number_results_displayed }}}</span> &nbsp;
                            <span class="table-title">{{ lg_search_result }}</span>
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
        <td class="tcell" width=""><b><a href="?pg=kb.page&page={{{ $result['id'] }}}">{{{ $result['book'] }}} ~ {{{ $result['name'] }}}</a></b><br />
            {{{ $result['content'] }}}...</td>

        <td class="tcell" width="180"></td>
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
            "tooltip-menu-img-base tooltip-menu-img-reqview"><span class="tooltip-menu-maintext">View Page</span></a></li>
    </ul>
</div>