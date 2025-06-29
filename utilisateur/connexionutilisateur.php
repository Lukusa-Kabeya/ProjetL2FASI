<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$nom || !$password) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id, mot_de_passe FROM utilisateurs WHERE nom = ?");
        $stmt->execute([$nom]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: formulaire1.php");
            exit;
        } else {
            $error = "Nom ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion</title>
<link rel="stylesheet" href="connexion.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="row">
            <div class="col-7">
                <div class="logo">
                <a href="accueil.html">
                    <img src="logo21.jpg" alt="Logo Clinique de la Vie" />
                </a>
                </div>
                <nav class="menu">
                <ul>
                    <li><a href="accueil.html">Accueil</a></li>
                    <li><a href="service .html">Service</a></li>
                    <li><a href="connexionutilisateur.php">Rendez-vous</a></li>
                </ul>
                </nav>
            </div>
            </div>
        </div>
    </header><br><br>
    <div class="form-container">
        <h2>Connexion</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="nom" placeholder="Nom d’utilisateur &#x1F464;" required>
            <input type="password" name="password" placeholder="Mot de passe &#x1F512;" required>
            <button type="submit">Se connecter &#x27A4;</button>
        </form>
        <p>Pas encore de compte ? <a href="enregistrementutilisateur.php">Créer un compte</a></p>
    </div><br><br>
    <footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>Clinique de la Vie</h3>
            <p>Nous mettons notre expertise médicale à votre disposition pour garantir votre santé et votre bien-être.</p>
        </div>
        <div class="footer-section">
            <h3>Navigation</h3>
            <ul>
                <li><a href="accueil.html">Accueil</a></li>
                <li><a href="service .html">Services</a></li>
                <li><a href="connexionutilisateur.php">Rendez-vous</a></li>
                <li><a href="Apropos.html">À propos</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Contact</h3>
            <ul>
                <li>Email : contact@cliniquedelavie.com</li>
                <li>Tél : +243 000000000</li>
                <li>Adresse : Kinshasa, RD Congo</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2025 Clinique de la Vie. Tous droits réservés.
    </div>
</footer>

</body>
</html>
