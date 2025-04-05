<?php
session_start();
include 'koneksi.php'; // Pastikan koneksi ke database benar

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login.php';</script>";
    exit();
}

// Ambil ID pengguna yang sedang login
$user_id = $_SESSION['user']['id'];

// Cek apakah ada taskid atau subtaskid di URL
$taskid = isset($_GET['taskid']) ? $_GET['taskid'] : null;
$subtaskid = isset($_GET['subtaskid']) ? $_GET['subtaskid'] : null;

$editType = ''; // Menyimpan tipe yang diedit (task atau subtask)
$data = []; // Menyimpan data yang akan diedit

if ($taskid) {
    // Ambil data tugas berdasarkan taskid dan user_id
    $query = "SELECT * FROM tasks WHERE taskid = ? AND id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $taskid, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        die("Tugas tidak ditemukan atau bukan milik user ini!");
    }

    $data = mysqli_fetch_assoc($result);
    $editType = 'task';

    // Ambil semua subtasks terkait
    $subtask_query = "SELECT * FROM subtasks WHERE taskid = ?";
    $subtask_stmt = mysqli_prepare($conn, $subtask_query);
    mysqli_stmt_bind_param($subtask_stmt, "i", $taskid);
    mysqli_stmt_execute($subtask_stmt);
    $subtasks = mysqli_stmt_get_result($subtask_stmt);

} elseif ($subtaskid) {
    // Ambil data subtugas berdasarkan subtaskid dan user_id
    $query = "SELECT subtasks.* FROM subtasks 
              JOIN tasks ON subtasks.taskid = tasks.taskid 
              WHERE subtasks.subtaskid = ? AND tasks.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $subtaskid, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        die("Subtugas tidak ditemukan atau bukan milik user ini!");
    }

    $data = mysqli_fetch_assoc($result);
    $editType = 'subtask';
} else {
    die("ID tugas atau subtugas tidak ditemukan!");
}

// Proses update jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $label = mysqli_real_escape_string($conn, $_POST['label']);

    if ($editType === 'task') {
        $deadline = $_POST['deadline'];
        $update_query = "UPDATE tasks SET tasklabel = ?, deadline = ? WHERE taskid = ? AND id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssii", $label, $deadline, $taskid, $user_id);
        mysqli_stmt_execute($update_stmt);

        // Update sub-tugas yang sudah ada
        if (!empty($_POST['subtasks'])) {
            foreach ($_POST['subtasks'] as $subtask_id => $subtask_label) {
                $update_subtask_query = "UPDATE subtasks SET subtasklabel = ? WHERE subtaskid = ? AND taskid = ?";
                $update_subtask_stmt = mysqli_prepare($conn, $update_subtask_query);
                mysqli_stmt_bind_param($update_subtask_stmt, "sii", $subtask_label, $subtask_id, $taskid);
                mysqli_stmt_execute($update_subtask_stmt);
            }
        }

        // Tambahkan sub-tugas baru jika ada
        if (!empty($_POST['new_subtasks'])) {
            foreach ($_POST['new_subtasks'] as $new_subtask_label) {
                if (!empty(trim($new_subtask_label))) { // Pastikan sub-tugas tidak kosong
                    $insert_subtask_query = "INSERT INTO subtasks (taskid, subtasklabel) VALUES (?, ?)";
                    $insert_subtask_stmt = mysqli_prepare($conn, $insert_subtask_query);
                    mysqli_stmt_bind_param($insert_subtask_stmt, "is", $taskid, $new_subtask_label);
                    mysqli_stmt_execute($insert_subtask_stmt);
                }
            }
        }

    } elseif ($editType === 'subtask') {
        $update_query = "UPDATE subtasks 
                         JOIN tasks ON subtasks.taskid = tasks.taskid
                         SET subtasks.subtasklabel = ? 
                         WHERE subtasks.subtaskid = ? AND tasks.id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sii", $label, $subtaskid, $user_id);
        mysqli_stmt_execute($update_stmt);
    }

    echo "<script>alert('Data berhasil diperbarui!'); window.location.href='index.php';</script>";
}

// Tentukan tanggal besok untuk atribut min
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= $editType === 'task' ? 'Tugas' : 'Subtugas' ?></title>
    <link rel="stylesheet" href="style/edit.css">
</head>
<body>
    <div class="container">
        <h2>Edit <?= $editType === 'task' ? 'Tugas' : 'Subtugas' ?></h2>

        <form action="" method="post">
            <label for="label"><?= $editType === 'task' ? 'Nama Tugas' : 'Nama Subtugas' ?>:</label>
            <input type="text" name="label" id="label" value="<?= htmlspecialchars($data[$editType === 'task' ? 'tasklabel' : 'subtasklabel']) ?>" required>

            <?php if ($editType === 'task'): ?>
                <label for="deadline">Deadline:</label>
                <input type="date" name="deadline" id="deadline" value="<?= $data['deadline'] ?>" min="<?= $tomorrow ?>" required>

                <h3>Subtugas:</h3>
                <div id="subtask-list">
                    <?php while ($subtask = mysqli_fetch_assoc($subtasks)): ?>
                        <div class="subtask-container">
                            <input type="text" name="subtasks[<?= $subtask['subtaskid'] ?>]" value="<?= htmlspecialchars($subtask['subtasklabel']) ?>" required>
                        </div>
                    <?php endwhile; ?>
                </div>

                <button type="button" class="add-subtask-btn" onclick="addSubtask()">+ Tambah Subtugas</button>
            <?php endif; ?>

            <div class="button-container">
                <button type="submit" class="save-btn">Simpan</button>
                <a href="index.php" class="back-btn">Kembali</a>
            </div>
        </form>
    </div>

    <script>
        let subtaskCounter = 0;

        function addSubtask() {
            const subtaskList = document.getElementById('subtask-list');
            const newSubtaskDiv = document.createElement('div');
            newSubtaskDiv.className = 'subtask-container';

            const newSubtaskInput = document.createElement('input');
            newSubtaskInput.type = 'text';
            newSubtaskInput.name = 'new_subtasks[]';
            newSubtaskInput.placeholder = 'Masukkan sub-tugas baru';
            newSubtaskInput.required = true;

            newSubtaskDiv.appendChild(newSubtaskInput);
            subtaskList.appendChild(newSubtaskDiv);
            subtaskCounter++;
        }
    </script>
</body>
</html>