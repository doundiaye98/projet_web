#!/bin/bash
set -e

# Attendre que MySQL soit prêt ET que les tables soient créées par le script SQL
echo "Attente de la base de données... ($DB_NAME)"
until php -r "
    \$mysqli = @new mysqli(getenv('DB_HOST') ?: 'db', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: 'root');
    if (\$mysqli->connect_error) exit(1);
    \$res = \$mysqli->query('SHOW DATABASES LIKE \"' . (getenv('DB_NAME') ?: 'projet_web') . '\"');
    if (!\$res || \$res->num_rows === 0) exit(1);
    \$mysqli->select_db(getenv('DB_NAME') ?: 'projet_web');
    \$res = \$mysqli->query('SHOW TABLES LIKE \"users\"');
    if (!\$res || \$res->num_rows === 0) exit(1);
" > /dev/null 2>&1; do
    echo "Base de données ou tables non prêtes, on patiente (2s)..."
    sleep 2
done

echo "Base de données et tables prêtes ! Lancement du script d'initialisation..."
php database/script.php

echo "Lancement d'Apache..."
exec apache2-foreground
