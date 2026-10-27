# Bài 3 – Gửi email nhiều người

Ứng dụng PHP gửi thư riêng lẻ cho danh sách email nhập từ form. Các địa chỉ được chuẩn hóa khoảng trắng, bỏ rỗng, loại bỏ trùng và kiểm tra định dạng. Trang kết quả báo số địa chỉ hợp lệ, email gửi thành công và thất bại. Mỗi thư chỉ có một người nhận, không để lộ danh sách cho người khác.

## Cài đặt và chạy

1. Cài PHP 8 trở lên và Composer.
2. Trong thư mục này, chạy:

   ```sh
   composer install
   ```

3. Sao chép `config.local.php.example` thành `config.local.php`; điền Gmail gửi và App Password.
4. Bật xác minh hai bước trên tài khoản Google và tạo App Password nếu tài khoản đủ điều kiện. Không dùng mật khẩu Gmail thông thường.
5. Đặt dự án trong `htdocs`, bật Apache trên XAMPP, rồi mở:

   `http://localhost/Lab8_Email/Bai3_GuiEmailNhieuNguoi/`

6. Nhập các địa chỉ phân tách bằng dấu phẩy, tiêu đề và nội dung; gửi và xem báo cáo trên trang. Kiểm tra thư mục Spam nếu không thấy email.

SMTP mặc định là `smtp.gmail.com`, cổng `587` với STARTTLS và charset UTF-8. Giới hạn tối đa 100 người nhận hợp lệ trong một lần gửi để tránh batch quá lớn. Không commit `config.local.php`; không tắt xác minh chứng chỉ TLS.

## Mở rộng lấy email từ MySQL

Có thể thay danh sách form bằng kết quả truy vấn PDO, sau đó truyền mảng email vào cùng bước loại trùng, kiểm tra và gửi riêng từng địa chỉ. Dùng prepared statement, phân trang/giới hạn kết quả và bảo đảm người nhận đã đồng ý nhận thư.

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=ten_database;charset=utf8mb4',
    $dbUser,
    $dbPassword,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$stmt = $pdo->prepare('SELECT email FROM subscribers WHERE is_active = :active');
$stmt->execute(['active' => 1]);
$recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
```

Nếu Composer báo thiếu ZIP extension trên Windows, bật ZIP extension trong `php.ini` mà Composer sử dụng, rồi chạy lại `composer install`. Thư viện PHPMailer chính thức được cài vào `PHPMailer/` bằng Composer; thư mục này không được commit.
