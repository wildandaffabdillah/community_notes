<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['comment_id'])) {
    $comment_id = intval($_GET['comment_id']);
    
    // Delete replies associated with the comment and check for errors
    if (!$conn->query("DELETE FROM comment_replies WHERE comment_id = $comment_id")) {
        error_log("Error deleting replies: " . $conn->error);
    }

    // Now delete the comment and check for errors
    if (!$conn->query("DELETE FROM comments WHERE id = $comment_id")) {
        error_log("Error deleting comment: " . $conn->error);
    }

    
    header("Location: index.php");
    exit();
}
?>
