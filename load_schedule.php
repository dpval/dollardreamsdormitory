<?php
include "db.php";

$result = $conn->query("SELECT * FROM viewing_schedule ORDER BY date ASC");

$data = [];

while($row = $result->fetch_assoc()){
  $data[] = $row;
}

echo json_encode($data);
?>