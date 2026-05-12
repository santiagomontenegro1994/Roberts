<?php
session_start();
session_destroy(); // Mata la sesión de PHP
setcookie('token_jefes_roberts', '', time() - 3600, '/'); // Mata la sesión del celular
header('Location: login.php');
exit;
?>