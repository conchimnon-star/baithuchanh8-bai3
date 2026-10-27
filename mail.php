<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/config.php';

function finishBatch(array $form, array $report): void
{
    $_SESSION['bai3_form'] = $form;
    $_SESSION['bai3_report'] = $report;
    header('Location: index.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

$postRecipients = $_POST['recipients'] ?? null;
$postSubject = $_POST['subject'] ?? null;
$postBody = $_POST['body'] ?? null;
$form = [
    'recipients' => is_string($postRecipients) ? trim($postRecipients) : '',
    'subject' => is_string($postSubject) ? trim($postSubject) : '',
    'body' => is_string($postBody) ? trim($postBody) : '',
];
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    finishBatch($form, ['valid' => 0, 'sent' => 0, 'failed' => 1, 'issues' => [['email' => '(yêu cầu)', 'reason' => 'Phiên gửi không hợp lệ; hãy thử lại.']]]);
}
if (!is_string($postRecipients) || !is_string($postSubject) || !is_string($postBody)) {
    finishBatch($form, ['valid' => 0, 'sent' => 0, 'failed' => 1, 'issues' => [['email' => '(nội dung)', 'reason' => 'Dữ liệu biểu mẫu không hợp lệ.']]]);
}
if (
    strlen($form['recipients']) > 20000
    || $form['subject'] === ''
    || strlen($form['subject']) > 200
    || preg_match('/[\r\n]/', $form['subject'])
    || $form['body'] === ''
    || strlen($form['body']) > 20000
) {
    finishBatch($form, ['valid' => 0, 'sent' => 0, 'failed' => 1, 'issues' => [['email' => '(nội dung)', 'reason' => 'Tiêu đề/nội dung không hợp lệ hoặc vượt giới hạn.']]]);
}

$rawRecipients = array_map('trim', explode(',', $form['recipients']));
$uniqueRecipients = [];
$seen = [];
$issues = [];
foreach ($rawRecipients as $email) {
    if ($email === '') {
        continue;
    }
    $key = strtolower($email);
    if (isset($seen[$key])) {
        continue;
    }
    $seen[$key] = true;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $issues[] = ['email' => $email, 'reason' => 'Định dạng email không hợp lệ.'];
        continue;
    }
    $uniqueRecipients[] = $email;
}

if (count($uniqueRecipients) > $mailConfig['max_recipients']) {
    finishBatch($form, [
        'valid' => count($uniqueRecipients),
        'sent' => 0,
        'failed' => count($issues) + count($uniqueRecipients),
        'issues' => array_merge($issues, [['email' => '(danh sách)', 'reason' => 'Vượt quá giới hạn 100 địa chỉ trong một lần gửi.']]),
    ]);
}
if ($uniqueRecipients === [] && $issues === []) {
    $issues[] = ['email' => '(danh sách)', 'reason' => 'Hãy nhập ít nhất một địa chỉ email.'];
}
if (
    $uniqueRecipients !== []
    && (
        !filter_var($mailConfig['smtp_username'], FILTER_VALIDATE_EMAIL)
        || $mailConfig['smtp_password'] === ''
        || !filter_var($mailConfig['from_email'], FILTER_VALIDATE_EMAIL)
    )
) {
    foreach ($uniqueRecipients as $email) {
        $issues[] = ['email' => $email, 'reason' => 'Thiếu cấu hình Gmail SMTP trong config.local.php.'];
    }
    finishBatch($form, [
        'valid' => count($uniqueRecipients),
        'sent' => 0,
        'failed' => count($issues),
        'issues' => $issues,
    ]);
}

require __DIR__ . '/PHPMailer/autoload.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$sent = 0;
foreach ($uniqueRecipients as $email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = (string) $mailConfig['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = (string) $mailConfig['smtp_username'];
        $mail->Password = (string) $mailConfig['smtp_password'];
        $mail->SMTPSecure = (string) $mailConfig['smtp_secure'];
        $mail->Port = (int) $mailConfig['smtp_port'];
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom((string) $mailConfig['from_email'], (string) $mailConfig['from_name']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $form['subject'];
        $mail->Body = nl2br(htmlspecialchars($form['body'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $mail->AltBody = $form['body'];
        $mail->send();
        $sent++;
    } catch (Exception $exception) {
        $detail = str_replace(
            [(string) $mailConfig['smtp_password'], (string) $mailConfig['smtp_username']],
            '[đã ẩn]',
            $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $exception->getMessage()
        );
        $issues[] = ['email' => $email, 'reason' => $detail];
    }
}

finishBatch($form, [
    'valid' => count($uniqueRecipients),
    'sent' => $sent,
    'failed' => count($issues),
    'issues' => $issues,
]);
