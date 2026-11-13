<?php

include "db.php";

if(isset($_POST['id'])){

    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM viewing_schedule WHERE id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){

        echo "success";

    } else {

        echo "failed";

    }

} else {

    echo "invalid";

}
?>