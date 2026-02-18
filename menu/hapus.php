<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

// Ambil ID menu
$id_menu = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Validasi ID
if ($id_menu === 0) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$conn = getConnection();

// Hapus data menu
$stmt = $conn->prepare("DELETE FROM data_menu WHERE id_menu = ?");
$stmt->bind_param("i", $id_menu);

if ($stmt->execute()) {
    header("Location: index.php?success=hapus");
} else {
    header("Location: index.php?error=" . urlencode("Gagal menghapus data menu"));
}

$stmt->close();
closeConnection($conn);
exit;
?>


