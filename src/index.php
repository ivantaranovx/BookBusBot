<?php
include "check_routes.php";
?><!DOCTYPE html>
<html>
    <head>
        <title>title</title>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <script src='https://telegram.org/js/telegram-web-app.js'></script>
        <script><?php
readfile("res/app.min.js");
?>

    var STR_PROCESS = "<?= STR_PROCESS ?>";
    var STR_DONE = "<?= STR_DONE ?>";
    var STR_BUSY = "<?= STR_BUSY ?>";
        </script><style><?php
readfile("res/app.min.css");
?></style>
    </head>
    <body></body>
</html>

