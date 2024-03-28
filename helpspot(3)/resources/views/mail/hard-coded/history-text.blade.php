@foreach($request->history as $history)
    @if(($excludeCurrentNote && $loop->first) || $history->fPublic != 1) @continue @endif
    {!! prepareEmailHistoryMessage('text', $history->fromName($request).' ('.hs_showDate($history->dtGMTChange).")\n-----------------------------\n".$history->tNote."\n\n", $history->fNoteIsHTML) !!}
@endforeach
