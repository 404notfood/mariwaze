# Mariwaze

Mariwaze est une application de préparation de croisières maritimes et fluviales : flotte, itinéraires, météo marine, bathymétrie, balisage, zones de danger et checklists de sécurité.

## Stack

- PHP 8.4, Laravel 13, Fortify, Inertia 3
- React 19, TypeScript strict, Vite 8, Tailwind CSS 4
- SQLite pour le développement et les tests ; PostgreSQL/PostGIS recommandé en production
- Moteur local Python pour le routage maritime

## Installation locale

Prérequis : PHP 8.4, Composer 2, Node.js 22.13 ou supérieur, npm et Python 3.11 ou supérieur.

```bash
composer setup
python -m pip install --target .python-packages -r requirements.txt
```

Vérifier ensuite `.env`, notamment la base de données et l’exécutable Python :

```dotenv
SEAROUTE_PYTHON=python
```

Initialiser le catalogue mondial (commande réexécutable, sans supprimer les données existantes) :

```bash
php artisan maritime:import-world --download
```

Cette commande charge les localités de [GeoNames cities500](https://download.geonames.org/export/dump/readme.txt), leurs variantes de noms et régions, ainsi que les repères portuaires du [NGA World Port Index](https://msi.nga.mil/Publications/WPI). Les identifiants importés restent stables lors des mises à jour. GeoNames est distribué sous CC BY 4.0. Les données brutes sont conservées dans `storage/app/geodata` ; elles ne sont pas servies publiquement.

Lancer l’application, le worker et Vite :

```bash
composer dev
```

Le compte créé par `php artisan db:seed` est destiné uniquement au développement. Ne jamais exécuter ce seeder sur une base de production existante.

## Contrôles qualité

```bash
composer ci:check
```

Cette commande vérifie Pint, PHPStan niveau 7, ESLint, Prettier, TypeScript, le build, les avis de sécurité Composer/npm et les tests PHPUnit.

Les tests frontend Vitest couvrent notamment l'encodage des SVG et le rendu sûr des alertes métier. Playwright vérifie la page publique dans un véritable navigateur Chromium. Les tests Feature couvrent l'isolation multi-tenant, les rôles, les actions administrateur sensibles, les en-têtes HTTP et les tâches de génération.

## Préparation et utilisation à bord

- Les suggestions de lieux indiquent le pays et les coordonnées. Une sélection de port ou de mouillage conserve son identifiant pour distinguer les homonymes ; modifier le texte efface cette sélection. Les localités du catalogue restent disponibles en complément.
- Les suggestions utilisent directement le catalogue mondial, avec recherche des variantes de noms (par exemple « Lisbonne » → Lisbon), classement des villes par population et références persistantes pour distinguer les homonymes. **Élargir la recherche en ligne** interroge explicitement le fournisseur configuré pour un lieu manquant. Les résultats permanents conservent leur région ; les données Mapbox temporaires ne sont pas enregistrées dans ce catalogue.
- La carte est disponible dès l'ouverture de **Nouvelle croisière**, centrée sur le monde. **Placer le départ**, puis **Placer l’arrivée**, capture les coordonnées GPS à cinq décimales. Ces points restent utilisables sans port référencé et sont conservés après réouverture. La carte marine permet également de créer une croisière depuis les deux points choisis, même sans port voisin identifié.
- L'exploration propose un filtre par pays ou territoire. Les repères NGA indiquent leur source ; l'accueil de plaisanciers et les profondeurs restent à confirmer lorsqu'ils ne sont pas documentés. Les trajets et recherches de proximité prennent en charge le passage de l'antiméridien dans le Pacifique.
- La création permet de choisir un bateau et une zone de navigation. Les scénarios affichent leur rythme visé, leur étape la plus longue, les critères pondérés et le nombre d'étapes disposant d'une météo complète.
- Une prévision partielle ne reçoit pas de note favorable : la note est plafonnée à 50, tout en conservant les pénalités des conditions dangereuses connues. La couverture mesure la part des valeurs horaires de vent, rafales et vagues disponibles sur la fenêtre demandée.
- L'exploration filtre les escales avant de limiter les résultats : profondeur minimale, service au port, orientation protégée au mouillage et bateau. La profondeur requise pour le bateau inclut 0,5 m de marge. Les profondeurs inconnues sont exclues lorsqu'un seuil est demandé ; les dimensions inconnues restent à confirmer.
- Le catalogue d'escales est paginé par 24 résultats avec un total filtré et un tri stable commun aux ports et mouillages. Les liens de pagination conservent les critères ; les champs se resynchronisent lors du retour dans l'historique, tout en gardant le départ et l'arrivée saisis. Les suggestions de lieux se parcourent avec les flèches, se valident avec Entrée et se ferment avec Échap.
- Dans une croisière, les coches de la checklist sont enregistrées. **Carnet hors connexion** télécharge un fichier HTML autonome avec les étapes de l'itinéraire retenu, les prévisions enregistrées, les dates et la checklist. Ouvrir ce fichier sans réseau ou l'imprimer en PDF depuis le navigateur. Les coches modifiées dans le fichier téléchargé ne sont pas synchronisées et ne sont pas conservées après fermeture ; imprimer la copie pour les garder.
- **Exporter le GPX retenu** télécharge une trace GPX 1.1 de l'itinéraire sélectionné. Les exports exigent le même accès à la croisière que l'application. Les cartes en ligne ne sont pas incluses dans le carnet.
- Modifier les paramètres du trajet invalide les anciennes routes : un nouveau calcul est nécessaire avant l'export GPX. Les changements d'équipage, de durée ou de bateau actualisent les quantités de la checklist tout en conservant ses coches. Une croisière en cours de calcul doit attendre sa fin avant modification.

### Tests navigateur isolés

`npm run test:e2e` prépare uniquement `storage/browser-tests.sqlite`, puis lance le routeur de test `tests/Browser/server.php`. Ce routeur remplace les fournisseurs météo, bathymétrie et routage par des données déterministes ; il n'est pas chargé par l'entrée publique de l'application. La connexion, la création du bateau et de la croisière, le calcul métier, la sélection, la checklist et les téléchargements passent par les vraies routes HTTP. Le carnet est rouvert avec le réseau désactivé. Un second parcours vérifie les filtres et une panne de recherche sur mobile. Le moteur Python et les services externes conservent leurs tests dédiés.

Ne pas réutiliser un serveur existant sur le port 4173 pour ces tests. Installer Chromium avec `npx playwright install chromium` si nécessaire ; `PLAYWRIGHT_CHANNEL` permet d'utiliser un navigateur compatible déjà installé.

## Import des données maritimes

Les commandes principales sont :

```bash
php artisan maritime:import-places
php artisan maritime:import-world --download
php artisan seamarks:import
php artisan hazards:import-areas
php artisan geodata:import-geonames /chemin/vers/le/fichier.tsv
php artisan waterways:import-overpass /chemin/vers/export.json
```

Les API tierces et leurs délais sont configurés dans `.env.example`. En production, utiliser une instance Nominatim autorisée ou auto-hébergée et un User-Agent avec une véritable adresse de contact.

Le routage et le catalogue sont mondiaux ; les mouillages détaillés, la bathymétrie EMODnet et les fiches réglementaires dépendent de la couverture de leurs sources. Une réglementation absente est signalée comme non renseignée. Les contacts d'urgence d'une autre zone ne sont pas substitués aux contacts locaux manquants.

L'autocomplétion et les estimations automatiques de durée n'appellent pas le géocodeur distant. Le service public Nominatim reste réservé au développement, aux recherches explicites mises en cache et limitées à une requête par seconde pour l'application ; respecter sa [politique d'utilisation](https://operations.osmfoundation.org/policies/nominatim/), notamment l'interdiction de l'autocomplétion distante. Sous Windows, renseigner de préférence le chemin absolu d'un interpréteur Python compatible avec les paquets installés dans `.python-packages` dans `SEAROUTE_PYTHON`.

## Déploiement

Avant chaque mise en production :

1. Configurer `APP_ENV=production`, `APP_DEBUG=false` et une URL HTTPS.
2. Activer `SESSION_SECURE_COOKIE=true` et conserver `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true` et `SESSION_SAME_SITE=lax`.
3. Générer une clé d’application unique et stocker tous les secrets dans le gestionnaire de secrets de l’hébergeur.
4. Exécuter `composer install --no-dev --classmap-authoritative`, `npm ci`, `npm run build` et `php artisan migrate --force`.
5. Exécuter `php artisan optimize`, démarrer un worker de queue supervisé et appeler le scheduler Laravel chaque minute.
6. Configurer les proxies de confiance afin que Laravel détecte correctement HTTPS et l’adresse client.
7. Sauvegarder la base et tester régulièrement la restauration.

Les routes métier exigent un compte authentifié et une adresse e-mail vérifiée. Les ressources de flotte et de croisière sont limitées à l'organisation courante, avec les rôles `owner`, `manager`, `member` et `viewer`. Seuls les propriétaires et gestionnaires peuvent modifier la flotte. Ne retirez pas ces contrôles pour créer un mode démo : utilisez un compte de démonstration isolé et réinitialisable.

La génération d'itinéraires est asynchrone et exige un worker de queue actif. L'interface suit l'état `pending`, `running`, `completed` ou `failed`, tandis qu'un verrou unique empêche deux calculs simultanés pour une même croisière.

## Exploitation et sécurité

- Surveiller les erreurs HTTP, l'état des workers, la durée du moteur Python et les échecs des fournisseurs météo/géographiques.
- Surveiller les violations CSP reçues sur `/api/security/csp-reports`, limitées en débit et consignées sans corps de requête complet.
- Modérer les nouveaux avis dans `/admin/avis` : seuls les avis approuvés sont publics et inclus dans les notes. Chaque décision est journalisée dans `audit_logs`.
- Conserver les audits `composer audit` et `npm audit` dans la CI et traiter les alertes Dependabot.
- Mettre en place une politique de rétention des journaux sans données personnelles inutiles.
- Afficher la date, la source et la fraîcheur des données nautiques. Mariwaze aide à préparer une navigation mais ne remplace pas les cartes, AVURNAV et bulletins officiels.

## Structure utile

- `app/Services` : logique métier et intégrations
- `app/Policies` : autorisation des ressources
- `resources/js/pages` : pages Inertia
- `resources/js/components/mariwaze` : composants cartographiques et métier
- `tests/Feature` : scénarios HTTP, sécurité et isolation des comptes
- `scripts/maritime_route.py` : moteur de routage maritime local
