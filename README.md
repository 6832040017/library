# ห้องสมุดออนไลน์ (PHP 8.5 + MySQL 8.4)
ต้อง login ก่อนอ่านหนังสือ / admin เพิ่มสมาชิกและเพิ่มหนังสือ (PDF เท่านั้น)

ติดตั้งบน server:
1. สร้างฐานข้อมูลและ user แล้วนำเข้า `schema.sql`
2. คัดลอก `config.local.php.example` เป็น `config.local.php` แล้วแก้ค่า
3. ให้ `www-data` เขียนโฟลเดอร์ `uploads/` ได้
4. สร้าง admin: `php create_admin.php ชื่อผู้ใช้ 'รหัสผ่าน'`
