<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

$id_role = $_SESSION['id_role'];

$stmtMenu = $conn->prepare("
    SELECT dm.nama_menu, dm.icon_menu, dm.link_menu
    FROM data_menu dm
    JOIN hak_akses ha ON dm.id_menu = ha.id_menu
    WHERE ha.id_role = ?
    AND ha.can_view = 1
    AND dm.status = 'aktif'
    ORDER BY dm.urutan ASC
");

$stmtMenu->bind_param("i", $id_role);
$stmtMenu->execute();
$resultMenu = $stmtMenu->get_result();

$error = '';
$id_role = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_role === 0) {
    header("Location: index.php?error=ID role tidak valid");
    exit;
}

$conn = getConnection();

/* Ambil data role */
$stmt = $conn->prepare("SELECT * FROM data_roles WHERE id_role = ?");
$stmt->bind_param("i", $id_role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Data role tidak ditemukan");
    exit;
}

$role = $result->fetch_assoc();

/* Proses update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_role  = sanitize($_POST['nama_role']);
    $keterangan = sanitize($_POST['keterangan']);
    $status     = sanitize($_POST['status']);

    if (empty($nama_role)) {
        $error = 'Nama role wajib diisi!';
    } else {
        /* Cek duplikasi nama role */
        $stmt = $conn->prepare(
            "SELECT id_role FROM data_roles WHERE nama_role = ? AND id_role != ?"
        );
        $stmt->bind_param("si", $nama_role, $id_role);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = 'Nama role sudah digunakan!';
        } else {
            /* Update role */
            $stmt = $conn->prepare("
                UPDATE data_roles
                SET nama_role = ?, keterangan = ?, status = ?
                WHERE id_role = ?
            ");
            $stmt->bind_param(
                "sssi",
                $nama_role,
                $keterangan,
                $status,
                $id_role
            );

            if ($stmt->execute()) {
                header("Location: index.php?success=edit");
                exit;
            } else {
                $error = 'Gagal memperbarui data role!';
            }
        }
    }
}

$stmt->close();
closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit roles - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>
    <!-- Mobile Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="bi bi-building"></i> Dandang Cap Gajah</h2>
            </div>
            <nav class="sidebar-menu">
                <?php
                if ($resultMenu && $resultMenu->num_rows > 0) {
                    while ($menu = $resultMenu->fetch_assoc()) {
                        $activeClass = (basename($_SERVER['PHP_SELF']) == basename($menu['link_menu'])) ? 'active' : '';
                        $logoutClass = ($menu['link_menu'] == 'logout.php') ? 'logout' : '';
                        echo '<a href="../' . htmlspecialchars($menu['link_menu']) . '" class="menu-item ' . $activeClass . ' ' . $logoutClass . '">';
                        echo '<i class="' . htmlspecialchars($menu['icon_menu']) . '"></i> ' . htmlspecialchars($menu['nama_menu']);
                        echo '</a>';
                    }
                } else {
                    // Fallback jika tidak ada data menu
                    echo '<a href="../dashboard.php" class="menu-item active">';
                    echo '<i class="bi bi-speedometer2"></i> Dashboard';
                    echo '</a>';
                }
                ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Edit Roles</h1>
                <div class="user-info">
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Form Edit Roles</h2>
                        <a href="index.php" class="btn btn-secondary">← Kembali</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="nama_role">
                                    <i class="bi bi-person-badge"></i> Nama Role <span class="required">*</span>
                                </label>
                                <input type="text" id="nama_role" name="nama_role" class="form-control"
                                    required value="<?= htmlspecialchars($role['nama_role']); ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="keterangan">
                                    <i class="bi bi-card-text"></i> Keterangan
                                </label>
                                <textarea id="keterangan" name="keterangan" class="form-control"
                                    rows="3"><?= htmlspecialchars($role['keterangan']); ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="status">
                                    <i class="bi bi-toggle-on"></i> Status
                                </label>
                                <select name="status" id="status" class="form-control">
                                    <option value="aktif" <?= $role['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="nonaktif" <?= $role['status'] === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>

                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>
