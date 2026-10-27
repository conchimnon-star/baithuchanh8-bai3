<?php
declare(strict_types=1);

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('config.local.php phải trả về một mảng cấu hình.');
}

$mailConfig = array_replace([
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => getenv('LAB8_SMTP_USERNAME') ?: '',
    'smtp_password' => getenv('LAB8_SMTP_PASSWORD') ?: '',
    'from_email' => getenv('LAB8_SMTP_USERNAME') ?: '',
    'from_name' => 'Lab 8 - Bai 3',
    'max_recipients' => 100,
], $localConfig);
