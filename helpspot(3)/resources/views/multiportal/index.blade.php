<table cellpadding="0" cellspacing="0" border="0" class="tablebody " width="100%" id="rsgroup_1">
    <tbody>
        <tr class="tabletop">
            <td class="" colspan="5">
                <table class="tabletop-inner">
                    <tbody>
                        <tr>
                            <td>
                                <div style="position:relative;"><span class="page-header-title">{{ lg_admin_portals_title }}</span></div>
                            </td>
                            <td align="right">
                                @if (request('showdeleted'))
                                    <a href="{{ route('admin', ['pg' => 'admin.tools.portals']) }}" class="">{{ lg_admin_portals_noshowdel }}</a>
                                @else
                                    <a href="{{ route('admin', ['pg' => 'admin.tools.portals', 'showdeleted' => 1]) }}" class="">{{ lg_admin_portals_showdel }}</a>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        @if (count($portals) == 0)
            <tr><td colspan="5" class="table-no-results">{{ lg_noresults }}</td></tr>
        @else
            <tr class="tableheaders" valign="bottom">
                <td scope="col">{{ lg_admin_portals_colid }}</td>
                <td scope="col">{{ lg_admin_portals_colname }}</td>
                <td scope="col">{{ lg_admin_portals_colhost }}</td>
                <td scope="col">{{ lg_admin_portals_colprimary }}</td>
                <td scope="col"></td>
            </tr>
            @foreach ($portals as $portal)
            <tr class="{{ ($loop->index % 2) ? 'tablerowon' : 'tablerowoff' }}">
                <td class="tcell" width="20">{{ $portal->xPortal }}</td>
                <td class="tcell" width="300">
                    <a href="{{ route('admin', ['pg' => 'admin.tools.portals', 'xPortal' => $portal->xPortal, 'showdeleted' => 0]) }}">{{ $portal->sPortalName }}</a>
                </td>
                <td class="tcell" width="">{{ $portal->sPortalName }} (<a href="{{ $portal->sHost }}" target="_blank">{{ lg_admin_portals_visit }}</a>)</td>
                <td class="tcell" width="">{{ $portal->fIsPrimaryPortal ? lg_yes : '' }}</td>
                <td class="tcell" width="150">
                    <a href="{{ route('admin', ['pg' => 'admin.tools.portals', 'xPortal' => $portal->xPortal, 'instructions' => 'true']) }}">{{ lg_admin_portals_viewinstructions }}</a>
                </td>
            </tr>
            @endforeach
        @endif
        <tr>
            <td class="tablefooter" colspan="5"></td>
        </tr>
    </tbody>
</table>
