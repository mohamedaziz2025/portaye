<?php
require_once __DIR__ . '/config.php';

class SMTPMailer {

    private static function connect() {
        $host   = SMTP_HOST;
        $port   = (int) SMTP_PORT;
        $secure = SMTP_SECURE;

        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]]);

        if ($secure === 'ssl') {
            $sock = stream_socket_client("ssl://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        } else {
            $sock = stream_socket_client("tcp://$host:$port", $errno, $errstr, 15);
        }

        if (!$sock) throw new RuntimeException("SMTP connexion impossible : $errstr ($errno)");
        stream_set_timeout($sock, 15);
        return $sock;
    }

    private static function read($sock): string {
        $buf = '';
        while (($line = fgets($sock, 1024)) !== false) {
            $buf .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $buf;
    }

    private static function cmd($sock, string $cmd, string $expect = ''): string {
        fputs($sock, $cmd . "\r\n");
        $resp = self::read($sock);
        if ($expect && strpos($resp, $expect) === false) {
            throw new RuntimeException("SMTP erreur sur '$cmd' : $resp");
        }
        return $resp;
    }

    public static function send(string $to, string $toName, string $subject, string $html): bool {
        try {
            $sock = self::connect();
            self::read($sock); // greeting

            $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
            self::cmd($sock, $ehlo, '250');

            if (SMTP_SECURE === 'tls') {
                self::cmd($sock, 'STARTTLS', '220');
                $ctx = stream_context_create(['ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]]);
                stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::cmd($sock, $ehlo, '250');
            }

            self::cmd($sock, 'AUTH LOGIN', '334');
            self::cmd($sock, base64_encode(SMTP_USER), '334');
            self::cmd($sock, base64_encode(SMTP_PASS), '235');

            self::cmd($sock, 'MAIL FROM:<' . SMTP_FROM . '>', '250');
            self::cmd($sock, 'RCPT TO:<' . $to . '>', '250');
            self::cmd($sock, 'DATA', '354');

            $fromEnc    = '=?UTF-8?B?' . base64_encode(SMTP_FROM_NAME) . '?=';
            $toEnc      = '=?UTF-8?B?' . base64_encode($toName) . '?=';
            $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $msgId      = '<' . uniqid('portaye', true) . '@' . gethostname() . '>';

            $body  = "From: $fromEnc <" . SMTP_FROM . ">\r\n";
            $body .= "To: $toEnc <$to>\r\n";
            $body .= "Subject: $subjectEnc\r\n";
            $body .= "Message-ID: $msgId\r\n";
            $body .= "Date: " . date('r') . "\r\n";
            $body .= "MIME-Version: 1.0\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: quoted-printable\r\n";
            $body .= "\r\n";
            $body .= quoted_printable_encode($html) . "\r\n";
            $body .= ".\r\n";

            fputs($sock, $body);
            self::read($sock);
            self::cmd($sock, 'QUIT');
            fclose($sock);
            return true;

        } catch (RuntimeException $e) {
            error_log('[Portaye Mailer] ' . $e->getMessage());
            return false;
        }
    }
}

// ─── TEMPLATES EMAIL ──────────────────────────────────────────────────────────

function email_wrap(string $title, string $content): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>$title</title></head>
<body style="margin:0;padding:0;background:#f5f5f7;font-family:-apple-system,Helvetica Neue,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%">
  <tr><td style="background:#000;border-radius:16px 16px 0 0;padding:28px 40px;text-align:center">
    <span style="font-size:24px;font-weight:700;color:#f5f5f7;letter-spacing:-0.5px">Portaye</span>
  </td></tr>
  <tr><td style="background:#fff;padding:40px;border-radius:0 0 16px 16px">
    $content
    <hr style="border:none;border-top:1px solid #e5e5ea;margin:32px 0">
    <p style="font-size:12px;color:#aeaeb2;text-align:center;margin:0">
      © 2026 Portaye · Sécurité Résidentielle Intelligente<br>
      <a href="mailto:contact@portaye.fr" style="color:#0071e3;text-decoration:none">contact@portaye.fr</a>
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

function format_date_fr(string $date): string {
    $ts = strtotime($date);
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    return $jours[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function mail_client_pending(array $a): bool {
    $date  = format_date_fr($a['appt_date']);
    $heure = substr($a['appt_time'], 0, 5);
    $name  = htmlspecialchars($a['name']);
    $cancel_url = SITE_URL . '/api.php?a=cancel_client&token=' . urlencode($a['cancel_token']);

    $content = <<<HTML
<h2 style="font-size:22px;font-weight:700;color:#1d1d1f;margin:0 0 8px">Demande reçue !</h2>
<p style="color:#6e6e73;font-size:15px;margin:0 0 28px">Bonjour $name, nous avons bien reçu votre demande de rendez-vous.</p>
<div style="background:#f5f5f7;border-radius:12px;padding:24px;margin-bottom:28px">
  <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#6e6e73;letter-spacing:.5px;text-transform:uppercase">Votre créneau</p>
  <p style="font-size:20px;font-weight:700;color:#1d1d1f;margin:0 0 4px">$date</p>
  <p style="font-size:20px;font-weight:700;color:#0071e3;margin:0">$heure</p>
</div>
<div style="background:#fff3cd;border-radius:10px;padding:16px;margin-bottom:28px">
  <p style="margin:0;font-size:14px;color:#856404">⏳ <strong>En attente de confirmation</strong> — Nous vous confirmons votre rendez-vous sous 1 heure.</p>
</div>
<p style="font-size:14px;color:#6e6e73">Prix : <strong style="color:#1d1d1f">179 € TTC</strong> (paiement uniquement le jour de l'installation, si satisfait).</p>
<p style="font-size:13px;color:#aeaeb2;margin-top:24px">
  Pour annuler : <a href="$cancel_url" style="color:#ff3b30;text-decoration:none">Annuler ma réservation</a>
</p>
HTML;

    return SMTPMailer::send($a['email'], $a['name'], 'Votre demande Portaye est enregistrée', email_wrap('Demande reçue', $content));
}

function mail_client_confirmed(array $a): bool {
    $date  = format_date_fr($a['appt_date']);
    $heure = substr($a['appt_time'], 0, 5);
    $name  = htmlspecialchars($a['name']);
    $cancel_url = SITE_URL . '/api.php?a=cancel_client&token=' . urlencode($a['cancel_token']);

    $content = <<<HTML
<h2 style="font-size:22px;font-weight:700;color:#30d158;margin:0 0 8px">✓ Rendez-vous confirmé</h2>
<p style="color:#6e6e73;font-size:15px;margin:0 0 28px">Bonjour $name, votre installation est confirmée !</p>
<div style="background:#f5f5f7;border-radius:12px;padding:24px;margin-bottom:28px">
  <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#6e6e73;letter-spacing:.5px;text-transform:uppercase">Votre rendez-vous</p>
  <p style="font-size:20px;font-weight:700;color:#1d1d1f;margin:0 0 4px">$date</p>
  <p style="font-size:20px;font-weight:700;color:#0071e3;margin:0">$heure</p>
</div>
<div style="background:#e8fdf0;border-radius:10px;padding:16px;margin-bottom:28px">
  <p style="margin:0 0 6px;font-size:14px;color:#1d6336;font-weight:600">✓ Ce que vous devez savoir</p>
  <p style="margin:0;font-size:14px;color:#1d6336">Un technicien certifié Portaye sera chez vous à l'heure indiquée. Aucun matériel à préparer. Comptez 45–60 minutes.</p>
</div>
<p style="font-size:14px;color:#6e6e73">Prix : <strong style="color:#1d1d1f">179 € TTC</strong> — vous payez uniquement à la fin, si vous êtes satisfait.</p>
<p style="font-size:14px;color:#6e6e73">Questions ? <a href="mailto:contact@portaye.fr" style="color:#0071e3;text-decoration:none">contact@portaye.fr</a></p>
<p style="font-size:13px;color:#aeaeb2;margin-top:24px">
  Pour annuler : <a href="$cancel_url" style="color:#ff3b30;text-decoration:none">Annuler ma réservation</a>
</p>
HTML;

    return SMTPMailer::send($a['email'], $a['name'], '✓ Installation Portaye confirmée — ' . $date . ' à ' . $heure, email_wrap('Confirmé', $content));
}

function mail_client_cancelled(array $a): bool {
    $date  = format_date_fr($a['appt_date']);
    $heure = substr($a['appt_time'], 0, 5);
    $name  = htmlspecialchars($a['name']);
    $book_url = SITE_URL . '/#booking';

    $content = <<<HTML
<h2 style="font-size:22px;font-weight:700;color:#ff3b30;margin:0 0 8px">Rendez-vous annulé</h2>
<p style="color:#6e6e73;font-size:15px;margin:0 0 28px">Bonjour $name, votre rendez-vous du $date à $heure a été annulé.</p>
<p style="font-size:15px;color:#6e6e73">Vous pouvez réserver un nouveau créneau à tout moment :</p>
<p style="margin-top:20px;text-align:center">
  <a href="$book_url" style="background:#0071e3;color:#fff;text-decoration:none;padding:12px 28px;border-radius:980px;font-size:15px;display:inline-block">Réserver à nouveau</a>
</p>
HTML;

    return SMTPMailer::send($a['email'], $a['name'], 'Votre rendez-vous Portaye a été annulé', email_wrap('Annulation', $content));
}

function mail_admin_new_booking(array $a): bool {
    $date    = format_date_fr($a['appt_date']);
    $heure   = substr($a['appt_time'], 0, 5);
    $name    = htmlspecialchars($a['name']);
    $email   = htmlspecialchars($a['email']);
    $phone   = htmlspecialchars($a['phone'] ?? '—');
    $city    = htmlspecialchars($a['city'] ?? '—');
    $door    = htmlspecialchars($a['door_type'] ?? '—');
    $admin_url = SITE_URL . '/admin/';

    $content = <<<HTML
<h2 style="font-size:22px;font-weight:700;color:#1d1d1f;margin:0 0 8px">🔔 Nouvelle réservation</h2>
<p style="color:#6e6e73;font-size:15px;margin:0 0 28px">Un client vient de réserver un créneau d'installation.</p>
<div style="background:#f5f5f7;border-radius:12px;padding:24px;margin-bottom:24px">
  <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#6e6e73;letter-spacing:.5px;text-transform:uppercase">Créneau</p>
  <p style="font-size:20px;font-weight:700;color:#1d1d1f;margin:0 0 4px">$date</p>
  <p style="font-size:20px;font-weight:700;color:#0071e3;margin:0">$heure</p>
</div>
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
  <tr><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#6e6e73;width:120px">Nom</td><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#1d1d1f;font-weight:500">$name</td></tr>
  <tr><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#6e6e73">Email</td><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#1d1d1f">$email</td></tr>
  <tr><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#6e6e73">Téléphone</td><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#1d1d1f">$phone</td></tr>
  <tr><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#6e6e73">Ville</td><td style="padding:10px 0;border-bottom:1px solid #e5e5ea;font-size:14px;color:#1d1d1f">$city</td></tr>
  <tr><td style="padding:10px 0;font-size:14px;color:#6e6e73">Type porte</td><td style="padding:10px 0;font-size:14px;color:#1d1d1f">$door</td></tr>
</table>
<p style="margin-top:28px;text-align:center">
  <a href="$admin_url" style="background:#0071e3;color:#fff;text-decoration:none;padding:12px 28px;border-radius:980px;font-size:15px;display:inline-block">Gérer dans l'admin</a>
</p>
HTML;

    return SMTPMailer::send(ADMIN_EMAIL, 'Admin Portaye', '🔔 Nouvelle réservation — ' . $name . ' · ' . $date, email_wrap('Nouvelle réservation', $content));
}
