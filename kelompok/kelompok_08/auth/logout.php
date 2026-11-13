<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/style.css">
<?php

session_start();

$_SESSION = [];

session_destroy();

header("Location: login.php");
exit;

?>
