@foreach($request->history as $history)
    @if(($excludeCurrentNote && $loop->first) || $history->fPublic != 1) @continue @endif
    <div style="font-weight:bold;border-bottom:2px solid black;margin-bottom:2px;" class="full_history_name">{{  hs_htmlspecialchars($history->fromName($request)) }} <span style="color:#7F7F7F;" class="full_history_date">- {{ hs_showDate($history->dtGMTChange) }}</span></div>
    <div class="full_history_note">{{ prepareEmailHistoryMessage('html', $history->tNote, $history->fNoteIsHTML) }}</div>
    <br /><br />
@endforeach
