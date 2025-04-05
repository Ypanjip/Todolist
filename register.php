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
    <script>
        function validateForm() {
            const username = document.forms["registerForm"]["username"].value;
            const password = document.forms["registerForm"]["password"].value;

            // Validasi username: hanya huruf dan angka
            const usernamePattern = /^[a-zA-Z0-9]+$/;
            if (!usernamePattern.test(username)) {
                alert("Username hanya boleh mengandung huruf dan angka.");
                return false;
            }

            // Validasi password: minimal 8 karakter
            if (password.length < 8) {
                alert("Password harus minimal 8 karakter.");
                return false;
            }

            return true;
        }
    </script>
</head>

<body>
    <?php
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = md5($_POST['password']); // Hash password dengan MD5

        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT * FROM akun WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<script>alert("Username sudah terdaftar, silakan gunakan username lain.");</script>';
        } else {
            // Menggunakan prepared statement untuk mencegah SQL injection
            $stmt = $conn->prepare("INSERT INTO akun (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);

            if ($stmt->execute()) {
                echo '<script>alert("Selamat, akun berhasil didaftarkan! Silakan login."); location.href="login.php";</script>';
            } else {
                echo '<script>alert("Pendaftaran gagal, coba lagi!");</script>';
            }
        }

        $stmt->close();
    }
    ?>
    <div class="center">
        <form name="registerForm" method="post" onsubmit="return validateForm()">
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