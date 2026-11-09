<?php
require '../config/koneksi.php';

$id = $_GET['id'];
$aksi = $_GET['aksi'];

if ($aksi == 'used') {
    $pdo->prepare("UPDATE e_tiket SET status = 'used' WHERE id_e_tiket = ?")->execute([$id]);
    echo "<script>alert('Tiket berhasil divalidasi!'); window.history.back();</script>";
}
?>