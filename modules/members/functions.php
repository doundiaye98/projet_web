<?php

/**
 * Récupère tous les utilisateurs de la base de données
 */
function getAllUsers($mysqli) {
    $sql = "SELECT id, nom, email, role, statut, is_system FROM users ORDER BY nom ASC";
    $result = $mysqli->query($sql);

    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

/**
 * Vérifie si un utilisateur est un compte système (protégé)
 */
function isUserSystem($mysqli, $userId) {
    $sql = "SELECT is_system FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    return $user && (int)$user['is_system'] === 1;
}

/**
 * Met à jour le rôle d'un utilisateur
 */
function updateUserRole($mysqli, $userId, $role) {
    $allowedRoles = ['membre', 'moderateur', 'admin'];
    if (!in_array($role, $allowedRoles)) {
        return false;
    }
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $role, $userId);
    return $stmt->execute();
}

/**
 * Change le statut d'un utilisateur (actif/banni)
 */
function toggleUserStatut($mysqli, $userId, $newStatut) {
    $allowedStatuts = ['actif', 'banni'];
    if (!in_array($newStatut, $allowedStatuts)) {
        return false;
    }
    $sql = "UPDATE users SET statut = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $newStatut, $userId);
    return $stmt->execute();
}

/**
 * Supprime un utilisateur de la base de données
 */
function deleteUser($mysqli, $userId) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Crée un nouvel utilisateur avec un rôle spécifique
 */
function createUser($mysqli, $nom, $email, $password, $role = 'membre') {
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        return "Cet email est déjà utilisé.";
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, ?, 'actif')";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssss", $nom, $email, $password_hash, $role);

    if ($stmt->execute()) {
        return true;
    } else {
        return "Erreur lors de la création de l'utilisateur : " . $stmt->error;
    }
}
