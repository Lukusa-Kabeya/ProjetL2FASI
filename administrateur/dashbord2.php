<?php
session_start();

if (!isset($_SESSION['Nom'])) {
    header("Location: connexionadmin.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$page = $_GET['page'] ?? 'accueil';

$services = $pdo->query("SELECT id, nom FROM service ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$docteurs = $pdo->query("SELECT id, nom FROM docteur ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$utilisateurs = $pdo->query("SELECT id, nom, prenom FROM utilisateurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$filter_service = $_GET['service'] ?? '';
$filter_docteur = $_GET['docteur'] ?? '';
$filter_user = $_GET['utilisateur'] ?? '';

$where = [];
$params = [];

if ($filter_service !== '') {
    $where[] = "r.service_id = ?";
    $params[] = $filter_service;
}
if ($filter_docteur !== '') {
    $where[] = "r.docteur_id = ?";
    $params[] = $filter_docteur;
}
if ($filter_user !== '') {
    $where[] = "r.utilisateur_id = ?";
    $params[] = $filter_user;
}

$whereSql = $where ? "AND " . implode(" AND ", $where) : "";

function getRDV($pdo, $start, $end, $whereSql, $params) {
    $sql = "SELECT r.*, s.nom AS service_nom, d.nom AS docteur_nom, u.nom AS utilisateur_nom, u.prenom
            FROM rendez_vous r
            LEFT JOIN service s ON r.service_id = s.id
            LEFT JOIN docteur d ON r.docteur_id = d.id
            LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
            WHERE r.date_rdv BETWEEN ? AND ? $whereSql
            ORDER BY r.date_rdv, r.heure_rdv";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$start, $end], $params));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$today = date('Y-m-d');
$startWeek = date('Y-m-d', strtotime("monday this week"));
$endWeek = date('Y-m-d', strtotime("sunday this week"));
$startMonth = date('Y-m-01');
$endMonth = date('Y-m-t');

$rdv_today = getRDV($pdo, $today, $today, $whereSql, $params);
$rdv_week = getRDV($pdo, $startWeek, $endWeek, $whereSql, $params);
$rdv_month = getRDV($pdo, $startMonth, $endMonth, $whereSql, $params);
$rdv_all = getRDV($pdo, '1000-01-01', '9999-12-31', $whereSql, $params);

$sqlGraph = "SELECT s.nom, COUNT(*) AS total FROM rendez_vous r
             LEFT JOIN service s ON r.service_id = s.id
             WHERE 1=1 $whereSql
             GROUP BY s.nom ORDER BY total DESC";
$stmtGraph = $pdo->prepare($sqlGraph);
$stmtGraph->execute($params);
$graphData = $stmtGraph->fetchAll(PDO::FETCH_ASSOC);
$labels = array_column($graphData, 'nom');
$counts = array_column($graphData, 'total');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="?page=accueil" class="<?= $page == 'accueil' ? 'active' : '' ?>">Accueil</a>
        <a href="?page=docteurs" class="<?= $page == 'docteurs' ? 'active' : '' ?>">Docteurs</a>
        <a href="?page=rendezvous" class="<?= $page == 'rendezvous' ? 'active' : '' ?>">Rendez-vous</a>
        <a href="?page=patients" class="<?= $page == 'patients' ? 'active' : '' ?>">Patients</a>
        <a href="deconnexion.php">Déconnexion</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION['Nom']) ?></h1>
        </div>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="message"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if ($page == 'accueil'): ?>
            <div class="card-container">
                <div class="card"><h2><?= count($rdv_today) ?></h2><p>Rendez-vous aujourd'hui</p></div>
                <div class="card"><h2><?= count($rdv_week) ?></h2><p>Cette semaine</p></div>
                <div class="card"><h2><?= count($rdv_month) ?></h2><p>Ce mois</p></div>
                <div class="card"><h2><?= count($rdv_all) ?></h2><p>Total</p></div>
            </div>

            <form method="get" class="filters">
                <input type="hidden" name="page" value="accueil">
                <select name="service">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filter_service == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="docteur">
                    <option value="">Tous les docteurs</option>
                    <?php foreach ($docteurs as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filter_docteur == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="utilisateur">
                    <option value="">Tous les utilisateurs</option>
                    <?php foreach ($utilisateurs as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filter_user == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filtrer</button>
            </form>

            <div class="chart-container">
                <canvas id="chartRDV"></canvas>
            </div>

        <?php elseif ($page == 'docteurs'): ?>
            <h2>Liste des rendez-vous par docteur</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Docteur</th>
                        <th>Service</th>
                        <th>Patient</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT r.date_rdv, r.heure_rdv, d.nom AS docteur_nom, s.nom AS service_nom, u.prenom, u.nom AS utilisateur_nom, r.statut
                                         FROM rendez_vous r
                                         LEFT JOIN docteur d ON r.docteur_id = d.id
                                         LEFT JOIN service s ON r.service_id = s.id
                                         LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
                                         ORDER BY d.nom, r.date_rdv, r.heure_rdv");
                    $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($rdvs) {
                        foreach ($rdvs as $rdv) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($rdv['date_rdv']) . "</td>";
                            echo "<td>" . htmlspecialchars($rdv['heure_rdv']) . "</td>";
                            echo "<td>" . htmlspecialchars($rdv['docteur_nom']) . "</td>";
                            echo "<td>" . htmlspecialchars($rdv['service_nom']) . "</td>";
                            echo "<td>" . htmlspecialchars($rdv['prenom'] . ' ' . $rdv['utilisateur_nom']) . "</td>";
                            echo "<td>" . htmlspecialchars($rdv['statut'] ?? 'En attente') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>Aucun rendez-vous trouvé.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        <?php elseif ($page == 'rendezvous'): ?>
            <h2>Tous les rendez-vous</h2>
            <?php
            $stmt = $pdo->query("SELECT r.id, r.date_rdv, r.heure_rdv, s.nom AS service_nom, d.nom AS docteur_nom, u.prenom, u.nom AS utilisateur_nom, r.statut
                                 FROM rendez_vous r
                                 LEFT JOIN service s ON r.service_id = s.id
                                 LEFT JOIN docteur d ON r.docteur_id = d.id
                                 LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
                                 ORDER BY r.date_rdv DESC, r.heure_rdv DESC");
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rdvs):
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Service</th>
                        <th>Docteur</th>
                        <th>Patient</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rdvs as $rdv): ?>
                    <tr>
                        <td><?= htmlspecialchars($rdv['date_rdv']) ?></td>
                        <td><?= htmlspecialchars($rdv['heure_rdv']) ?></td>
                        <td><?= htmlspecialchars($rdv['service_nom']) ?></td>
                        <td><?= htmlspecialchars($rdv['docteur_nom']) ?></td>
                        <td><?= htmlspecialchars($rdv['prenom'] . ' ' . $rdv['utilisateur_nom']) ?></td>
                        <td>
                            <form method="post" action="valider_rdv.php" style="display:inline-block; margin:0;">
                                <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                <select name="statut" required>
                                    <option value="en attente" <?= ($rdv['statut'] == 'en attente' ? 'selected' : '') ?>>En attente</option>
                                    <option value="confirmé" <?= ($rdv['statut'] == 'confirmé' ? 'selected' : '') ?>>Confirmé</option>
                                    <option value="annulé" <?= ($rdv['statut'] == 'annulé' ? 'selected' : '') ?>>Annulé</option>
                                </select>
                        </td>
                        <td>
                                <button type="submit">Mettre à jour</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Aucun rendez-vous trouvé.</p>
            <?php endif; ?>

        <?php elseif ($page == 'patients'): ?>
            <h2>Liste des patients</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, nom, prenom, email, telephone FROM utilisateurs ORDER BY nom, prenom");
                    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($patients) {
                        foreach ($patients as $patient) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($patient['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($patient['nom']) . "</td>";
                            echo "<td>" . htmlspecialchars($patient['prenom']) . "</td>";
                            echo "<td>" . htmlspecialchars($patient['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($patient['telephone']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>Aucun patient trouvé.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <script>
        new Chart(document.getElementById('chartRDV'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Répartition des rendez-vous par service'
                    }
                }
            }
        });
    </script>
</body>
</html>
