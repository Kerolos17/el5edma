<?php

$secret = getenv('WEBHOOK_SECRET');
$payload = file_get_contents('php://input');
$signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

$data = json_decode($payload, true);

if (($data['ref'] ?? '') !== 'refs/heads/main') {
    http_response_code(200);
    exit('Not main branch, skipping.');
}

$projectPath = dirname(__DIR__);
$output = [];
$returnCode = 0;

exec("cd {$projectPath} && git fetch origin main 2>&1", $output, $returnCode);
exec("cd {$projectPath} && git reset --hard origin/main 2>&1", $output, $returnCode);
exec("cd {$projectPath} && bash deploy.sh 2>&1", $output, $returnCode);

http_response_code($returnCode === 0 ? 200 : 500);
header('Content-Type: text/plain');
echo implode("\n", $output);
