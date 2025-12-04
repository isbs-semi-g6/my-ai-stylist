<?php
require_once 'includes/auth.php';

logout();
header('Location: ./login.html');
exit;
?>