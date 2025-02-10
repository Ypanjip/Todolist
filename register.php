<?php
session_start();
include "koneksi.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Aplikasi To-Do-List</title>
    <link rel="stylesheet" href="style/register.css">
</head>

<body>
    <?php
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = md5($_POST['password']); // Hash password dengan MD5

        $query = mysqli_query($conn, "INSERT INTO akun (username, password) VALUES ('$username', '$password')");

        if ($query) {
            echo '<script>alert("Selamat, akun berhasil didaftarkan! Silakan login."); location.href="login.php";</script>';
        } else {
            echo '<script>alert("Pendaftaran gagal, coba lagi!");</script>';
        }
    }
    ?>
    <div class="center">
        <form method="post">
            <table>
                <tr>
                    <td colspan="2"><h2>Register</h2></td>
                </tr>
                <tr>
                    <td>Username</td>
                    <td><input type="text" name="username" required></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td><input type="password" name="password" required></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="button-group">
                            <button type="submit">Register</button>
                            <a href="login.php" class="login-link">Login</a>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>

</html>
