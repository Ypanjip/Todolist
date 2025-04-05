<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='login.php';</script>";
    exit();
}

include 'koneksi.php';

$id = $_SESSION['user']['id'] ?? null;
if (!$id) {
    echo "<script>alert('User tidak ditemukan! Silakan login ulang.'); window.location.href='login.php';</script>";
    exit();
}

// Tambah tugas utama
if (isset($_POST['add'])) {
    $task = mysqli_real_escape_string($conn, $_POST['task']);
    $deadline = $_POST['deadline'];
    $today = date('Y-m-d');
    $user_id = $_SESSION['user']['id'];

    if ($deadline < $today) {
        echo "<script>alert('Tanggal deadline tidak boleh di masa lalu!'); window.location.href='index.php';</script>";
        exit();
    }

    $q_insert = "INSERT INTO tasks (id, tasklabel, taskstatus, deadline) 
                 VALUES ('$user_id', '$task', 'open', '$deadline')";
    $run_q_insert = mysqli_query($conn, $q_insert);

    if (!$run_q_insert) {
        die("Error Insert Task: " . mysqli_error($conn));
    }

    $taskid = mysqli_insert_id($conn);

    if (!empty($_POST['subtasks'])) {
        foreach ($_POST['subtasks'] as $subtask) {
            if (!empty($subtask)) {
                $q_insert_sub = "INSERT INTO subtasks (taskid, subtasklabel, subtaskstatus) 
                                 VALUES ('$taskid', '$subtask', 'open')";
                mysqli_query($conn, $q_insert_sub);
            }
        }
    }
    header('Location: index.php');
    exit();
}

// Tandai tugas selesai/tidak selesai
if (isset($_GET['done'])) {
    $taskid = $_GET['done'];
    $status = $_GET['status'] == 'open' ? 'close' : 'open';
    mysqli_query($conn, "UPDATE tasks SET taskstatus = '$status' WHERE taskid = '$taskid' AND id = '$id'");
    header('Location: index.php');
    exit();
}

// Tandai subtasks selesai/tidak selesai
if (isset($_GET['done_sub'])) {
    $subtaskid = $_GET['done_sub'];
    $status = $_GET['status'] == 'open' ? 'close' : 'open';
    mysqli_query($conn, "UPDATE subtasks SET subtaskstatus = '$status' WHERE subtaskid = '$subtaskid'");
    header('Location: index.php');
    exit();
}

// Hapus tugas dan subtasks terkait
if (isset($_GET['delete'])) {
    $taskid = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM subtasks WHERE taskid = '$taskid'");
    mysqli_query($conn, "DELETE FROM tasks WHERE taskid = '$taskid' AND id = '$id'");
    header('Location: index.php');
    exit();
}

// Hapus subtasks
if (isset($_GET['delete_sub'])) {
    $subtaskid = $_GET['delete_sub'];
    mysqli_query($conn, "DELETE FROM subtasks WHERE subtaskid = '$subtaskid'");
    header('Location: index.php');
    exit();
}

// Penyortiran berdasarkan deadline dan status terlambat
$order_by = "ORDER BY 
    CASE 
        WHEN deadline < CURDATE() AND taskstatus = 'open' THEN 1 
        WHEN taskstatus = 'close' THEN 2 
        ELSE 0 
    END ASC, 
    deadline ASC";

if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'latest') {
        $order_by = "ORDER BY 
            CASE 
                WHEN deadline < CURDATE() AND taskstatus = 'open' THEN 1 
                WHEN taskstatus = 'close' THEN 2 
                ELSE 0 
            END ASC, 
            deadline DESC";
    } elseif ($_GET['sort'] == 'soonest') {
        $order_by = "ORDER BY 
            CASE 
                WHEN deadline < CURDATE() AND taskstatus = 'open' THEN 1 
                WHEN taskstatus = 'close' THEN 2 
                ELSE 0 
            END ASC, 
            deadline ASC";
    }
}

$status_filter = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'open') {
        $status_filter = "AND taskstatus = 'open'";
    } elseif ($_GET['status'] == 'close') {
        $status_filter = "AND taskstatus = 'close'";
    } elseif ($_GET['status'] == 'late') {
        $today = date('Y-m-d');
        $status_filter = "AND deadline < '$today' AND taskstatus = 'open'";
    }
}

// Pencarian tugas
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $q_select = "SELECT * FROM tasks WHERE id = '$id' AND tasklabel LIKE '%$search%' $status_filter $order_by";
} else {
    $q_select = "SELECT * FROM tasks WHERE id = '$id' $status_filter $order_by";
}

$run_q_select = mysqli_query($conn, $q_select);
if (!$run_q_select) {
    die("Error Fetching Tasks: " . mysqli_error($conn));
}

$today = date('Y-m-d');

// Ambil tugas yang mendekati deadline (1 hari sebelum deadline)
$q_notif = "SELECT tasklabel, deadline FROM tasks 
            WHERE id = '$id' 
            AND deadline <= DATE_ADD('$today', INTERVAL 1 DAY) 
            AND taskstatus = 'open' 
            AND deadline >= '$today'";
$run_q_notif = mysqli_query($conn, $q_notif);

