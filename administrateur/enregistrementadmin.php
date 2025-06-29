<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $Nom = $_POST['Nom'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO administrateur (Nom, password) VALUES (?, ?)");
    $stmt->execute([$Nom, $password]);

    echo "<p style='color: green;'>Administrateur enregistré avec succès. <a href='connexionadmin.php'>Se connecter</a></p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Enregistrement Administrateur</title>
    <style>
        body { font-family: sans-serif; background: #f1f1f1; padding: 50px; }
        .form-container {
            width: 400px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        input { width: 100%; padding: 10px; margin-bottom: 15px; }
        button { padding: 10px 20px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Créer un compte administrateur</h2>
    <form method="POST">
        <input type="text" name="Nom" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Enregistrer</button>
    </form>
</div>
</body>
</html>
