<?php
$conn = new mysqli("localhost","root","","sewa_in");
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}
?> 