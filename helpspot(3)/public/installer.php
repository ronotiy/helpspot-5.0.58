<?php

http_response_code(301);
// Redirect to /install route
header('Location: https://support.helpspot.com/index.php?pg=kb.page&id=501');
exit();
