<?php
$host = "localhost";
$user = "root"; // Ganti jika ada username lain
$password = ""; // Ganti jika ada password database
$database = "todolist"; // Ganti dengan nama database Anda

$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>