$notifications = [];
while ($notif = mysqli_fetch_assoc($run_q_notif)) {
    $notifications[] = "<li>" . htmlspecialchars($notif['tasklabel']) . " - Deadline: " . date("d M Y", strtotime($notif['deadline'])) . "</li>";
}

// Otomatis mencentang tugas yang terlambat
$q_late_tasks = "SELECT taskid FROM tasks WHERE id = '$id' AND deadline < '$today' AND taskstatus = 'open'";
$run_q_late_tasks = mysqli_query($conn, $q_late_tasks);
while ($late_task = mysqli_fetch_assoc($run_q_late_tasks)) {
    mysqli_query($conn, "UPDATE tasks SET taskstatus = 'close' WHERE taskid = '{$late_task['taskid']}'");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="style/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
        function addSubtaskField() {
            let container = document.getElementById("subtasks-container");
            let input = document.createElement("input");
            input.type = "text";
            input.name = "subtasks[]";
            input.placeholder = "Subtugas...";
            container.appendChild(input);
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>To-Do List</h2>
            <div>Halo, <?= htmlspecialchars($_SESSION['user']['username']); ?>!</div>
            <a href="logout.php">Logout</a>
            <?php if (!empty($notifications)): ?>
                <div class="notification-box">
                    <span class="notif-icon">🔔</span>
                    <strong>Tugas yang akan segera deadline:</strong>
                    <ul>
                        <?= implode("", $notifications); ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="content">
            <form action="" method="post">
                <input type="text" name="task" placeholder="Tambahkan tugas..." required>
                <input type="date" name="deadline" required>
                <div id="subtasks-container">
                    <input type="text" name="subtasks[]" placeholder="Subtugas...">
                </div>
                <button type="button" onclick="addSubtaskField()">+ Subtugas</button>
                <button type="submit" name="add">Tambah</button>
            </form>

            <form method="GET">
                <input type="text" name="search" placeholder="Cari tugas..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari</button>
            </form>

            <!-- Dropdown Penyortiran -->
            <form method="GET">
                <label for="sort">Urutkan:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="soonest" <?= isset($_GET['sort']) && $_GET['sort'] == 'soonest' ? 'selected' : '' ?>>Deadline Terdekat</option>
                    <option value="latest" <?= isset($_GET['sort']) && $_GET['sort'] == 'latest' ? 'selected' : '' ?>>Deadline Terjauh</option>
                </select>
            </form>
            <form method="GET">
                <label for="status">Filter Tugas:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="open" <?= isset($_GET['status']) && $_GET['status'] == 'open' ? 'selected' : '' ?>>Aktif</option>
                    <option value="close" <?= isset($_GET['status']) && $_GET['status'] == 'close' ? 'selected' : '' ?>>Selesai</option>
                    <option value="late" <?= isset($_GET['status']) && $_GET['status'] == 'late' ? 'selected' : '' ?>>Terlambat</option>
                </select>
            </form>

            <!-- Bagian tugas tanpa batasan tinggi -->
            <div class="task-list">
                <?php while ($r = mysqli_fetch_assoc($run_q_select)): ?>
                    <?php
                    $status = '';
                    $today = date('Y-m-d');
                    if ($r['taskstatus'] == 'close') {
                        $status = 'Selesai';
                    } elseif ($r['deadline'] < $today && $r['taskstatus'] == 'open') {
                        $status = 'Terlambat';
                    } else {
                        $status = 'Aktif';
                    }
                    ?>
                    <div class="task">
                        <input type="checkbox" onclick="window.location.href = '?done=<?= $r['taskid'] ?>&status=<?= $r['taskstatus'] ?>'" <?= $r['taskstatus'] == 'close' ? 'checked disabled' : '' ?>>
                        <span><?= htmlspecialchars($r['tasklabel']) ?></span>
                        <small>Deadline: <?= date("d M Y", strtotime($r['deadline'])) ?></small>
                        <small>Status: <?= $status ?></small>

                        <div class="task-actions">
                            <a href="edit.php?taskid=<?= $r['taskid'] ?>" class="btn-edit">Edit</a>
                            <a href="?delete=<?= $r['taskid'] ?>" class="btn-delete" onclick="return confirm('Hapus tugas ini?')">Hapus</a>
                        </div>

                        <div class="subtasks">
                            <?php
                            $taskid = $r['taskid'];
                            $q_subtasks = "SELECT * FROM subtasks WHERE taskid = '$taskid'";
                            $run_subtasks = mysqli_query($conn, $q_subtasks);

                            while ($sub = mysqli_fetch_assoc($run_subtasks)): ?>
                                <div class="subtask">
                                    <input type="checkbox" onclick="window.location.href = '?done_sub=<?= $sub['subtaskid'] ?>&status=<?= $sub['subtaskstatus'] ?>'" <?= $sub['subtaskstatus'] == 'close' ? 'checked disabled' : '' ?>>
                                    <span><?= htmlspecialchars($sub['subtasklabel']) ?></span>
                                    <a href="?delete_sub=<?= $sub['subtaskid'] ?>" onclick="return confirm('Hapus subtugas ini?')">Hapus</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>

</html>