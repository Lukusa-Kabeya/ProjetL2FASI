<?php
session_start();

if (!isset($_SESSION['Nom'])) {
    header("Location: connexionadmin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['rdv_id']) && isset($_POST['statut'])) {
        $rdv_id = (int)$_POST['rdv_id'];
        $statut = $_POST['statut'];
        $allowed_statuts = ['en attente', 'confirmé', 'annulé'];

        if (in_array($statut, $allowed_statuts)) {
            try {
                $pdo = new PDO("mysql:host=localhost;dbname=prise_rdv;charset=utf8mb4", "root", "");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE id = ?");
                $stmt->execute([$statut, $rdv_id]);

                $_SESSION['message'] = "Statut mis à jour avec succès.";
            } catch (PDOException $e) {
                $_SESSION['message'] = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        } else {
            $_SESSION['message'] = "Statut invalide.";
        }
    } else {
        $_SESSION['message'] = "Données manquantes.";
    }
} else {
    $_SESSION['message'] = "Méthode non autorisée.";
}

header("Location: dashbord2.php?page=rendezvous");
exit;
