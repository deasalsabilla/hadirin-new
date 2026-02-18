<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

/* Ambil ID role */
$id_role = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* Validasi ID */
if ($id_role === 0) {
    header("Location: index.php?error=ID role tidak valid");
    exit;
}

$conn = getConnection();

/* Hapus data role */
$stmt = $conn->prepare("DELETE FROM data_roles WHERE id_role = ?");
$stmt->bind_param("i", $id_role);

if ($stmt->execute()) {
    header("Location: index.php?success=hapus");
} else {
    header("Location: index.php?error=" . urlencode("Gagal menghapus data role"));
}

$stmt->close();
closeConnection($conn);
exit;
?>





