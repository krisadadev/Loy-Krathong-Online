<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล MySQL / MariaDB
// แก้ค่าด้านล่างให้ตรงกับเซิร์ฟเวอร์ของคุณ (ดูขั้นตอนใน README.md)
return [
    'host'    => 'localhost',
    'port'    => 3306,
    'name'    => 'loy_krathong',   // ชื่อฐานข้อมูลที่สร้างไว้
    'user'    => 'root',           // XAMPP ค่าเริ่มต้นคือ root
    'pass'    => '',               // XAMPP ค่าเริ่มต้นคือรหัสผ่านว่าง
    'charset' => 'utf8mb4',
];
