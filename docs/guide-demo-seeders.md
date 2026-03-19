# Guide Seeders et Commandes Démo

## Objectif

Ce document résume les seeders récents ajoutés au projet et fournit une liste de commandes utiles pour le jour de la démo.

## Seeders récents

### 1) PlantTypeSeeder

Fichier: database/seeders/PlantTypeSeeder.php

Rôle:

- Crée et met à jour le référentiel des espèces de plantes (idempotent via updateOrCreate).
- Injecte les seuils de référence par espèce:
    - humidité optimale
    - température optimale
    - luminosité optimale
- Injecte les coefficients RSE:
    - co2_k_per_m2_year
    - surface foliaire par taille S, M, L

Espèces actuellement seedées:

- Monstera deliciosa
- Strelitzia reginae
- Pachira aquatica
- Ocimum basilicum
- Ficus lyrata

Pourquoi c est important:

- Le rapport RSE multi-espèces dépend de ces seuils et coefficients.
- Sans ce seeder, les calculs par espèce ne sont pas fiables.

### 2) PlantsDemoSeeder

Fichier: database/seeders/PlantsDemoSeeder.php

Rôle:

- Crée ou met à jour l organisation SHBF Compagnie.
- Crée 25 plantes réalistes avec:
    - espèce
    - taille
    - localisation (building, floor, zone)
    - date d installation
- Crée 25 devices dédiés (POTTS-SHBF-001 à POTTS-SHBF-025) et les lie aux plantes.

Répartition des plantes:

- 7 Monstera
- 5 Strelitzia
- 5 Pachira
- 4 Ficus
- 4 Basilic

Pourquoi c est important:

- Donne une base démo crédible et stable pour la partie client.
- Assure la liaison Device -> Plant -> PlantType pour les calculs RSE.

### 3) DatabaseSeeder (orchestration)

Fichier: database/seeders/DatabaseSeeder.php

Rôle:

- Initialise des utilisateurs de base (admin, client, tech).
- Appelle les seeders clés dans cet ordre:
    - PlantTypeSeeder
    - PlantsDemoSeeder
    - MissionSeeder
    - DemoDeviceSeeder

Pourquoi c est important:

- Permet un bootstrap rapide d un environnement de démo en une seule commande.

## Complément non-seeder mais essentiel

### GenerateYearReadingsCommand

Fichier: app/Console/Commands/GenerateYearReadingsCommand.php

Commande:
php artisan potts:generate-year-readings --org=shbf --year=2026 --reset

Rôle:

- Génère un an de télémétrie réaliste (2 mesures par jour, 3 capteurs EAV).
- Simule saisonnalité, arrosage, jours gris et pannes ponctuelles.
- Met à jour last_seen_at et last_values sur les devices.

## Commandes utiles pour le jour de la démo

## A) Setup rapide

Réinitialiser la base et rejouer tous les seeders:
php artisan migrate:fresh --seed

Générer la télémétrie annuelle SHBF:
php artisan potts:generate-year-readings --org=shbf --year=2026 --reset

## B) Créer un utilisateur client

Créer un user client sur org_id 2:
php artisan tinker --execute='use Illuminate\Support\Facades\Hash; $u=\App\Models\User::updateOrCreate(["email"=>"demo.client@potts.app"],["name"=>"Demo Client","password"=>Hash::make("demo1234"),"org_id"=>2,"role"=>"client","email_verified_at"=>now()]); echo "id={$u->id}, email={$u->email}, org_id={$u->org_id}, role={$u->role}".PHP_EOL;'

## C) Modifier un utilisateur existant

Changer le mot de passe et l organisation:
php artisan tinker --execute='use Illuminate\Support\Facades\Hash; $u=\App\Models\User::where("email","demo.client@potts.app")->first(); if(!$u){echo "user_not_found".PHP_EOL; return;} $u->update(["password"=>Hash::make("newpass123"),"org_id"=>2,"role"=>"client"]); echo "updated_user={$u->email}".PHP_EOL;'

Passer un user en role tech:
php artisan tinker --execute='$u=\App\Models\User::where("email","tech@potts.app")->first(); if(!$u){echo "user_not_found".PHP_EOL; return;} $u->update(["role"=>"tech"]); echo "role={$u->role}".PHP_EOL;'

## D) Vérifier rapidement les données de démo

Compter plantes/devices sur SHBF:
php artisan tinker --execute='$org=\App\Models\Org::where("name","SHBF Compagnie")->first(); echo "org_id=".($org?->id ?? "null").PHP_EOL; if(!$org){return;} echo "plants=".\App\Models\Plant::where("org_id",$org->id)->count().PHP_EOL; echo "devices=".\App\Models\Device::where("org_id",$org->id)->count().PHP_EOL;'

Tester le endpoint RSE avec un user:
php artisan tinker --execute='$u=\App\Models\User::where("email","demo.client@potts.app")->first(); $req=tap(\Illuminate\Http\Request::create("/api/app/v1/rse/report","GET",["year"=>2026]), fn($r)=>$r->setUserResolver(fn()=>$u)); $d=app(\App\Http\Controllers\Api\App\RseReportController::class)->show($req)->getData(true); echo "org_id=".($d["scope"]["org_id"] ?? "null").PHP_EOL; echo "data_points=".($d["coverage"]["data_points"] ?? 0).PHP_EOL; echo "top_plants=".count($d["top_plants"] ?? []).PHP_EOL;'

## E) Ajouter quelques alertes réalistes (optionnel)

Créer des missions/incidents pour alimenter les graphes alertes:
php artisan tinker --execute='$orgId=2; $user=\App\Models\User::where("email","demo.client@potts.app")->first(); $devices=\App\Models\Device::where("org_id",$orgId)->orderBy("id")->limit(3)->pluck("id")->all(); if(!$user || empty($devices)){echo "missing_prerequisites".PHP_EOL; return;} $dates=[["2026-05-08 11:00:00","critical","replacement","Pic de temperature critique"],["2026-06-21 08:45:00","warning","maintenance","Lumiere insuffisante persistante"],["2026-09-17 10:10:00","warning","maintenance","Derive capteur lumiere a verifier"]]; foreach($dates as $i=>$e){[$dt,$sev,$type,$title]=$e; $scheduled=\Carbon\Carbon::parse($dt,"UTC"); $m=\App\Models\Mission::create(["org_id"=>$orgId,"assigned_to_user_id"=>null,"type"=>$type,"title"=>$title,"address"=>"Site SHBF","status"=>"done","scheduled_for"=>$scheduled,"closed_at"=>$scheduled->copy()->addHours(12)]); \App\Models\MissionIncident::create(["mission_id"=>$m->id,"device_id"=>$devices[$i % count($devices)],"severity"=>$sev,"type"=>"other","description"=>$title,"created_by"=>$user->id,"created_at"=>$scheduled->copy()->addHour(),"updated_at"=>$scheduled->copy()->addHour()]); } echo "alerts_seeded".PHP_EOL;'

## Notes pratiques

- Les seeders PlantTypeSeeder et PlantsDemoSeeder sont idempotents.
- Le reset de télémétrie est piloté par l option --reset.
- Toujours hasher les mots de passe via Hash::make.
- Pour la démo, privilégier l année 2026 si les datasets ont été générés sur cette année.
