<?php

function document_template_defaults(): array
{
    return [
        'angebot' => [
            'name' => 'Standard Angebot',
            'header_title' => 'ANGEBOT',
            'firmenname' => 'Ihre Firma GmbH',
            'firmenadresse' => "Musterstraße 123\n12345 Musterstadt\nDeutschland",
            'intro_text' => "Sehr geehrte/r {{kunde_name}},\n\nvielen Dank für Ihre Anfrage. Gerne bieten wir Ihnen folgende Positionen an.",
            'payment_info' => '',
            'footer_text' => "Vielen Dank für Ihr Interesse.\nMit freundlichen Grüßen\nIhr Team",
            'primary_color' => '#5B7DB1',
            'accent_color' => '#D4AF37',
        ],
        'bestellung' => [
            'name' => 'Standard Bestellbestätigung',
            'header_title' => 'BESTELLBESTÄTIGUNG',
            'firmenname' => 'Ihre Firma GmbH',
            'firmenadresse' => "Musterstraße 123\n12345 Musterstadt\nDeutschland",
            'intro_text' => "Sehr geehrte/r {{kunde_name}},\n\nhiermit bestätigen wir Ihre Bestellung {{dokument_nummer}}.",
            'payment_info' => '',
            'footer_text' => "Vielen Dank für Ihre Bestellung.\nMit freundlichen Grüßen\nIhr Team",
            'primary_color' => '#5B7DB1',
            'accent_color' => '#D4AF37',
        ],
        'rechnung' => [
            'name' => 'Standard Rechnung',
            'header_title' => 'RECHNUNG',
            'firmenname' => 'Ihre Firma GmbH',
            'firmenadresse' => "Musterstraße 123\n12345 Musterstadt\nDeutschland\nTel: +49 123 456789\nE-Mail: info@firma.de",
            'intro_text' => "Sehr geehrte/r {{kunde_name}},\n\nvielen Dank für Ihre Bestellung. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:",
            'payment_info' => "Zahlungsziel: {{faelligkeit}}\nIBAN: DE89 3704 0044 0532 0130 00\nBIC: COBADEFFXXX\nBank: Commerzbank AG\nVerwendungszweck: {{dokument_nummer}}",
            'footer_text' => "Geschäftsführer: Max Mustermann | Handelsregister: HRB 12345 | USt-IdNr.: DE123456789\nBankverbindung: IBAN DE89 3704 0044 0532 0130 00 | BIC COBADEFFXXX",
            'primary_color' => '#5B7DB1',
            'accent_color' => '#D4AF37',
        ],
        'lieferschein' => [
            'name' => 'Standard Lieferschein',
            'header_title' => 'LIEFERSCHEIN',
            'firmenname' => 'Ihre Firma GmbH',
            'firmenadresse' => "Musterstraße 123\n12345 Musterstadt\nDeutschland",
            'intro_text' => "Lieferschein zu Vorgang {{dokument_nummer}}.",
            'payment_info' => '',
            'footer_text' => "Vielen Dank für Ihr Vertrauen!",
            'primary_color' => '#5B7DB1',
            'accent_color' => '#D4AF37',
        ],
    ];
}

function ensure_document_template_exists(PDO $PDO, string $type): void
{
    $defaults = document_template_defaults();
    if (!isset($defaults[$type])) {
        return;
    }

    $stmt = $PDO->prepare("SELECT id FROM dokument_vorlagen WHERE dokument_typ = ?");
    $stmt->execute([$type]);
    if ($stmt->fetch()) {
        return;
    }

    $template = $defaults[$type];
    $stmt = $PDO->prepare("
        INSERT INTO dokument_vorlagen
        (dokument_typ, name, header_title, firmenname, firmenadresse, intro_text, payment_info, footer_text, primary_color, accent_color, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $type,
        $template['name'],
        $template['header_title'],
        $template['firmenname'],
        $template['firmenadresse'],
        $template['intro_text'],
        $template['payment_info'],
        $template['footer_text'],
        $template['primary_color'],
        $template['accent_color'],
    ]);
}

function get_document_template(PDO $PDO, string $type): array
{
    $defaults = document_template_defaults();
    if (!isset($defaults[$type])) {
        return [];
    }

    ensure_document_template_exists($PDO, $type);

    $stmt = $PDO->prepare("SELECT * FROM dokument_vorlagen WHERE dokument_typ = ?");
    $stmt->execute([$type]);
    $stored = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return array_merge($defaults[$type], $stored);
}

function save_document_template(PDO $PDO, string $type, array $data): void
{
    ensure_document_template_exists($PDO, $type);

    $stmt = $PDO->prepare("
        UPDATE dokument_vorlagen
        SET name = ?, header_title = ?, firmenname = ?, firmenadresse = ?, intro_text = ?, payment_info = ?, footer_text = ?, primary_color = ?, accent_color = ?, updated_at = CURRENT_TIMESTAMP
        WHERE dokument_typ = ?
    ");

    $stmt->execute([
        $data['name'],
        $data['header_title'],
        $data['firmenname'],
        $data['firmenadresse'],
        $data['intro_text'],
        $data['payment_info'],
        $data['footer_text'],
        $data['primary_color'],
        $data['accent_color'],
        $type,
    ]);
}

function render_document_template_text(string $text, array $variables): string
{
    $replacements = [];
    foreach ($variables as $key => $value) {
        $replacements['{{' . $key . '}}'] = (string) $value;
    }

    return strtr($text, $replacements);
}

function render_document_template_html(string $text, array $variables): string
{
    return nl2br(htmlspecialchars(render_document_template_text($text, $variables)));
}
