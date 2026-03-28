<?php
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/db.php';

$nom = 'Admin';
$email = 'admin@club.test';
$password = 'admin123';
$role = 'admin';
$statut = 'actif';

$hashed = password_hash($password, PASSWORD_DEFAULT);

// Vérifier si l'admin existe déjà
$sql_check = "SELECT id FROM users WHERE email = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    $sql = "INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssss", $nom, $email, $hashed, $role, $statut);
    if ($stmt->execute()) {
        echo "Admin ajouté avec succès";
    } else {
        echo "Erreur lors de l'ajout de l'admin : " . $stmt->error;
    }
    $stmt->close();
} else {
    // Si l'admin existe déjà, on met à jour le mot de passe (utile si on a changé le script)
    $sql_update = "UPDATE users SET nom = ?, password_hash = ?, role = ?, statut = ? WHERE email = ?";
    $stmt = $mysqli->prepare($sql_update);
    $stmt->bind_param("sssss", $nom, $hashed, $role, $statut, $email);
    if ($stmt->execute()) {
        echo "Admin mis a jour avec succes";
    } else {
        echo "Erreur lors de la mise a jour de l'admin : " . $stmt->error;
    }
    $stmt->close();
}
$stmt_check->close();
$mysqli->close();
