<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    $username = $_SESSION['username'];
    $target_dir = __DIR__ . "/uploads/profile_pictures/"; // Gunakan __DIR__ untuk memastikan path benar

    // Cek apakah folder ada, jika tidak buat foldernya
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = $username . "_" . time() . "_" . basename($_FILES["profile_picture"]["name"]);
    $target_file = $target_dir . $file_name;

    // Debugging: Cek apakah folder bisa ditulis
    if (!is_writable($target_dir)) {
        die("Error: Folder tidak bisa ditulis. Cek izin folder!");
    }

    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
        $relative_path = "uploads/profile_pictures/" . $file_name; // Simpan path relatif
        $conn->query("UPDATE users SET profile_picture = '$relative_path' WHERE username = '$username'");
        header("Location: index.php");
        exit();
    } else {
        echo "Upload failed. Error: " . $_FILES["profile_picture"]["error"];
    }
}
?>
