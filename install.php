<?php
// ตัวช่วยติดตั้งลอยกระทงออนไลน์: เปิดไฟล์นี้ในเบราว์เซอร์ กรอกข้อมูลฐานข้อมูล แล้วกด "ติดตั้ง"
// ระบบจะสร้างตาราง (จาก schema.sql) และสร้างไฟล์ config.php ให้อัตโนมัติ
// ติดตั้งเสร็จแล้วให้ลบไฟล์ install.php ทิ้ง

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function page($body) {
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>ติดตั้ง · ลอยกระทงออนไลน์</title><style>'
       . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#050b1e;color:#fdf3dc;font-family:system-ui,"Segoe UI",Tahoma,sans-serif;padding:16px}'
       . '.box{width:100%;max-width:460px;background:#0e1a3a;border:1px solid rgba(255,210,122,.3);border-radius:20px;padding:26px}'
       . 'h1{margin:0 0 6px;font-size:1.4rem;color:#ffe9b8}p{color:#cdbf9f;line-height:1.6;margin:.4rem 0 1rem}'
       . 'label{display:block;margin:.8rem 0 .25rem;font-size:.9rem;color:#ffe0a0}'
       . 'input{width:100%;box-sizing:border-box;padding:.65rem .8rem;border-radius:10px;border:1px solid #3a4a7a;background:#08112b;color:#fff;font-size:1rem}'
       . 'button{margin-top:1.2rem;width:100%;padding:.8rem;border:0;border-radius:999px;background:linear-gradient(135deg,#ffe29a,#ffa84a);color:#3b1d00;font-size:1.05rem;font-weight:600;cursor:pointer}'
       . '.err{background:#4a1420;border:1px solid #ff6b81;border-radius:10px;padding:.7rem .9rem;margin:.8rem 0;color:#ffd0d6}'
       . '.ok{background:#12351f;border:1px solid #4cd68a;border-radius:10px;padding:.7rem .9rem;margin:.8rem 0;color:#c9f5da}'
       . 'a{color:#ffe0a0}code,textarea{font-family:Consolas,monospace}textarea{width:100%;height:170px;background:#08112b;color:#fff;border:1px solid #3a4a7a;border-radius:10px;padding:.6rem}'
       . '.hint{font-size:.82rem;color:#9fb0d8}'
       . '</style></head><body><div class="box">' . $body . '</div></body></html>';
    exit;
}

$configFile = __DIR__ . '/config.php';
$schemaFile = __DIR__ . '/schema.sql';

if (is_file($configFile)) {
    page('<h1>ติดตั้งแล้ว ✅</h1><p>พบไฟล์ <code>config.php</code> อยู่แล้ว จึงปิดตัวช่วยติดตั้งไว้เพื่อความปลอดภัย</p>'
       . '<p>เปิดใช้งานได้ที่ <a href="./">หน้าเว็บลอยกระทง</a><br>แนะนำให้ลบไฟล์ <code>install.php</code> ออกจากเซิร์ฟเวอร์</p>'
       . '<p class="hint">ถ้าต้องการติดตั้งใหม่ ให้ลบไฟล์ config.php ก่อน</p>');
}

