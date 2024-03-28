<div class="page-header">
    <div class="page-header-title">{{ lg_admin_portals }}</div>
</div>

<table class="tablebody" width="100%">
    <tr class="tableheaders" valign="bottom">
        <td scope="col" id="">{{ lg_admin_portals_colid }}</td>
        <td scope="col" id="">{{ lg_admin_portals_colname }}</td>
        <td scope="col" id="">{{ lg_admin_portals_colhost }}</td>
    </tr>
    <tr>
        <td class="tcell">-</td>
        <td class="tcell">
            <a href="{{ cHOST }}" target="_blank" rel="noopener">{{ lg_primaryportal }}</a>
        </td>
        <td class="tcell">
            <a href="{{ cHOST }}" target="_blank" rel="noopener">{{ cHOST }}</a>
        </td>
    </tr>
    @foreach ($portals as $portal)
        <tr class="{{ ($loop->index % 2) ? 'tablerowoff' : 'tablerowon' }}">
            <td class="tcell">{{ $portal->xPortal }}</td>
            <td class="tcell" width="300">
                <a href="{{ $portal->sHost }}" target="_blank" rel="noopener">{{ $portal->sPortalName }}</a>
            </td>
            <td class="tcell" width="">
                <a href="{{ $portal->sHost }}" target="_blank" rel="noopener">{{ $portal->sHost }}</a>
            </td>
        </tr>
    @endforeach
    <tr>
        <td class="tablefooter" colspan="5"></td>
    </tr>
</table>
