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
if(isset($_POST['add'])){
    $task = $_POST['task'];
    $deadline = $_POST['deadline']; 
    $today = date('Y-m-d');

    if ($deadline < $today) {
        echo "<script>alert('Tanggal deadline tidak boleh di masa lalu!'); window.location.href='index.php';</script>";
        exit();
    }
    $q_insert = "INSERT INTO tasks (id, tasklabel, taskstatus, deadline) VALUES ('$id', '$task', 'open', '$deadline')";
    $run_q_insert = mysqli_query($conn, $q_insert);
    
    if($run_q_insert){
        $taskid = mysqli_insert_id($conn);
        
        if (!empty($_POST['subtasks'])) {
            foreach ($_POST['subtasks'] as $subtask) {
                if (!empty($subtask)) {
                    $q_insert_sub = "INSERT INTO subtasks (taskid, subtasklabel, subtaskstatus) VALUES ('$taskid', '$subtask', 'open')";
                    mysqli_query($conn, $q_insert_sub);
                }
            }
        }
        header('Refresh:0; url=index.php');
    }
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

// Penyortiran berdasarkan deadline
$order_by = "ORDER BY deadline ASC"; // Default sorting: Terdekat
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'latest') {
        $order_by = "ORDER BY deadline DESC"; // Deadline Terjauh
    } elseif ($_GET['sort'] == 'soonest') {
        $order_by = "ORDER BY deadline ASC"; // Deadline Terdekat
    }
}

// Pencarian tugas
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $q_select = "SELECT * FROM tasks WHERE id = '$id' AND tasklabel LIKE '%$search%' $order_by";
} else {
    $q_select = "SELECT * FROM tasks WHERE id = '$id' $order_by";
}

$run_q_select = mysqli_query($conn, $q_select);
if (!$run_q_select) {
    die("Error Fetching Tasks: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="style/style.css">
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

            <?php while ($r = mysqli_fetch_assoc($run_q_select)): ?>
                <div class="task">
                    <input type="checkbox" onclick="window.location.href = '?done=<?= $r['taskid'] ?>&status=<?= $r['taskstatus'] ?>'" <?= $r['taskstatus'] == 'close' ? 'checked disabled' : '' ?>>
                    <span><?= htmlspecialchars($r['tasklabel']) ?></span>
                    <small>Deadline: <?= date("d M Y", strtotime($r['deadline'])) ?></small>

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
</body>

</html>
