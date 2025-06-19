<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
include 'koneksi.php';

if(isset($_POST['suhu']) && isset($_POST['kelembaban'])){
    $s = floatval($_POST['suhu']);
    $h = floatval($_POST['kelembaban']);
    $stmt = $conn->prepare("INSERT INTO monitoring (suhu, kelembaban) VALUES (?, ?)");
    $stmt->bind_param("dd", $s, $h);
    $stmt->execute();
    echo 'OK';
}else{
    echo 'NO DATA';
}

?>
<!-- 
If you need this JavaScript, move it to a separate .js file or below the PHP closing tag.
<script>
function saveToDB(s, h) {
  fetch('simpan.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `suhu=${encodeURIComponent(s)}&kelembaban=${encodeURIComponent(h)}`
  })
  .then(res => res.text())
  .then(txt => {
    if(txt.trim() !== 'OK') {
      console.error('Gagal simpan ke DB:', txt);
    } else {
      console.log('Data berhasil disimpan:', s, h);
    }
  })
  .catch(err => {
    console.error('Fetch error:', err);
  });
}
</script>
-->
