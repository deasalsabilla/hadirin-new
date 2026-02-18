<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$id_absensi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_absensi === 0) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$conn = getConnection();

// Hapus data absensi
$stmt = $conn->prepare("DELETE FROM absensi WHERE id_absensi = ?");
$stmt->bind_param("i", $id_absensi);

if ($stmt->execute()) {
    header("Location: index.php?success=hapus");
} else {
    header("Location: index.php?error=" . urlencode($conn->error));
}

$stmt->close();
closeConnection($conn);
exit;
?>