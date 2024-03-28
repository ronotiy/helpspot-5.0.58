# Workspace

## Domain Events

The Request and History items are a core area of HelpSpot. The following events are fired:

##### `request.history.create`
Fired (queued) when `apiAddRequestHistory()` is called. Note that because this is called within transactions in several
areas, it's queued and then flushed after the transaction is complete. This is only flushed if a note is added, not if a
log entry is added.

This has the side-effect of needing to be flushed in several areas:

* api.requests.lib.php
* class.api.public.php
* class.auto.rule.php
* class.mail.rule.php
* class.triggers.php
* logic.php

