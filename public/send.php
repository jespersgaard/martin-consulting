<?php
// Kontaktformular-handler til Simply.com (PHP). Ændr kun $to ved behov.
$to = 'kontakt@jespersgaard.com';
$host = 'www.jespersgaard.com';

$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

function done(bool $ok, string $msg, bool $json, int $code = 200): void {
    if ($json) {
        http_response_code($ok ? 200 : $code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $msg]);
    } else {
        header('Location: /kontakt/?status=' . ($ok ? 'sendt' : 'fejl'));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    done(false, 'Ugyldig forespørgsel.', $wantsJson, 405);
}

// Honeypot: rigtige brugere efterlader feltet tomt
if (!empty($_POST['website'])) {
    done(true, 'Tak for din besked.', $wantsJson);
}

$clean = fn(string $v): string => trim(str_replace(["\r", "\n"], ' ', $v));
$name = $clean((string)($_POST['name'] ?? ''));
$email = $clean((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5 || mb_strlen($message) > 5000) {
    done(false, 'Udfyld venligst alle felter korrekt.', $wantsJson, 422);
}

$subject = '=?UTF-8?B?' . base64_encode('Ny henvendelse fra ' . $name) . '?=';
$body = "Navn: $name\nE-mail: $email\n\n$message\n";
$headers = [
    "From: Kontaktformular <noreply@jespersgaard.com>",
    "Reply-To: $email",
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));
done($sent, $sent ? 'Tak for din besked. Jeg vender tilbage hurtigst muligt.' : 'Beskeden kunne ikke sendes. Prøv igen senere.', $wantsJson, 500);
