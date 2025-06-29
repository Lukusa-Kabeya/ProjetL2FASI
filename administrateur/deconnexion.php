<?php
session_start();
session_destroy();
header("Location: connexionadmin.php");
exit;
