<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$form = $_SESSION['bai3_form'] ?? ['recipients' => '', 'subject' => '', 'body' => ''];
unset($_SESSION['bai3_form']);
$report = $_SESSION['bai3_report'] ?? null;
unset($_SESSION['bai3_report']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bài 3 - Gửi email nhiều người</title>
    <style>
        :root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#182230;background:#f3f6fb}
        body{margin:0;padding:36px 18px}.card{max-width:800px;margin:auto;padding:30px;background:#fff;border:1px solid #e3e8ef;border-radius:16px;box-shadow:0 12px 35px #18223012}
        h1{margin-top:0;font-size:1.75rem}.intro{color:#5d6878;line-height:1.6}label{display:block;margin:18px 0 7px;font-weight:650}
        input,textarea{box-sizing:border-box;width:100%;padding:11px 12px;border:1px solid #bdc7d5;border-radius:8px;font:inherit}
        input:focus,textarea:focus{outline:3px solid #155eef30;border-color:#155eef}textarea{min-height:160px;resize:vertical}
        button{margin-top:20px;border:0;border-radius:9px;padding:12px 20px;background:#155eef;color:#fff;font-size:1rem;font-weight:650;cursor:pointer}
        button:hover{background:#124bc2}.report{padding:16px;background:#f2f6ff;border-radius:10px;margin:22px 0}.report ul{padding-left:22px}.success{color:#176b35}.error{color:#a52a1c}
        .help{margin:6px 0;color:#5d6878;font-size:.9rem}
    </style>
</head>
<body>
<main class="card">
    <h1>Bài 3 - Gửi email nhiều người</h1>
    <p class="intro">Nhập tối đa 100 địa chỉ, phân tách bằng dấu phẩy. Mỗi người nhận được gửi một email riêng, không nhìn thấy địa chỉ của người khác.</p>
    <?php if (is_array($report)): ?>
        <section class="report" aria-live="polite">
            <h2>Kết quả gửi</h2>
            <p>Số địa chỉ hợp lệ, duy nhất: <strong><?= (int) $report['valid'] ?></strong></p>
            <p>Số email gửi thành công: <strong class="success"><?= (int) $report['sent'] ?></strong></p>
            <p>Số địa chỉ/email thất bại: <strong class="error"><?= (int) $report['failed'] ?></strong></p>
            <?php if ($report['issues'] !== []): ?>
                <details>
                    <summary>Chi tiết địa chỉ chưa gửi được</summary>
                    <ul>
                        <?php foreach ($report['issues'] as $issue): ?>
                            <li><?= e($issue['email']) ?> — <?= e($issue['reason']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <form method="POST" action="mail.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <label for="recipients">Email người nhận</label>
        <textarea id="recipients" name="recipients" maxlength="20000" required><?= e((string) $form['recipients']) ?></textarea>
        <p class="help">Ví dụ: nguoi1@example.com, nguoi2@example.com</p>
        <label for="subject">Tiêu đề email</label>
        <input id="subject" name="subject" type="text" maxlength="200" required value="<?= e((string) $form['subject']) ?>">
        <label for="body">Nội dung email</label>
        <textarea id="body" name="body" maxlength="20000" required><?= e((string) $form['body']) ?></textarea>
        <button type="submit">Gửi email riêng cho từng người</button>
    </form>
</main>
</body>
</html>
