<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['note_id'])) {
        $note_id = intval($_POST['note_id']);
        $comment = $conn->real_escape_string($_POST['comment']);
        if (!$conn->query("INSERT INTO comments (note_id, username, comment) VALUES ('$note_id', '$username', '$comment')")) {
            $_SESSION['error'] = "Failed to add comment.";
            header("Location: index.php");
            exit();
        }
    } elseif (isset($_POST['comment_id'])) {
        $comment_id = intval($_POST['comment_id']);
        $reply = $conn->real_escape_string($_POST['reply']);
        if (!$conn->query("INSERT INTO comment_replies (comment_id, username, reply) VALUES ('$comment_id', '$username', '$reply')")) {
            $_SESSION['error'] = "Failed to add reply.";
            header("Location: index.php");
            exit();
        }
    } elseif (isset($_POST['edit_comment_id'])) {
        $edit_comment_id = intval($_POST['edit_comment_id']);
        $new_comment = $conn->real_escape_string($_POST['new_comment']);
        if (!$conn->query("UPDATE comments SET comment='$new_comment' WHERE id='$edit_comment_id'")) {
            $_SESSION['error'] = "Failed to update comment.";
            header("Location: index.php");
            exit();
        }
    }
}

header("Location: index.php");
exit();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Comments</h2>
        <!-- Existing comments will be displayed here -->
        <form action="" method="POST">
            <div class="mb-3">
                <label>Edit Comment:</label>
                <input type="hidden" name="edit_comment_id" value="<!-- Comment ID here -->">
                <input type="text" name="new_comment" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Comment</button>
        </form>
    </div>
</body>
</html>
