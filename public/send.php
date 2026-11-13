<?php
// Kontaktformular-handler. Kræver PHP 8.1+ (ALTCHA-biblioteket ligger i vendor/).
// Ændr kun $to ved behov.
require __DIR__ . '/vendor/autoload.php';

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;

$to = 'martin@jespersgaard.com';
$allowedHosts = ['jespersgaard.com', 'www.jespersgaard.com'];
$maxPerHour = 5;
$maxLinks = 2;

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function done(bool $ok, string $msg, bool $json, int $code = 200): never
{
    if ($json) {
        http_response_code($ok ? 200 : $code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $msg]);
    } else {
        header('Location: /kontakt/?status=' . ($ok ? 'sendt' : 'fejl'));
    }
    exit;
}

function text_length(string $v): int
{
    return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v);
}

function clean_line(string $v): string
{
    return trim(str_replace(["\r", "\n"], ' ', $v));
}

// Hemmelig nøgle genereres én gang og gemmes ved siden af scriptet
function secret_key(): string
{
    $file = __DIR__ . '/.send-secret';
    if (is_readable($file)) {
        $s = trim((string) file_get_contents($file));
        if ($s !== '') {
            return $s;
        }
    }
    $s = bin2hex(random_bytes(32));
    if (@file_put_contents($file, $s) === false) {
        return hash('sha256', __FILE__ . ($_SERVER['SERVER_NAME'] ?? '') . filemtime(__FILE__));
    }
    @chmod($file, 0600);
    return $s;
}

function altcha_instance(): Altcha
{
    return new Altcha(
        hmacSignatureSecret: hash_hmac('sha256', 'signature', secret_key()),
        hmacKeySignatureSecret: hash_hmac('sha256', 'key-signature', secret_key()),
    );
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function temp_file(string $prefix, string $id): string
{
    return sys_get_temp_dir() . '/' . $prefix . hash('sha256', $id . secret_key());
}

function recent_hits(): array
{
    $file = temp_file('contact-rl-', client_ip());
    $hits = [];
    if (is_readable($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            foreach ($data as $t) {
                if (time() - (int) $t < 3600) {
                    $hits[] = (int) $t;
                }
            }
        }
    }
    return $hits;
}

function record_hit(): void
{
    $hits = recent_hits();
    $hits[] = time();
    @file_put_contents(temp_file('contact-rl-', client_ip()), json_encode($hits), LOCK_EX);
}

// Hver løst udfordring kan kun bruges én gang
function challenge_already_used(string $payload): bool
{
    $data = json_decode((string) base64_decode($payload, true), true);
    $sig = is_array($data) ? ($data['challenge']['signature'] ?? '') : '';
    if (!is_string($sig) || $sig === '') {
        return true;
    }
    $file = temp_file('contact-ch-', $sig);
    if (file_exists($file)) {
        return true;
    }
    @file_put_contents($file, (string) time());
    return false;
}

// Udsteder en ALTCHA-udfordring til widgetten
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['challenge'])) {
    $challenge = altcha_instance()->createChallenge(new CreateChallengeOptions(
        algorithm: new Pbkdf2(),
        cost: 1000,
        counter: random_int(500, 3000),
        expiresAt: time() + 600,
    ));
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($challenge->toArray());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    done(false, 'Ugyldig forespørgsel.', $wantsJson, 405);
}

// Honeypot: rigtige brugere efterlader feltet tomt
if (!empty($_POST['website'])) {
    done(true, 'Tak for din besked.', $wantsJson);
}

// Afvis forespørgsler fra andre sites
foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
    if (!empty($_SERVER[$h])) {
        $host = parse_url($_SERVER[$h], PHP_URL_HOST);
        if (!in_array($host, $allowedHosts, true)) {
            done(false, 'Ugyldig forespørgsel.', $wantsJson, 403);
        }
    }
}

// ALTCHA: proof-of-work skal være løst og gyldigt
$payload = (string) ($_POST['altcha'] ?? '');
$verified = false;
if ($payload !== '') {
    try {
        $verified = altcha_instance()->verifySolution(new VerifySolutionOptions(
            algorithm: new Pbkdf2(),
            payload: $payload,
        ))->verified;
    } catch (Throwable $e) {
        $verified = false;
    }
}
if (!$verified || challenge_already_used($payload)) {
    done(false, 'Bekræftelsen mod spam mislykkedes. Genindlæs siden, og prøv igen.', $wantsJson, 400);
}

$name = clean_line((string) ($_POST['name'] ?? ''));
$email = clean_line((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || text_length($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($message) < 5 || text_length($message) > 5000) {
    done(false, 'Udfyld venligst alle felter korrekt.', $wantsJson, 422);
}

if (preg_match_all('#https?://|www\.#i', $name . ' ' . $message) > $maxLinks) {
    done(false, 'Beskeden indeholder for mange links.', $wantsJson, 422);
}

if (count(recent_hits()) >= $maxPerHour) {
    done(false, 'Der er sendt for mange beskeder. Prøv igen senere.', $wantsJson, 429);
}

$subject = '=?UTF-8?B?' . base64_encode('Ny henvendelse fra ' . $name) . '?=';
$body = "Navn: $name\nE-mail: $email\n\n$message\n";
$headers = implode("\r\n", [
    'From: Kontaktformular <' . $to . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
]);

$sent = mail($to, $subject, $body, $headers, '-f' . $to);
if ($sent) {
    record_hit();
} else {
    error_log('Kontaktformular: mail() fejlede');
}
done($sent, $sent ? 'Tak for din besked. Jeg vender tilbage hurtigst muligt.' : 'Beskeden kunne ikke sendes. Prøv igen senere.', $wantsJson, 500);
