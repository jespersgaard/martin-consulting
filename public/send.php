<?php
// Kontaktformular-handler (kompatibel med PHP 5.6+). Ændr kun $to ved behov.
$to = 'martin@jespersgaard.com';
$allowedHosts = array('jespersgaard.com', 'www.jespersgaard.com');
$minSeconds = 3;       // hurtigere afsendelse end dette er en bot
$maxSeconds = 7200;    // token udløber efter 2 timer
$maxPerHour = 5;       // pr. IP
$maxLinks = 2;         // flere links i beskeden = spam

$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
$wantsJson = strpos($accept, 'application/json') !== false;

function done($ok, $msg, $json, $code = 200)
{
    if ($json) {
        http_response_code($ok ? 200 : $code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('ok' => $ok, 'message' => $msg));
    } else {
        header('Location: /kontakt/?status=' . ($ok ? 'sendt' : 'fejl'));
    }
    exit;
}

function text_length($v)
{
    return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v);
}

function clean_line($v)
{
    return trim(str_replace(array("\r", "\n"), ' ', $v));
}

// Hemmelig nøgle genereres én gang og gemmes ved siden af scriptet
function secret_key()
{
    $file = __DIR__ . '/.send-secret';
    if (is_readable($file)) {
        $s = trim((string) file_get_contents($file));
        if ($s !== '') {
            return $s;
        }
    }
    $s = function_exists('random_bytes') ? bin2hex(random_bytes(32)) : hash('sha256', uniqid('', true) . mt_rand() . __FILE__);
    if (@file_put_contents($file, $s) === false) {
        return hash('sha256', __FILE__ . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') . filemtime(__FILE__));
    }
    @chmod($file, 0600);
    return $s;
}

function sign($ts)
{
    return hash_hmac('sha256', (string) $ts, secret_key());
}

function client_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
}

// Simpel rate limit pr. IP i den midlertidige mappe
function rate_limited($max)
{
    $file = sys_get_temp_dir() . '/contact-rl-' . hash('sha256', client_ip() . secret_key());
    $now = time();
    $hits = array();
    if (is_readable($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (is_array($data)) {
            foreach ($data as $t) {
                if ($now - (int) $t < 3600) {
                    $hits[] = (int) $t;
                }
            }
        }
    }
    if (count($hits) >= $max) {
        return true;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
    return false;
}

// Udsteder et signeret tidsstempel, som formularen henter ved sideindlæsning
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $ts = time();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('ts' => $ts, 'sig' => sign($ts)));
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
foreach (array('HTTP_ORIGIN', 'HTTP_REFERER') as $h) {
    if (!empty($_SERVER[$h])) {
        $host = parse_url($_SERVER[$h], PHP_URL_HOST);
        if (!in_array($host, $allowedHosts, true)) {
            done(false, 'Ugyldig forespørgsel.', $wantsJson, 403);
        }
    }
}

// Signeret tidsstempel: skal være ægte, ikke for nyt og ikke for gammelt
$ts = isset($_POST['ts']) ? (int) $_POST['ts'] : 0;
$sig = isset($_POST['sig']) ? (string) $_POST['sig'] : '';
$age = time() - $ts;
if ($ts <= 0 || !hash_equals(sign($ts), $sig) || $age < $minSeconds || $age > $maxSeconds) {
    done(false, 'Formularen er udløbet eller blev sendt for hurtigt. Genindlæs siden, og prøv igen.', $wantsJson, 400);
}

$name = clean_line(isset($_POST['name']) ? (string) $_POST['name'] : '');
$email = clean_line(isset($_POST['email']) ? (string) $_POST['email'] : '');
$message = trim(isset($_POST['message']) ? (string) $_POST['message'] : '');

if ($name === '' || text_length($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($message) < 5 || text_length($message) > 5000) {
    done(false, 'Udfyld venligst alle felter korrekt.', $wantsJson, 422);
}

if (preg_match_all('#https?://|www\.#i', $name . ' ' . $message) > $maxLinks) {
    done(false, 'Beskeden indeholder for mange links.', $wantsJson, 422);
}

if (rate_limited($maxPerHour)) {
    done(false, 'Der er sendt for mange beskeder. Prøv igen senere.', $wantsJson, 429);
}

$subject = '=?UTF-8?B?' . base64_encode('Ny henvendelse fra ' . $name) . '?=';
$body = "Navn: $name\nE-mail: $email\n\n$message\n";
$headers = implode("\r\n", array(
    'From: Kontaktformular <noreply@jespersgaard.com>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
));

$sent = mail($to, $subject, $body, $headers);
done($sent, $sent ? 'Tak for din besked. Jeg vender tilbage hurtigst muligt.' : 'Beskeden kunne ikke sendes. Prøv igen senere.', $wantsJson, 500);
