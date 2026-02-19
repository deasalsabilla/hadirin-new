<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

// Ambil data roles
$result = $conn->query("SELECT * FROM data_roles ORDER BY id_role ASC");

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

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error   = isset($_GET['error']) ? $_GET['error'] : '';

closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Roles - Sistem Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">


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
                <h1>Data Roles</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <?php
                            if ($success === 'tambah') echo 'Data roles berhasil ditambahkan!';
                            elseif ($success === 'edit') echo 'Data roles berhasil diperbarui!';
                            elseif ($success === 'hapus') echo 'Data roles berhasil dihapus!';
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
                        <h2><i class="bi bi-people"></i> Daftar Roles</h2>
                        <a href="tambah.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Roles
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabelRoles" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Role</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                        <th>Tanggal Dibuat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php $no = 1;
                                        while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= htmlspecialchars($row['nama_role']); ?></td>
                                                <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                                <td class="text-center">
                                                    <?= $row['status'] == 'aktif'
                                                        ? '<span class="badge bg-success">Aktif</span>'
                                                        : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                <td class="text-center">
                                                    <a href="edit.php?id=<?= $row['id_role']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="hapus.php?id=<?= $row['id_role']; ?>" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Yakin ingin menghapus role ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        </div>
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
            $('#tabelRoles').DataTable({
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


</body>

</html>