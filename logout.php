<?php
session_start();

// Check if this is the confirmation step (after user clicks "OK")
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    session_destroy();
    echo "<script>alert('Selamat anda berhasil logout'); location.href = 'login.php';</script>";
    exit();
}
?>
<script type="text/javascript">
    if (confirm('Apakah anda ingin logout?')) {
        // Redirect to the same page with a confirmation parameter
        location.href = "logout.php?confirm=yes";
    } else {
        location.href = "index.php";
    }
</script>