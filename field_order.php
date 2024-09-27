<?php
// field_order.php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['fieldOrder'])) {
        $_SESSION['fieldOrder'] = $data['fieldOrder'];
    }
}
?>