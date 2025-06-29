<?php
session_start();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $Nom = $_POST['Nom'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE Nom = ?");
    $stmt->execute([$Nom]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['Nom'] = $admin['Nom'];
        header("Location: dashbord2.php");
        exit;
    } else {
        $erreur = "Nom ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body { font-family: sans-serif; background: #eaeaea; padding: 50px; }
        .form-container {
            width: 400px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #aaa;
        }
        input { width: 100%; padding: 10px; margin-bottom: 15px; }
        button { padding: 10px 20px; background: blue; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Connexion</h2>
        <?php if ($erreur): ?>
            <div class="error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <form method="post"  autocomplete="off">
            <input type="text" name="Nom" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
