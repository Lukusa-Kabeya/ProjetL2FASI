<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialiser $errors pour éviter les warnings
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $postnom = trim($_POST['postnom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $sexe = $_POST['sexe'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$nom || !$prenom || !$email || !$password) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs 
                (nom, postnom, prenom, sexe, age, telephone, email, mot_de_passe)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $postnom, $prenom, $sexe, $age ?: null, $telephone, $email, $hash]);

            echo "<p class='success'>✅ Inscription réussie ! Redirection vers la connexion...</p>";
            echo "<script>setTimeout(function() { window.location.href = 'connexionutilisateur.php'; }, 2000);</script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Utilisateur</title>
    <link rel="stylesheet" href="enregistrement.css">
</head>
<body>

<div class="form-container">
    <h2>📝 Créer un compte</h2>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" autocomplete="off">
        <label>Nom* <input type="text" name="nom" required></label>
        <label>Postnom <input type="text" name="postnom"></label>
        <label>Prénom* <input type="text" name="prenom" required></label>

        <label>Sexe
            <select name="sexe">
                <option value="">--</option>
                <option value="Masculin">Masculin</option>
                <option value="Féminin">Féminin</option>
            </select>
        </label>

        <label>Âge <input type="number" name="age" min="0" max="120"></label>
        <label>Téléphone <input type="tel" name="telephone"></label>
        <label>Email* <input type="email" name="email" required></label>
        <label>Mot de passe* <input type="password" name="password" required></label>
        <label>Confirmer mot de passe* <input type="password" name="password_confirm" required></label>

        <button type="submit">✅ S'inscrire</button>
    </form>

    <div class="login-link">
        Déjà un compte ? <a href="connexionutilisateur.php">Se connecter</a>
    </div>
</div>

</body>
</html>
