<?php
session_start();
include 'config.php';

// Proteksi agar hanya user yang login bisa mengakses
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Hapus catatan
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Pertama, hapus komentar yang disembunyikan terkait dengan catatan ini
    $conn->query("DELETE FROM hide_comments WHERE note_id = $delete_id");
    
    // Sekarang hapus catatan
    $conn->query("DELETE FROM notes WHERE id = $delete_id");
    
    header("Location: index.php");
    exit();
}

// Tambahkan catatan baru dengan video
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['note'])) {
    $username = $_SESSION['username'];
    $note = $conn->real_escape_string($_POST['note']);
    
    // Proses Upload Video
    $video_path = NULL;
    if (!empty($_FILES['video']['name'])) {
        $target_dir = "uploads/";
        $video_name = basename($_FILES["video"]["name"]);
        $video_path = $target_dir . time() . "_" . $video_name;
        move_uploaded_file($_FILES["video"]["tmp_name"], $video_path);
    }

    $conn->query("INSERT INTO notes (username, note, video_path) VALUES ('$username', '$note', '$video_path')");
    header("Location: index.php");
    exit();
}

// Menyembunyikan komentar
if (isset($_GET['hide_comment_id'])) {
    $note_id = intval($_GET['hide_comment_id']);
    $username = $_SESSION['username'];

    // Memindahkan komentar ke tabel hide_comments
    $conn->query("INSERT INTO hide_comments (note_id, username) VALUES ($note_id, '$username')");
    header("Location: index.php");
    exit();
}

// Menampilkan kembali komentar
if (isset($_GET['unhide_comment_id'])) {
    $note_id = intval($_GET['unhide_comment_id']);
    $username = $_SESSION['username'];

    // Menghapus komentar dari tabel hide_comments
    $conn->query("DELETE FROM hide_comments WHERE note_id = $note_id AND username = '$username'");
    header("Location: index.php");
    exit();
}

// Tambahkan balasan ke database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply'])) {
    $username = $_SESSION['username'];
    $note_id = intval($_POST['note_id']);
    $reply = $conn->real_escape_string($_POST['reply']);

    $conn->query("INSERT INTO replies (note_id, username, reply) VALUES ($note_id, '$username', '$reply')");
    header("Location: index.php");
    exit();
}

// Hapus balasan
if (isset($_GET['delete_reply_id'])) {
    $reply_id = intval($_GET['delete_reply_id']);
    $username = $_SESSION['username'];

    // Hapus likes terkait balasan sebelum menghapus balasan
    $conn->query("DELETE FROM reply_likes WHERE reply_id = $reply_id");
    // Hapus balasan hanya jika user yang menghapus adalah pembuat balasan
    $conn->query("DELETE FROM replies WHERE id = $reply_id AND username = '$username'");

    header("Location: index.php");
    exit();
}

