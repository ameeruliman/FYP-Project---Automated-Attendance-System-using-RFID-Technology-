<?php
session_start();
$filename = 'latest_uid.txt';
if (file_exists($filename)) {
    echo file_get_contents($filename);
} else {
    echo '';
}
?>