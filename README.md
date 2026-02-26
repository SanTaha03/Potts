# POTT’S

POTT’S est une application **Laravel + Vue.js (Vite)** utilisant une base **SQLite**.  
Le projet met en place une chaîne **DevSecOps** complète : **CI** (qualité + tests + scans sécurité), **CD** (build image Docker, push registry, déploiement automatisé sur VPS), et **Monitoring** (Prometheus + Grafana).

---

## Sommaire

- [POTT’S](#potts)
  - [Sommaire](#sommaire)
  - [Objectif](#objectif)
  - [Architecture](#architecture)
    - [Composants](#composants)
    - [Réseau Docker](#réseau-docker)
  - [Prérequis](#prérequis)
    - [Local (développement)](#local-développement)
    - [Serveur (production)](#serveur-production)
  - [Démarrage en local](#démarrage-en-local)
  - [CI/CD GitHub Actions](#cicd-github-actions)
    - [1) CI — `CI - DevSecOps` (Pull Request)](#1-ci--ci---devsecops-pull-request)
    - [2) CD — `Build and Deploy` (push sur `main`)](#2-cd--build-and-deploy-push-sur-main)
      - [Secrets GitHub requis](#secrets-github-requis)
  - [Déploiement en production (VPS)](#déploiement-en-production-vps)
    - [Dossiers sur le VPS](#dossiers-sur-le-vps)
    - [`.env` production (sur le VPS)](#env-production-sur-le-vps)
    - [Docker Compose runtime (VPS)](#docker-compose-runtime-vps)
    - [Commandes de déploiement (VPS)](#commandes-de-déploiement-vps)
  - [Reverse proxy \& HTTPS (Nginx Proxy Manager)](#reverse-proxy--https-nginx-proxy-manager)
  - [Monitoring](#monitoring)
    - [Grafana](#grafana)
  - [Logs](#logs)
    - [Logs applicatifs (Laravel)](#logs-applicatifs-laravel)
  - [Rollback](#rollback)
    - [Procédure rollback (par SHA)](#procédure-rollback-par-sha)
    - [Vérifier la version déployée](#vérifier-la-version-déployée)
  - [Dossier de preuves](#dossier-de-preuves)
  - [Incidents rencontrés \& résolutions](#incidents-rencontrés--résolutions)
  - [Troubleshooting rapide](#troubleshooting-rapide)
    - [Tester l’accès depuis NPM vers POTT’S](#tester-laccès-depuis-npm-vers-potts)
    - [Forcer nettoyage caches Laravel après changement `.env`](#forcer-nettoyage-caches-laravel-après-changement-env)
    - [État des conteneurs](#état-des-conteneurs)

---

## Objectif

Mettre en place une solution DevSecOps de bout en bout :

- **CI** sur Pull Request : lint, build, tests, scans (secrets + dépendances + vulnérabilités)
- **CD** sur `main` : build image Docker, push sur registry, déploiement auto sur VPS
- **Monitoring** : dashboards (VPS + conteneurs) via Grafana
- **Documentation** : procédure, architecture, rollback, preuves (screenshots)

---

## Architecture

### Composants

- **VPS Infomaniak (Ubuntu)** — accès SSH via clé
- **Docker** + **Docker Compose**
- **GHCR (GitHub Container Registry)** : images Docker du projet
- **Nginx Proxy Manager (NPM)** : reverse proxy + certificats Let’s Encrypt
- **POTT’S** : conteneur applicatif unique (Apache + PHP 8.2)
- **SQLite** : base persistée sur le VPS (fichier `.sqlite`)
- **Monitoring** : Prometheus + Grafana + cAdvisor + node-exporter

### Réseau Docker

Tous les services exposés via NPM partagent un réseau Docker externe :

- `public` (external network)

Cela permet à NPM de forward vers les conteneurs par leur nom (ex: `potts`, `grafana`).

---

## Prérequis

### Local (développement)
- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (via PHP)
- (optionnel) Docker

### Serveur (production)
- Docker + Docker Compose
- Accès SSH par clé
- Réseau Docker externe `public` (utilisé par NPM + services)

---

## Démarrage en local

> Objectif : permettre à un nouveau dev de lancer le projet rapidement.

1) Cloner le repo
```bash
git clone <repo>
cd Potts
````

2. Installer dépendances back + front

```bash
composer install
npm ci
```

3. Préparer l’environnement

```bash
cp .env.example .env
php artisan key:generate
```

4. SQLite + migrations

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate
```

5. Assets front

* Dev:

```bash
npm run dev
```

* ou build:

```bash
npm run build
```

6. Lancer l’app

```bash
php artisan serve
```

---

## CI/CD GitHub Actions

Deux workflows principaux :

### 1) CI — `CI - DevSecOps` (Pull Request)

Déclenchement : `pull_request` vers `main` / `develop`

Contenu :

* **Qualité**

  * `./vendor/bin/pint --test`
  * `npm run build`
  * `phpunit` (avec `APP_KEY` en env pour éviter `MissingAppKeyException`)
* **Scans sécurité**

  * **Gitleaks** (détection de secrets)
  * **Composer audit** (vulnérabilités PHP)
  * **NPM audit** (report, non bloquant)
  * **Trivy filesystem scan** (OS + libs)

> Remarque : `npm audit` est laissé en mode **report** (`|| true`) pour ne pas bloquer la livraison, mais le rapport est bien généré en preuve DevSecOps.

### 2) CD — `Build and Deploy` (push sur `main`)

Déclenchement : `push` sur `main` (après merge PR)

Contenu :

* Build image Docker
* Push GHCR (tags : `latest` et `${GITHUB_SHA}`)
* Déploiement sur VPS via SSH :

  * `docker compose pull`
  * `docker compose down`
  * `docker compose up -d`

#### Secrets GitHub requis

Repo → Settings → Secrets and variables → Actions :

* `VPS_HOST`
* `VPS_USER`
* `VPS_KEY`
* (optionnel) `VPS_PORT`

---

## Déploiement en production (VPS)

### Dossiers sur le VPS

* App : `/home/ubuntu/apps/potts`
* Monitoring : `/home/ubuntu/apps/monitoring`
* NPM : `/home/ubuntu/apps/nginx-proxy-manager`

### `.env` production (sur le VPS)

Le `.env` n’est pas embarqué dans l’image Docker.

Sur le VPS :

* `/home/ubuntu/apps/potts/env/.env`

Variables importantes :

* `APP_URL=https://potts.taha.tadil.mds-nantes.fr`
* `ASSET_URL=https://potts.taha.tadil.mds-nantes.fr`
* `DB_CONNECTION=sqlite`
* `DB_DATABASE=/var/www/html/database/database.sqlite`

### Docker Compose runtime (VPS)

Fichier : `/home/ubuntu/apps/potts/docker-compose.yml`

Points clés :

* conteneur `potts` connecté au réseau `public`
* persistance `storage` (volume)
* persistance SQLite via **montage du fichier** `database.sqlite` (pas du dossier entier)

> ⚠️ Important : ne pas monter tout `/var/www/html/database`
> Sinon cela masque `database/seeders` dans l’image et rend les seeders “introuvables”.

Exemple :

```yaml
services:
  potts:
    container_name: potts
    image: ghcr.io/<GITHUB_ACCOUNT>/<GITHUB_REPO>:latest
    restart: unless-stopped
    networks:
      - public
    volumes:
      - ./env/.env:/var/www/html/.env:ro
      - potts_storage:/var/www/html/storage
      - ./data/database.sqlite:/var/www/html/database/database.sqlite

networks:
  public:
    external: true

volumes:
  potts_storage:
```

### Commandes de déploiement (VPS)

```bash
cd ~/apps/potts
docker compose pull
docker compose down
docker compose up -d
```

---

## Reverse proxy & HTTPS (Nginx Proxy Manager)

* Domaine app : `https://potts.taha.tadil.mds-nantes.fr`
* Domaine Grafana : `https://grafana.taha.tadil.mds-nantes.fr`

Configuration NPM (Proxy Host POTT’S) :

* Forward Hostname : `potts`
* Port : `80`
* SSL : Let’s Encrypt + Force SSL

---

## Monitoring

Stack monitoring : **Prometheus + Grafana + cAdvisor + node-exporter**

Déploiement :

```bash
cd ~/apps/monitoring
docker compose up -d
```

### Grafana

* URL : `https://grafana.taha.tadil.mds-nantes.fr`
* Datasource : Prometheus (`http://prometheus:9090`)
* Dashboards :

  * **Node Exporter Full** (VPS CPU/RAM/Disk)
  * **Dashboard cAdvisor custom** (CPU/RAM par conteneur + last seen)

> Si un rate limit Docker Hub survient lors d’un `docker compose up` : exécuter `docker login` sur le VPS.

---

## Logs

### Logs applicatifs (Laravel)

Les logs Laravel se trouvent dans :

* `storage/logs/laravel.log`

Ils sont persistés via le volume `potts_storage`.

Commande :

```bash
docker exec -it potts tail -n 100 storage/logs/laravel.log
```

---

## Rollback

Les images sont taggées :

* `ghcr.io/santaha03/potts:latest`
* `ghcr.io/santaha03/potts:<SHA>`

### Procédure rollback (par SHA)

1. Modifier `docker-compose.yml` :

   * remplacer `:latest` par `:<SHA>`
2. Déployer :

```bash
cd ~/apps/potts
docker compose pull
docker compose up -d
```

### Vérifier la version déployée

L’image embarque le SHA via label :

```bash
docker inspect potts --format 'REV={{index .Config.Labels "org.opencontainers.image.revision"}}'
```

---

## Dossier de preuves

* `docs/evidence/`

**Captures**

* `docs/evidence/ci-pipeline-success.png`
* `docs/evidence/security-scan-report.png`
* `docs/evidence/app-https.png`
* `docs/evidence/monitoring-dashboard.png`

**Captures complémentaires utiles**

* `docs/evidence/monitoring-node-exporter.png`

---

## Incidents rencontrés & résolutions

* **502 Bad Gateway (NPM)** : NPM forward vers un mauvais hostname (`potts-app`) → corrigé vers `potts:80`.
* **500 Laravel (APP_KEY manquant)** : `.env` absent dans l’image → `.env` créé sur VPS + monté dans le conteneur + génération `APP_KEY`.
* **Mixed Content (écran blanc)** : assets générés en HTTP → correction `APP_URL` + `ASSET_URL` en HTTPS + `php artisan optimize:clear` + `config:cache`.
* **Unauthorized GHCR** : image privée → image rendue publique (ou docker login GHCR).
* **Docker Hub rate limit** : pulls non authentifiés → `docker login` sur VPS.
* **Seeders invisibles** : montage d’un volume sur `/var/www/html/database` masquait `database/seeders` → montage uniquement du fichier sqlite.

---

## Troubleshooting rapide

### Tester l’accès depuis NPM vers POTT’S

```bash
docker exec -it nginx-proxy-manager-app-1 curl -I http://potts:80
```

### Forcer nettoyage caches Laravel après changement `.env`

```bash
docker exec -it potts php artisan optimize:clear
docker exec -it potts php artisan config:cache
```

### État des conteneurs

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Networks}}"
```