if (isset($_POST['edit_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    $new_comment = $conn->real_escape_string($_POST['new_comment']);
    
    if (!$conn->query("UPDATE comments SET comment_text = '$new_comment' WHERE id = $comment_id")) {
        $_SESSION['error'] = "Failed to update comment.";
    }
    header("Location: index.php");
    exit();
}


// Like/Dislike Note
if (isset($_GET['like_id'])) {
    $note_id = intval($_GET['like_id']);
    $username = $_SESSION['username'];
    $conn->query("INSERT INTO likes_dislikes (note_id, username, type) VALUES ($note_id, '$username', 'like') ON DUPLICATE KEY UPDATE type='like'");
    header("Location: index.php");
    exit();
}

if (isset($_GET['dislike_id'])) {
    $note_id = intval($_GET['dislike_id']);
    $username = $_SESSION['username'];
    $conn->query("INSERT INTO likes_dislikes (note_id, username, type) VALUES ($note_id, '$username', 'dislike') ON DUPLICATE KEY UPDATE type='dislike'");
    header("Location: index.php");
    exit();
}

// Like/Unlike Reply
if (isset($_GET['like_reply_id'])) {
    $reply_id = intval($_GET['like_reply_id']);
    $username = $_SESSION['username'];
    $conn->query("INSERT INTO reply_likes (reply_id, username, type) VALUES ($reply_id, '$username', 'like') ON DUPLICATE KEY UPDATE type='like'");
    header("Location: index.php");
    exit();
}

if (isset($_GET['unlike_reply_id'])) {
    $reply_id = intval($_GET['unlike_reply_id']);
    $username = $_SESSION['username'];
    $conn->query("DELETE FROM reply_likes WHERE reply_id = $reply_id AND username = '$username'");
    header("Location: index.php");
    exit();
}

// Ambil catatan yang tidak disembunyikan oleh user yang sedang login
$result = $conn->query("
    SELECT n.*, u.username FROM notes n
    JOIN users u ON n.username = u.username
    WHERE n.id NOT IN (
        SELECT note_id FROM hide_comments WHERE username = '{$_SESSION['username']}'
    ) 
    ORDER BY n.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Notes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function toggleReplyForm(noteId) {
            var replyForm = document.getElementById("reply-form-" + noteId);
            replyForm.style.display = replyForm.style.display === "none" ? "block" : "none";
        }

        function toggleHideComments() {
            var hideCommentsSection = document.getElementById("hide-comments-section");
            hideCommentsSection.style.display = hideCommentsSection.style.display === "none" ? "block" : "none";
        }
    </script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center">Community Notes</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>

        <div class="card shadow-sm p-4 mb-4">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Notes:</label>
                    <textarea name="note" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Video:</label>
                    <input type="file" name="video" class="form-control" accept="video/*">
                </div>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>

        <div class="card shadow-sm p-4">
            <h4>Shared Notes</h4>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="alert alert-secondary">
                    <strong><?= htmlspecialchars($row['username']); ?>:</strong>
                    <p><?= nl2br(htmlspecialchars($row['note'])); ?></p>
                    <small class="text-muted"><?= $row['created_at']; ?></small>

                    <?php if ($row['video_path']): ?>
                        <video width="320" height="240" controls>
                            <source src="<?= htmlspecialchars($row['video_path']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>

                    <!-- Edit Comment Button -->
                    <button onclick="document.getElementById('edit-form-<?= $row['id']; ?>').style.display='block'" class="btn btn-warning btn-sm">Edit</button>
                    <div id="edit-form-<?= $row['id']; ?>" style="display:none;">
                        <form action="" method="POST">
                            <input type="hidden" name="comment_id" value="<?= $row['id']; ?>">
                            <textarea name="new_comment" class="form-control" rows="2" required><?= htmlspecialchars($row['note']); ?></textarea>
                            <button type="submit" name="edit_comment" class="btn btn-success btn-sm">Update</button>
                        </form>
                    </div>

                    <!-- Tombol Like/Dislike -->
                    <?php
                    $likes = $conn->query("SELECT COUNT(*) as count FROM likes_dislikes WHERE note_id = {$row['id']} AND type = 'like'")->fetch_assoc()['count'];
                    $dislikes = $conn->query("SELECT COUNT(*) as count FROM likes_dislikes WHERE note_id = {$row['id']} AND type = 'dislike'")->fetch_assoc()['count'];
                    ?>
                    <button onclick="location.href='?like_id=<?= $row['id']; ?>'" class="btn btn-light btn-sm">👍 <?= $likes; ?></button>
                    <button onclick="location.href='?dislike_id=<?= $row['id']; ?>'" class="btn btn-light btn-sm">👎 <?= $dislikes; ?></button>

                    <!-- Tombol Hide Comment -->
                    <a href="?hide_comment_id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Hide Comment</a>
                    <a href="delete_comment.php?comment_id=<?= $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    <button class="btn btn-primary btn-sm" onclick="toggleReplyForm(<?= $row['id']; ?>)">Reply</button>

                    <!-- Form balasan -->
                    <div id="reply-form-<?= $row['id']; ?>" style="display: none; margin-top: 10px;">
                        <form action="" method="POST">
                            <input type="hidden" name="note_id" value="<?= $row['id']; ?>">
                            <div class="mb-2">
                                <textarea name="reply" class="form-control" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">Send Reply</button>
                        </form>
                    </div>

                    <!-- Tampilkan balasan -->
                    <?php
                    $replies = $conn->query("SELECT * FROM replies WHERE note_id = {$row['id']} ORDER BY created_at ASC");
                    while ($reply = $replies->fetch_assoc()):
                        $reply_likes = $conn->query("SELECT COUNT(*) as count FROM reply_likes WHERE reply_id = {$reply['id']} AND type = 'like'")->fetch_assoc()['count'];
                    ?>
                        <div class="ms-4 mt-2 p-2 border rounded bg-white">
                            <strong><?= htmlspecialchars($reply['username']); ?>:</strong>
                            <p><?= nl2br(htmlspecialchars($reply['reply'])); ?></p>
                            <small class="text-muted"><?= $reply['created_at']; ?></small>

                            <!-- Tombol Like/Unlike Reply -->
                            <button onclick="location.href='?like_reply_id=<?= $reply['id']; ?>'" class="btn btn-light btn-sm">👍 <?= $reply_likes; ?></button>
                            <button onclick="location.href='?unlike_reply_id=<?= $reply['id']; ?>'" class="btn btn-light btn-sm">👎</button>

                            <!-- Tombol Delete Reply -->
                            <?php if ($reply['username'] === $_SESSION['username']): ?>
                                <a href="?delete_reply_id=<?= $reply['id']; ?>" class="btn btn-danger btn-sm">Delete Reply</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endwhile; ?>

            <!-- Tombol untuk menampilkan Hide Comments -->
            <button class="btn btn-info" onclick="toggleHideComments()">Show/Hide Hide Comments</button>

            <!-- Menampilkan komentar yang disembunyikan -->
            <div id="hide-comments-section" style="display: none; margin-top: 20px;">
                <h4>Hide Comments</h4>
                <?php
                $hidden_comments = $conn->query("SELECT * FROM hide_comments WHERE username = '{$_SESSION['username']}' ORDER BY hide_at DESC");
                while ($hidden_comment = $hidden_comments->fetch_assoc()):
                ?>
                    <div class="alert alert-warning">
                        <strong>Hidden Comment:</strong>
                        <?php
                        // Menampilkan note yang disembunyikan
                        $note_result = $conn->query("SELECT * FROM notes WHERE id = {$hidden_comment['note_id']}");
                        $note = $note_result->fetch_assoc();
                        ?>
                        <p><?= nl2br(htmlspecialchars($note['note'])); ?></p>
                        <small class="text-muted"><?= $hidden_comment['hide_at']; ?></small>
                        <a href="?unhide_comment_id=<?= $hidden_comment['note_id']; ?>" class="btn btn-success btn-sm">Unhide Comment</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