$v = ['host' => 'localhost', 'port' => '3306', 'name' => 'loy_krathong', 'user' => 'root', 'pass' => ''];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $v['pass'] = (string)($_POST['pass'] ?? '');   // รหัสผ่านไม่ตัดช่องว่าง

    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $v['name'])) {
        $error = 'ชื่อฐานข้อมูลใช้ได้เฉพาะ A-Z, 0-9 และ _ เท่านั้น';
    } elseif (!is_file($schemaFile)) {
        $error = 'ไม่พบไฟล์ schema.sql ในโฟลเดอร์เดียวกัน';
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $con = @mysqli_connect($v['host'], $v['user'], $v['pass'], '', (int)$v['port']);
        if (!$con) {
            $error = 'เชื่อมต่อ MySQL ไม่ได้: ' . mysqli_connect_error();
        } else {
            mysqli_set_charset($con, 'utf8mb4');
            // พยายามสร้างฐานข้อมูล (ถ้าโฮสต์ไม่ให้สิทธิ์ ให้สร้างไว้ก่อนแล้วใช้ชื่อนั้น)
            @mysqli_query($con, 'CREATE DATABASE IF NOT EXISTS `' . $v['name'] . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            if (!@mysqli_select_db($con, $v['name'])) {
                $error = 'ไม่พบฐานข้อมูล "' . $v['name'] . '" และสร้างให้ไม่ได้ กรุณาสร้างฐานข้อมูลนี้ก่อน (ดูคู่มือ) แล้วลองใหม่';
            } else {
                // รัน schema.sql ทีละคำสั่ง (ตัดบรรทัดคอมเมนต์ --)
                $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($schemaFile));
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if (!mysqli_query($con, $stmt)) {
                        $error = 'สร้างตารางไม่สำเร็จ: ' . mysqli_error($con);
                        break;
                    }
                }
                if ($error === '') {
                    $cfg = "<?php\n// สร้างอัตโนมัติโดย install.php\nreturn " . var_export([
                        'host' => $v['host'], 'port' => (int)$v['port'], 'name' => $v['name'],
                        'user' => $v['user'], 'pass' => $v['pass'], 'charset' => 'utf8mb4',
                    ], true) . ";\n";
                    if (@file_put_contents($configFile, $cfg) === false) {
                        page('<h1>เกือบเสร็จแล้ว</h1><div class="ok">สร้างตารางเรียบร้อย</div>'
                           . '<p>แต่เขียนไฟล์ <code>config.php</code> ให้อัตโนมัติไม่ได้ (โฟลเดอร์ไม่มีสิทธิ์เขียน) '
                           . 'ให้สร้างไฟล์ <code>config.php</code> เองแล้ววางข้อความนี้:</p>'
                           . '<textarea readonly onclick="this.select()">' . h($cfg) . '</textarea>'
                           . '<p>จากนั้นลบไฟล์ <code>install.php</code> แล้วเปิด <a href="./">หน้าเว็บลอยกระทง</a></p>');
                    }
                    page('<h1>ติดตั้งสำเร็จ 🎉</h1><div class="ok">สร้างตารางและไฟล์ config.php เรียบร้อยแล้ว</div>'
                       . '<p><b>สำคัญ:</b> ลบไฟล์ <code>install.php</code> ออกจากเซิร์ฟเวอร์ก่อนเปิดใช้งานจริง</p>'
                       . '<p><a href="./">▶ ไปที่หน้าเว็บลอยกระทง</a></p>');
                }
            }
        }
    }
}

$f = function ($label, $name, $type = 'text') use ($v) {
    return '<label>' . $label . '</label><input type="' . $type . '" name="' . $name . '" value="' . h($v[$name]) . '"' . ($name === 'pass' ? '' : ' required') . '>';
};

page('<h1>ติดตั้ง · ลอยกระทงออนไลน์</h1><p>กรอกข้อมูลฐานข้อมูล MySQL แล้วกด "ติดตั้ง" ระบบจะสร้างตารางและตั้งค่าให้เอง</p>'
   . ($error !== '' ? '<div class="err">' . h($error) . '</div>' : '')
   . '<form method="post">'
   . $f('เซิร์ฟเวอร์ (Host)', 'host') . $f('พอร์ต', 'port') . $f('ชื่อฐานข้อมูล', 'name')
   . $f('ผู้ใช้ (User)', 'user') . $f('รหัสผ่าน (Password)', 'pass', 'password')
   . '<button type="submit">ติดตั้ง</button></form>'
   . '<p class="hint">XAMPP: ใช้ค่าเริ่มต้นได้เลย (user root, รหัสผ่านว่าง) · ไม่ตั้งฐานข้อมูลก็เปิดเว็บใช้ได้ แต่กระทงจะเก็บเฉพาะในเบราว์เซอร์ของแต่ละคน</p>');
