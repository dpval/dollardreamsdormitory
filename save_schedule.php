<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

/* =========================
   CHECK REQUEST
========================= */

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $day = trim($_POST['day'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time_start = trim($_POST['time_start'] ?? '');
    $time_end = trim($_POST['time_end'] ?? '');

    /* =========================
       VALIDATE
    ========================= */

    if(
        empty($day) ||
        empty($date) ||
        empty($time_start) ||
        empty($time_end)
    ){
        die("Missing fields");
    }

    /* =========================
       PREPARE
    ========================= */

    $stmt = $conn->prepare("
        INSERT INTO viewing_schedule
        (
            day,
            date,
            time_start,
            time_end
        )
        VALUES (?, ?, ?, ?)
    ");

    /* CHECK PREPARE */

    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }

    /* =========================
       BIND
    ========================= */

    $stmt->bind_param(
        "ssss",
        $day,
        $date,
        $time_start,
        $time_end
    );

    /* =========================
       EXECUTE
    ========================= */

    if($stmt->execute()){

        header("Location: viewing_sched.php");
        exit;

    }else{

        die("Execute failed: " . $stmt->error);

    }

}else{

    echo "Invalid request";

}
?>