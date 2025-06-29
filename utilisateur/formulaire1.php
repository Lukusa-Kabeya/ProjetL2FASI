<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: connexionutilisateur.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header("Location: connexionutilisateur.php");
    exit;
}

$services = $pdo->query("SELECT * FROM service ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = (int)($_POST['service'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!$service_id) {
        $error = "Veuillez choisir un service.";
    } else {
        $date = date('Y-m-d');
        $heure_debut = new DateTime('09:00:00');
        $attribue = false;

        while (!$attribue) {
            $stmt = $pdo->prepare("SELECT * FROM docteur WHERE service_id = ?");
            $stmt->execute([$service_id]);
            $docteurs = $stmt->fetchAll();

            foreach ($docteurs as $doc) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rendez_vous WHERE docteur_id = ? AND date_rdv = ?");
                $stmt->execute([$doc['id'], $date]);
                $rdv_count = $stmt->fetchColumn();

                if ($rdv_count < 4) {
                    $heure_rdv = clone $heure_debut;
                    $heure_rdv->modify('+' . ($rdv_count * 2) . ' hours');
                    $heure_str = $heure_rdv->format('H:i:s');

                    $stmt = $pdo->prepare("INSERT INTO rendez_vous (utilisateur_id, date_rdv, heure_rdv, service_id, docteur_id, message, statut)
                                           VALUES (?, ?, ?, ?, ?, ?, 'En attente')");
                    $stmt->execute([$_SESSION['user_id'], $date, $heure_str, $service_id, $doc['id'], $message]);

                    $success = "✅ Rendez-vous enregistré le $date à " . substr($heure_str, 0, 5) . " avec le Dr " . $doc['nom'];
                    $attribue = true;
                    break;
                }
            }

            if (!$attribue) {
                $date = date('Y-m-d', strtotime($date . ' +1 day'));
            }
        }
    }
}

// Récupération des RDV de l'utilisateur
$stmt = $pdo->prepare("
    SELECT r.date_rdv, r.heure_rdv, s.nom AS service, d.nom AS docteur, r.message, r.statut
    FROM rendez_vous r
    JOIN service s ON r.service_id = s.id
    JOIN docteur d ON r.docteur_id = d.id
    WHERE r.utilisateur_id = ?
    ORDER BY r.date_rdv, r.heure_rdv
");
$stmt->execute([$_SESSION['user_id']]);
$rendezvous = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Prise de rendez-vous</title>
<link rel="stylesheet" href="formulaire.css">
</head>
<body>
<h2 style="text-align:center">
    Bonjour <?= htmlspecialchars($user['prenom']) ?>, prends rendez-vous 🎯
    <a href="deconnexion.php" style="float:right; font-size:16px; color:#e74c3c; text-decoration:none;">🚪 Déconnexion</a>
</h2>


<?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post">
  <label>Nom</label>
  <input type="text" value="<?= htmlspecialchars($user['nom']) ?>" readonly>
  <label>Prénom</label>
  <input type="text" value="<?= htmlspecialchars($user['prenom']) ?>" readonly>
  <label>Email</label>
  <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
  <label>Service</label>
  <select name="service" required>
    <option value="">-- Choisir un service --</option>
    <?php foreach ($services as $s): ?>
      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
    <?php endforeach; ?>
  </select>
  <label>Message (symptômes)</label>
  <textarea name="message" rows="4"></textarea>
  <button type="submit">Prendre rendez-vous</button>
</form>

<?php if ($rendezvous): ?>
<table>
  <tr><th>Date</th><th>Heure</th><th>Service</th><th>Docteur</th><th>Message</th><th>Statut</th></tr>
  <?php foreach($rendezvous as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['date_rdv']) ?></td>
    <td><?= substr(htmlspecialchars($r['heure_rdv']), 0, 5) ?></td>
    <td><?= htmlspecialchars($r['service']) ?></td>
    <td><?= htmlspecialchars($r['docteur']) ?></td>
    <td><?= nl2br(htmlspecialchars($r['message'])) ?></td>
    <td><?= htmlspecialchars($r['statut']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center;">Aucun rendez-vous enregistré.</p>
<?php endif; ?>
</body>
</html>
