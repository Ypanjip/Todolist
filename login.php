<?php
session_start();
include "koneksi.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Aplikasi To-Do-List </title>
    <link rel="stylesheet" href="style/login.css">
</head>
<body>
    <?php
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = md5($_POST['password']); // Hash password yang diinput

        // Cek ke database dengan password yang sudah di-hash
        $query = mysqli_query($conn, "SELECT * FROM akun WHERE username='$username' AND password='$password'");

        if (mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_array($query);
            $_SESSION['user'] = $data;
            echo '<script>alert("Selamat anda berhasil login"); location.href="index.php";</script>';
        } else {
            echo '<script>alert("Username/password tidak sesuai");</script>';
        }
    }
    ?>
    <div class="center">
        <form method="post">
            <table>
                <tr>
                    <td colspan="2"></td>
                    <h2>Login</h2>
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
                        <div class="button-container">
                            <button type="submit">Login</button>
                            <a href="register.php">Register</a>
                        </div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>