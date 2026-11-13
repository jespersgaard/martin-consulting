<?php
// Kontaktformular-handler (kompatibel med PHP 5.6+). Ændr kun $to ved behov.
$to = 'martin@jespersgaard.com';

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

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    done(false, 'Ugyldig forespørgsel.', $wantsJson, 405);
}

// Honeypot: rigtige brugere efterlader feltet tomt
if (!empty($_POST['website'])) {
    done(true, 'Tak for din besked.', $wantsJson);
}

$name = clean_line(isset($_POST['name']) ? (string) $_POST['name'] : '');
$email = clean_line(isset($_POST['email']) ? (string) $_POST['email'] : '');
$message = trim(isset($_POST['message']) ? (string) $_POST['message'] : '');

if ($name === '' || text_length($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($message) < 5 || text_length($message) > 5000) {
    done(false, 'Udfyld venligst alle felter korrekt.', $wantsJson, 422);
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
