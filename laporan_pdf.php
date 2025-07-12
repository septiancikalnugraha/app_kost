<?php
// Pastikan sudah install dompdf via composer: composer require dompdf/dompdf
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    exit("Dompdf belum terinstall. Jalankan <b>composer require dompdf/dompdf</b> di folder app_kost, lalu coba lagi.");
}
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/database.php';
$conn = getConnection();
$kamar = $conn->query('SELECT * FROM tb_kamar ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$penghuni = $conn->query('SELECT * FROM tb_penghuni ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$tagihan = $conn->query('SELECT t.*, k.nomor AS nomor_kamar, p.nama AS nama_penghuni FROM tb_tagihan t JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN tb_kamar k ON kp.id_kamar = k.id JOIN tb_penghuni p ON kp.id_penghuni = p.id ORDER BY t.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$barang = $conn->query('SELECT * FROM tb_barang ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$barang_bawaan = $conn->query('SELECT bb.*, p.nama AS nama_penghuni, p.no_ktp, b.nama AS nama_barang, bb.created_at FROM tb_brng_bawaan bb JOIN tb_penghuni p ON bb.id_penghuni = p.id JOIN tb_barang b ON bb.id_barang = b.id ORDER BY p.nama, p.no_ktp, bb.id DESC')->fetchAll(PDO::FETCH_ASSOC);

$html = '<html><head><style>
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f8fafc; margin: 0; }
    .container { width: 100%; max-width: 800px; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(124,51,234,0.04); padding: 32px 32px 24px 32px; }
    h1 { color: #7c3aed; text-align: center; margin-bottom: 32px; font-size: 2.1em; font-weight: bold; letter-spacing: 1px; }
    h2 { color: #7c3aed; margin-top: 32px; margin-bottom: 12px; font-size: 1.15em; font-weight: bold; text-align: center; }
    table { width: 95%; margin: 0 auto 28px auto; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(124,51,234,0.04); }
    th, td { padding: 12px 10px; text-align: left; }
    th { background: #f3e8ff; color: #7c3aed; font-size: 1em; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
    tr { border-bottom: 1px solid #f1f1f1; }
    tr:last-child { border-bottom: none; }
    td { font-size: 1em; color: #333; }
    tr:nth-child(even) { background: #f8fafc; }
    tr:nth-child(odd) { background: #fff; }
</style></head><body><div class="container">';
$html .= '<h1>Laporan Kost</h1>';
// Kamar
$html .= '<h2>Laporan Kamar</h2><table><thead><tr><th>No</th><th>Nomor Kamar</th><th>Harga</th><th>Status</th></tr></thead><tbody>';
$no=1; foreach($kamar as $k) {
    $html .= '<tr><td>'.$no++.'</td><td>'.htmlspecialchars($k['nomor']).'</td><td>Rp '.number_format($k['harga'],0,',','.').'</td><td>'.htmlspecialchars($k['status']).'</td></tr>';
}
$html .= '</tbody></table>';
// Penghuni
$html .= '<h2>Laporan Penghuni</h2><table><thead><tr><th>No</th><th>Nama</th><th>No. KTP</th><th>No. HP</th><th>Tgl Masuk</th><th>Tgl Keluar</th></tr></thead><tbody>';
$no=1; foreach($penghuni as $p) {
    $html .= '<tr><td>'.$no++.'</td><td>'.htmlspecialchars($p['nama']).'</td><td>'.htmlspecialchars($p['no_ktp']).'</td><td>'.htmlspecialchars($p['no_hp']).'</td><td>'.$p['tgl_masuk'].'</td><td>'.$p['tgl_keluar'].'</td></tr>';
}
$html .= '</tbody></table>';
// Tagihan
$html .= '<h2>Laporan Tagihan</h2><table><thead><tr><th>No</th><th>Bulan</th><th>Nomor Kamar</th><th>Nama Penghuni</th><th>Jumlah Tagihan</th><th>Status</th></tr></thead><tbody>';
$no=1; foreach($tagihan as $t) {
    $html .= '<tr><td>'.$no++.'</td><td>'.htmlspecialchars($t['bulan']).'</td><td>'.htmlspecialchars($t['nomor_kamar']).'</td><td>'.htmlspecialchars($t['nama_penghuni']).'</td><td>Rp '.number_format($t['jml_tagihan'],0,',','.').'</td><td>'.htmlspecialchars($t['status']).'</td></tr>';
}
$html .= '</tbody></table>';
// Barang Kost
$html .= '<h2>Laporan Barang Kost</h2><table><thead><tr><th>No</th><th>Nama Barang</th><th>Created</th><th>Updated</th></tr></thead><tbody>';
$no=1; foreach($barang as $b) {
    $html .= '<tr><td>'.$no++.'</td><td>'.htmlspecialchars($b['nama']).'</td><td>'.($b['created_at'] ? date('d M Y H:i', strtotime($b['created_at'])) : '-').'</td><td>'.($b['updated_at'] ? date('d M Y H:i', strtotime($b['updated_at'])) : '-').'</td></tr>';
}
$html .= '</tbody></table>';
// Barang Bawaan
$html .= '<h2>Laporan Barang Bawaan</h2><table><thead><tr><th>No</th><th>Nama Penghuni</th><th>No. KTP</th><th>Nama Barang</th><th>Created</th></tr></thead><tbody>';
$no=1; foreach($barang_bawaan as $row) {
    $html .= '<tr><td>'.$no++.'</td><td>'.htmlspecialchars($row['nama_penghuni']).'</td><td>'.htmlspecialchars($row['no_ktp']).'</td><td>'.htmlspecialchars($row['nama_barang']).'</td><td>'.($row['created_at'] ? date('d M Y H:i', strtotime($row['created_at'])) : '-').'</td></tr>';
}
$html .= '</tbody></table>';
$html .= '</div></body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('laporan_kost_'.date('Ymd_His').'.pdf', ['Attachment' => true]);
exit; 