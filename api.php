<?php
// API ลอยกระทง
// GET  api.php        -> รายการกระทงล่าสุด (ชื่อ/แบบกระทง/คำอธิษฐาน/เวลา)
// POST api.php (JSON) -> บันทึกกระทงใหม่ {name, theme, wish}
// ตั้งค่าฐานข้อมูลที่ config.php และสร้างตารางด้วย schema.sql

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$cfg = require __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$con = @mysqli_connect($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int)$cfg['port']);
if (!$con) out(['ok' => false, 'error' => 'db'], 500);
mysqli_set_charset($con, $cfg['charset']);

$themes = ['lotus', 'marigold', 'orchid', 'jasmine'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) out(['ok' => false, 'error' => 'bad_request'], 400);

    $name  = mb_substr(trim((string)($in['name'] ?? '')), 0, 30, 'UTF-8');
    $theme = (string)($in['theme'] ?? '');
    $wish  = mb_substr(trim((string)($in['wish'] ?? '')), 0, 120, 'UTF-8');
    if ($name === '' || !in_array($theme, $themes, true)) out(['ok' => false, 'error' => 'invalid'], 400);

    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);

    // กันสแปม: ไม่เกิน 6 ดวง/นาที ต่อ IP
    $st = mysqli_prepare($con, "SELECT COUNT(*) FROM loy_krathong WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)");
    if (!$st) out(['ok' => false, 'error' => 'table_missing'], 500);
    mysqli_stmt_bind_param($st, 's', $ip);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $cnt);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    if ($cnt >= 6) out(['ok' => false, 'error' => 'rate_limit'], 429);

    $st = mysqli_prepare($con, "INSERT INTO loy_krathong (name, theme, wish, ip, created_at) VALUES (?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($st, 'ssss', $name, $theme, $wish, $ip);
    if (!mysqli_stmt_execute($st)) out(['ok' => false, 'error' => 'insert'], 500);
    out(['ok' => true, 'id' => mysqli_insert_id($con)]);
}

// GET: ล่าสุด 60 ดวง (เรียงเก่า→ใหม่)
$rows = [];
$res = mysqli_query($con, "SELECT id, name, theme, wish, UNIX_TIMESTAMP(created_at) AS t FROM loy_krathong ORDER BY id DESC LIMIT 60");
if (!$res) out(['ok' => false, 'error' => 'table_missing'], 500);
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'theme' => $r['theme'], 'wish' => $r['wish'], 't' => (int)$r['t'] * 1000];
}
out(['ok' => true, 'items' => array_reverse($rows)]);
