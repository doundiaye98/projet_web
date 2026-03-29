# Book Club Project

Une plateforme web pour gérer un club de lecture, suivre ses progressions et partager des avis sur des livres.

## 🚀 Installation & Setup

Ce projet est entièrement conteneurisé avec Docker pour faciliter le déploiement.

### 1. Pré-requis
- Docker et Docker Compose installés sur votre machine.

### 2. Configuration (.env)
Créez un fichier `.env` à la racine du projet (copiez les valeurs ci-dessous) :

```env
# Configuration de la base de données
DB_HOST=db
DB_NAME=projet_web
DB_USER=root
DB_PASS=root

# Configuration Docker
MYSQL_ROOT_PASSWORD=root
PORT_WEB=8080
PORT_DB=3307
```

### 3. Lancement
Exécutez la commande suivante pour construire et lancer les conteneurs :

```bash
docker compose up -d --build
```

Le site sera accessible à l'adresse : [http://localhost:8080](http://localhost:8080)

## 🔑 Accès Administrateur
Un compte administrateur est créé automatiquement lors du premier lancement :
- **Email** : `admin@club.test`
- **Mot de passe** : `admin123`

## 📂 Structure du Projet
- `modules/` : Logique métier (Auth, Livres, Sessions, etc.)
- `includes/` : Composants réutilisables (Header, Nav, Helpers)
- `database/` : Scripts SQL et initialisation
- `config/` : Configuration de la base de données
