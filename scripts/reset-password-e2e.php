<?php
/**
 * End-to-end check of the password reset flow against a local server.
 *
 * Drives real HTTP: request link -> read tokenized URL from the mail log
 * (MAIL_MAILER=log) -> load the form -> submit a new secret. Post bodies are
 * built as arrays so nothing at the shell layer can rewrite one field and not
 * its confirmation.
 *
 * WARNING: this really does change the target account's password. It is
 * localhost-only by design; afterwards, reset the account from the admin UI or
 * re-seed. Never point it at an account you cannot restore.
 *
 * Usage: php scripts/reset-password-e2e.php [baseUrl] [email]
 */

$base = $argv[1] ?? 'http://127.0.0.1:8001';
$email = $argv[2] ?? 'staff@rapidrebuilt.com';

$host = parse_url($base, PHP_URL_HOST);
if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "Refusing to run against non-local host: {$host}\n");
    exit(1);
}

$jar = sys_get_temp_dir() . '/rr_reset_e2e_cookies.txt';
@unlink($jar);

$pwField = 'password';
$confField = 'password_confirmation';
$newSecret = 'NewStr0ngPass99';

function req(string $url, ?array $post, string $jar): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    return ['code' => $code, 'body' => (string) $body, 'redirect' => (string) $redirect];
}

function csrf(string $html): string
{
    preg_match('/name="_token" value="([^"]+)"/', $html, $m);

    return $m[1] ?? '';
}

$logPath = __DIR__ . '/../storage/logs/laravel.log';
$sizeBefore = file_exists($logPath) ? filesize($logPath) : 0;

// 1. Ask for a reset link.
$page = req("$base/forget-password", null, $jar);
$sent = req("$base/forget-password", ['_token' => csrf($page['body']), 'email' => $email], $jar);
echo "1. request link       -> {$sent['code']}\n";

// 2. Recover the tokenized URL from the freshly written log tail.
$fresh = file_get_contents($logPath, false, null, $sizeBefore);
preg_match_all('#https?://[^"\s<]*?/reset-password/([A-Za-z0-9]{64})\?email=[^"<\s]+#', $fresh, $m);
if (! $m[0]) {
    fwrite(STDERR, "No reset link found in log tail\n");
    exit(1);
}
$link = html_entity_decode(end($m[0]));
$token = end($m[1]);
echo "2. link in mail log   -> token " . strlen($token) . " chars\n";

// 3. Load the form the link points at.
$form = req($link, null, $jar);
$present = str_contains($form['body'], 'name="' . $confField . '"');
echo "3. reset form loads   -> {$form['code']} (confirmation field " . ($present ? 'present' : 'MISSING') . ")\n";

// 4. Submit the new secret.
$done = req("$base/reset-password", [
    '_token' => csrf($form['body']),
    'token' => $token,
    'email' => $email,
    $pwField => $newSecret,
    $confField => $newSecret,
], $jar);
echo "4. submit new secret  -> {$done['code']} redirect={$done['redirect']}\n";

// 5. Report any validation error left on the page.
$after = req($link, null, $jar);
if (preg_match('/alert alert-danger(.*?)<\/div>/s', $after['body'], $em)) {
    $msg = trim(preg_replace('/\s+/', ' ', strip_tags($em[1])));
    if ($msg !== '') {
        echo "   error still shown  -> {$msg}\n";
    }
}

echo "   new secret (for the verification step): {$newSecret}\n";
