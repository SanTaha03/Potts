#!/bin/bash

# Arrêter en cas d'erreur
set -e

echo "🚀 Début du déploiement..."

# 1. Build de l'image (si vous testez en local, sinon c'est fait par la CI)
# docker-compose build

# 2. Lancer les conteneurs
docker-compose up -d --remove-orphans
echo "✅ Conteneurs lancés."

# 3. Attendre que le conteneur app soit prêt
echo "⏳ Attente du démarrage de l'app..."
sleep 5

# 4. Commandes Artisan critiques
echo "🔧 Exécution des commandes Laravel..."

# Générer la clé d'application si elle n'existe pas (utile au 1er déploiement)
docker-compose exec -T app php artisan key:generate --force --no-interaction || true

# Migrations de base de données (SQLite)
# --force est requis en prod
docker-compose exec -T app php artisan migrate --force

# Optimisation du cache (Config, Routes, Events)
docker-compose exec -T app php artisan optimize

# Cache des vues
docker-compose exec -T app php artisan view:cache

# (Optionnel) Set permissions correct for storage and cache if needed
docker-compose exec -T app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "🎉 Déploiement terminé avec succès !"
