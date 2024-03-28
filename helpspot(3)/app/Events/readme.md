# Events

> Note: Illuminate\Events\EventServiceProvider used here rather than HelpSpot's own SP.

This currently uses `Illuminate\Events` without any HelpSpot application wrapper. A wrapper *may* eventually be added as
an interface between HelpSpot specific needs and the dependendy of Illuminate, as needed. For now, that'd just be over-
engineering.

To search for uses of the events library in HelpSpot code:

    $ cd ./helpspot    # the application library directory

    # Or `ack-grep` in Ubuntu
    $ ack --php "make\('events'\)"

    # Or to get only file names, use the `-l` flag
    ack -l --php "make\('events'\)"
