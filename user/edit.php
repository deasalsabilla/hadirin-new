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
$id_user = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_user === 0) {
    header("Location: index.php?error=ID user tidak valid");
    exit;
}

$conn = getConnection();

/* Ambil data user */
$stmt = $conn->prepare("SELECT id_user, username, nama, id_role FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Data user tidak ditemukan");
    exit;
}

$user = $result->fetch_assoc();

/* Ambil data role untuk dropdown */
$roles = $conn->query("SELECT id_role, nama_role FROM data_roles WHERE status='aktif'");

/* Proses update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $nama = sanitize($_POST['nama']);
    $id_role = (int) $_POST['id_role'];
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($id_role)) {
        $error = 'Username dan role wajib diisi!';
    } else {
        /* Cek username duplikat */
        $stmt = $conn->prepare(
            "SELECT id_user FROM users WHERE username = ? AND id_user != ?"
        );
        $stmt->bind_param("si", $username, $id_user);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            /* Update user */
            if (!empty($password)) {
                // update + password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users
                    SET username=?, password=?, nama=?, id_role=?
                    WHERE id_user=?
                ");
                $stmt->bind_param(
                    "sssii",
                    $username,
                    $password_hash,
                    $nama,
                    $id_role,
                    $id_user
                );
            } else {
                // update tanpa password
                $stmt = $conn->prepare("
                    UPDATE users
                    SET username=?, nama=?, id_role=?
                    WHERE id_user=?
                ");
                $stmt->bind_param(
                    "ssii",
                    $username,
                    $nama,
                    $id_role,
                    $id_user
                );
            }

            if ($stmt->execute()) {
                header("Location: index.php?success=edit");
                exit;
            } else {
                $error = 'Gagal memperbarui data user!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit user - Sistem Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
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
                <h1>Edit User</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Form Edit User</h2>
                        <a href="index.php" class="btn btn-secondary">← Kembali</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person-badge"></i> Username <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="username" name="username" class="form-control" required
                                    value="<?= htmlspecialchars($user['username']); ?>">
                                <div class="invalid-feedback">
                                    Username wajib diisi.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">
                                    <i class="bi bi-person"></i> Nama Lengkap
                                </label>
                                <input type="text" id="nama" name="nama" class="form-control"
                                    value="<?= htmlspecialchars($user['nama'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password Baru
                                </label>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Kosongkan jika tidak diubah">
                                <div class="form-text">
                                    Biarkan kosong jika tidak ingin mengubah password.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_role" class="form-label">
                                    <i class="bi bi-shield-lock-fill"></i> Role <span class="text-danger">*</span>
                                </label>
                                <select id="id_role" name="id_role" class="form-select" required>
                                    <option value="">Pilih Role</option>
                                    <?php while ($r = $roles->fetch_assoc()): ?>
                                        <option value="<?= $r['id_role']; ?>"
                                            <?= $user['id_role'] == $r['id_role'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($r['nama_role']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Role wajib dipilih.
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
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
    <script>
        // Bootstrap form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('form')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>

</html>
