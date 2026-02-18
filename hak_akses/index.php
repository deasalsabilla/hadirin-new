<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");


// Ambil data roles untuk dropdown
$sql_roles = "SELECT * FROM data_roles WHERE status = 'aktif' ORDER BY nama_role ASC";
$result_roles = $conn->query($sql_roles);

// Ambil data menu
$sql_menus = "SELECT * FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC";
$result_menus = $conn->query($sql_menus);

// Ambil role yang dipilih (jika ada)
$selected_role = $_GET['role'] ?? '';
$permissions = [];

if ($selected_role) {
    // Ambil hak akses untuk role tersebut (asumsi ada tabel hak_akses)
    $sql_permissions = "SELECT * FROM hak_akses WHERE id_role = ?";
    $stmt = $conn->prepare($sql_permissions);
    $stmt->bind_param("i", $selected_role);
    $stmt->execute();
    $result_permissions = $stmt->get_result();
    while ($row = $result_permissions->fetch_assoc()) {
        $permissions[$row['id_menu']] = $row;
    }
    $stmt->close();
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User - Sistem Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- DataTables CSS tidak diperlukan -->


    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <h1>Data User</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $_SESSION['username']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <?php
                            if ($success === 'tambah') echo 'Hak akses berhasil ditambahkan!';
                            elseif ($success === 'edit') echo 'Hak akses berhasil diperbarui!';
                            elseif ($success === 'hapus') echo 'Hak akses berhasil dihapus!';
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-shield-check"></i> Pengaturan Hak Akses</h2>
                    </div>
                    <div class="card-body">
                        <!-- Form Pilih Role -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Pilih Role:</label>
                                    <select name="role" id="role" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Pilih Role --</option>
                                        <?php if ($result_roles && $result_roles->num_rows > 0): ?>
                                            <?php while ($role = $result_roles->fetch_assoc()): ?>
                                                <option value="<?= $role['id_role']; ?>" <?= $selected_role == $role['id_role'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($role['nama_role']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <?php if ($selected_role): ?>
                            <!-- Tabel Hak Akses -->
                            <form method="POST" action="update_permissions.php">
                                <input type="hidden" name="role_id" value="<?= $selected_role; ?>">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="tabelUser" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Menu</th>
                                                <th>Icon</th>
                                                <th class="text-center">Semua</th>
                                                <th class="text-center">Melihat</th>
                                                <th class="text-center">Menambah</th>
                                                <th class="text-center">Mengedit</th>
                                                <th class="text-center">Menghapus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result_menus && $result_menus->num_rows > 0): ?>
                                                <?php $no = 1; ?>
                                                <?php while ($menu = $result_menus->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= $no++; ?></td>
                                                        <td><?= htmlspecialchars($menu['nama_menu']); ?></td>
                                                        <td class="text-center">
                                                            <i class="<?= htmlspecialchars($menu['icon_menu']); ?>"></i>
                                                        </td>

                                                        <!-- CHECKBOX SEMUA PER BARIS -->
                                                        <td class="text-center">
                                                            <input type="checkbox" class="checkRow">
                                                        </td>

                                                        <!-- MELIHAT -->
                                                        <td class="text-center">
                                                            <input type="checkbox"
                                                                class="checkView"
                                                                name="permissions[<?= $menu['id_menu']; ?>][view]" value="1"
                                                                <?= isset($permissions[$menu['id_menu']]['can_view']) && $permissions[$menu['id_menu']]['can_view'] ? 'checked' : ''; ?>>
                                                        </td>

                                                        <!-- MENAMBAH -->
                                                        <td class="text-center">
                                                            <input type="checkbox"
                                                                class="checkAdd"
                                                                name="permissions[<?= $menu['id_menu']; ?>][add]" value="1"
                                                                <?= isset($permissions[$menu['id_menu']]['can_add']) && $permissions[$menu['id_menu']]['can_add'] ? 'checked' : ''; ?>>
                                                        </td>

                                                        <!-- MENGEDIT -->
                                                        <td class="text-center">
                                                            <input type="checkbox"
                                                                class="checkEdit"
                                                                name="permissions[<?= $menu['id_menu']; ?>][edit]" value="1"
                                                                <?= isset($permissions[$menu['id_menu']]['can_edit']) && $permissions[$menu['id_menu']]['can_edit'] ? 'checked' : ''; ?>>
                                                        </td>

                                                        <!-- MENGHAPUS -->
                                                        <td class="text-center">
                                                            <input type="checkbox"
                                                                class="checkDelete"
                                                                name="permissions[<?= $menu['id_menu']; ?>][delete]" value="1"
                                                                <?= isset($permissions[$menu['id_menu']]['can_delete']) && $permissions[$menu['id_menu']]['can_delete'] ? 'checked' : ''; ?>>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>

                                    </table>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Hak Akses
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <span>Silakan pilih role terlebih dahulu untuk mengatur hak akses.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>

    <script>
        $(document).ready(function() {
            $('#tabelUser').DataTable({
                responsive: true,
                pageLength: 10,
                lengthChange: true,
                autoWidth: false,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                },
                columnDefs: [{
                        orderable: false,
                        targets: [2, 7]
                    }, // icon & aksi
                    {
                        searchable: false,
                        targets: [0, 2, 7]
                    }
                ]
            });
        });
    </script>
    <script>
        $(document).ready(function() {

            // PILIH SEMUA PER BARIS
            $('.checkRow').on('change', function() {
                let row = $(this).closest('tr');
                row.find('.checkView, .checkAdd, .checkEdit, .checkDelete')
                    .prop('checked', this.checked);
            });

        });
    </script>



</body>

</html>