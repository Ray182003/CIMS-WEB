<?php
require_once("../config/db.php");

// Redirect to print request form instead of printing directly
$certificateId = intval($_GET['id'] ?? 0);
$redirectUrl = "/cims-app/print_request_form.php?id={$certificateId}&type=confirmation";
header("Location: " . $redirectUrl);
exit();
?>
