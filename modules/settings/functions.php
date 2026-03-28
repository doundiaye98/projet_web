<?php

/**
 * Met à jour le mot de passe d'un utilisateur
 */
function updatePassword($mysqli, $userId, $currentPassword, $newPassword) {
    $sql = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        return 'Mot de passe actuel incorrect.';
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $newHash, $userId);
    return $stmt->execute() ? true : 'Erreur lors de la mise à jour du mot de passe.';
}

/**
 * Met à jour le nom et l'email d'un utilisateur
 */
function updateProfile($mysqli, $userId, $nom, $email, $avatarPath = null) {
    if ($avatarPath !== null) {
        $sql = "UPDATE users SET nom = ?, email = ?, avatar_path = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssi", $nom, $email, $avatarPath, $userId);
    } else {
        $sql = "UPDATE users SET nom = ?, email = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssi", $nom, $email, $userId);
    }
    return $stmt->execute() ? true : 'Erreur lors de la mise à jour du profil.';
}

/**
 * Vérifie si un email est déjà utilisé par un autre utilisateur
 */
function isEmailTaken($mysqli, $email, $excludeUserId) {
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $email, $excludeUserId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

/**
 * Récupère les informations d'un utilisateur par son ID
 */
function getUserById($mysqli, $userId) {
    $sql = "SELECT id, nom, email, avatar_path, role, statut, created_at, is_system FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
