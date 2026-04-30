<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php include 'includes/modern-light-style.php'; ?>
    <style>
        body { padding: 2rem 0; }
        .container { max-width: 900px; }
        .btn-back { margin-bottom: 2rem; text-decoration: none; }
        h3 { font-size: 1.1rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="web_oberflaeche.php" class="btn-back">← Zurück</a>
        
        <h1>Impressum</h1>
        
        <h2>Angaben gemäß § 5 TMG</h2>
        <p>
            <strong>[Firmenname]</strong><br>
            [Straße und Hausnummer]<br>
            [PLZ und Ort]<br>
        </p>
        
        <h2>Vertreten durch</h2>
        <p>[Name des Geschäftsführers/Inhabers]</p>
        
        <h2>Kontakt</h2>
        <p>
            Telefon: [Telefonnummer]<br>
            E-Mail: [E-Mail-Adresse]
        </p>
        
        <h2>Registereintrag</h2>
        <p>
            Eintragung im Handelsregister<br>
            Registergericht: [Gericht]<br>
            Registernummer: [Nummer]
        </p>
        
        <h2>Umsatzsteuer-ID</h2>
        <p>
            Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG:<br>
            [USt-IdNr.]
        </p>
        
        <h2>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
        <p>
            [Name]<br>
            [Adresse]
        </p>
        
        <h2>Haftungsausschluss</h2>
        
        <h3>Haftung für Inhalte</h3>
        <p>Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen.</p>
        
        <h3>Haftung für Links</h3>
        <p>Unser Angebot enthält Links zu externen Webseiten Dritter, auf deren Inhalte wir keinen Einfluss haben. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>
        
        <h3>Urheberrecht</h3>
        <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht.</p>
        
        <div style="margin-top: 3rem; padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
            <p><strong>Hinweis:</strong> Bitte passen Sie die Platzhalter [...] mit Ihren tatsächlichen Unternehmensdaten an.</p>
        </div>
    </div>
</body>
</html>