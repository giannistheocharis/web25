<?php
session_start();
session_unset();         // καθαρίζει όλα τα session values
session_destroy();       // κλείνει το session

header("Location: ../frontend/login.html"); // επιστροφή στην σελίδα login
exit();
?>
