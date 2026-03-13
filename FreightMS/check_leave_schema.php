<?php
require_once 'c:/xampp/htdocs/FMS/config/config.php';
$pdo = getDBConnection();
$stmt = $pdo->query("DESCRIBE leave_requests");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
