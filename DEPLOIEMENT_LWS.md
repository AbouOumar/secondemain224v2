# Mise en ligne sur LWS (cPanel) — Seconde Main 224

Ce guide couvre le déploiement du site Laravel/Blade (API + back-office admin) sur un
hébergement mutualisé cPanel chez LWS. Le front Next.js n'est pas couvert ici (encore
en construction, nécessite une configuration Node.js/Passenger distincte).

## 0. Prérequis côté LWS

- Un hébergement cPanel LWS avec accès **SSH** (Terminal, disponible sur les offres
  mutualisées/cPanel LWS) et **Composer**.
- PHP **8.3** ou supérieur (cPanel > Logiciel > *Sélectionner une version de PHP*).
- Extensions PHP activées : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`,
  `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (toutes activables depuis l'écran de
  sélection de version PHP, onglet extensions).
- Une base **MySQL** (cPanel > Bases de données MySQL).
- Un nom de domaine/sous-domaine pointé vers l'hébergement.

## 1. Préparer le code en local avant l'upload

Le build des assets (Tailwind/Vite) doit être fait avant l'upload si `npm` n'est pas
disponible dans le terminal SSH de l'hébergement (c'est le cas sur certaines offres
mutualisées LWS — le module Node.js de cPanel sert à héberger des apps Node, pas à
exécuter des builds pour du PHP).

```bash
npm ci
npm run build
```

Cela génère `public/build/` (normalement ignoré par Git). Vérifiez qu'il est bien
présent avant l'upload — sans lui, le site s'affichera sans styles ni JS.

## 2. Créer la base de données MySQL

Dans cPanel > **Bases de données MySQL** :
1. Créer une base (ex: `secondmain`) → cPanel la préfixera automatiquement
   (`utilisateur_secondmain`).
2. Créer un utilisateur MySQL avec un mot de passe fort.
3. Associer l'utilisateur à la base avec **tous les privilèges**.

Notez le nom complet préfixé de la base et de l'utilisateur : vous en aurez besoin
dans le `.env`.

## 3. Uploader le code

**Option recommandée (SSH + Git)** — dans le Terminal cPanel :

```bash
cd ~
git clone <url-de-votre-repo> secondmainv2
cd secondmainv2
```

**Option alternative (sans Git)** : zippez le projet en local (en excluant
`vendor/`, `node_modules/`, `.git/`) et uploadez via le Gestionnaire de fichiers
cPanel, puis décompressez.

Placez le projet **en dehors** de `public_html` (ex: `~/secondmainv2`), pour que seul
le dossier `public/` de Laravel soit accessible publiquement — voir étape 5.

## 4. Installer les dépendances PHP

```bash
cd ~/secondmainv2
composer install --no-dev --optimize-autoloader
```

## 5. Pointer le domaine vers le dossier `public/`

C'est l'étape la plus importante : le document root du domaine doit pointer vers
`~/secondmainv2/public`, jamais vers la racine du projet (sinon `.env`, `app/`,
`composer.json` etc. seraient exposés publiquement).

- cPanel > **Domaines** > sélectionnez votre domaine > modifiez le **Document Root**
  pour qu'il pointe vers `secondmainv2/public`.
- Si votre offre ne permet pas de changer le document root (certains plans
  mutualisés basiques), utilisez la méthode de repli : placez le contenu de
  `public/` à la racine de `public_html`, et éditez `public_html/index.php` pour
  faire pointer les deux `require` vers `../secondmainv2/vendor/autoload.php` et
  `../secondmainv2/bootstrap/app.php`. Cette méthode fonctionne mais est plus
  fragile lors des mises à jour — préférez toujours la première option si possible.

## 6. Configurer l'environnement

```bash
cp .env.production.example .env
php artisan key:generate
```

Éditez `.env` et renseignez au minimum :
- `APP_URL` — l'URL finale du site (https://...)
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — les valeurs créées à l'étape 2
- `MAIL_*` — les identifiants SMTP fournis par LWS (cPanel > Comptes e-mail) ou un
  fournisseur tiers
- `DJOMY_CLIENT_ID` / `DJOMY_CLIENT_SECRET` — clés de production, `DJOMY_SANDBOX=false`
- `SANCTUM_STATEFUL_DOMAINS` et `SESSION_DOMAIN` — votre vrai domaine

## 7. Initialiser l'application

```bash
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force   # catégories de base
php artisan db:seed --class=AdminSeeder --force       # compte admin initial
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> ⚠️ **Sécurité** : `AdminSeeder` crée un compte admin avec un mot de passe codé en
> dur (`admin@secondemain224.com` / `admin123@`). Connectez-vous immédiatement après
> le seed et changez ce mot de passe — le laisser tel quel sur un site public est
> une faille de sécurité évidente.

`storage:link` crée un lien symbolique `public/storage` → `storage/app/public`. Les
hébergements cPanel autorisent `symlink()` dans l'immense majorité des cas ; si la
commande échoue avec une erreur de permission, contactez le support LWS ou copiez
manuellement le contenu au lieu d'un lien (moins pratique, à éviter si possible).

## 8. Permissions fichiers

```bash
chmod -R 755 storage bootstrap/cache
```

Le propriétaire des fichiers doit être votre utilisateur cPanel (normalement déjà le
cas après un upload via SSH/Git).

## 9. Cron (scheduler Laravel)

L'application dépend du planificateur Laravel pour deux choses : l'expiration des
boosts et le traitement de la file d'attente (queue) — voir `routes/console.php`.
Sur hébergement mutualisé, il n'y a pas de worker permanent possible : le scheduler
déclenche `queue:work --stop-when-empty` toutes les minutes à la place.

Dans cPanel > **Tâches Cron**, ajoutez :

```
* * * * * cd /home/utilisateur/secondmainv2 && php artisan schedule:run >> /dev/null 2>&1
```

(remplacez `/home/utilisateur/secondmainv2` par le vrai chemin absolu, visible en
lançant `pwd` dans le Terminal SSH depuis le dossier du projet).

## 10. HTTPS

Activez le SSL gratuit (Let's Encrypt / AutoSSL) depuis cPanel > **SSL/TLS Status**,
puis forcez HTTPS — c'est déjà géré côté code (`AppServiceProvider::boot()` force le
schéma HTTPS en environnement `production`).

## 11. Vérifications post-déploiement

- [ ] La page d'accueil charge sans erreur 500
- [ ] `/register` et `/login` fonctionnent (créer un compte de test)
- [ ] `/admin` est accessible avec un compte `role: admin` et refuse les autres rôles
- [ ] Les images uploadées (annonces, avatars) s'affichent — vérifie `storage:link`
- [ ] Un paiement en mode sandbox/test passe par le webhook correctement
- [ ] `php artisan about` (en SSH) confirme `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Les tâches cron s'exécutent (vérifiable via les logs cPanel > Tâches Cron, ou
      `storage/logs/laravel.log`)

## 12. Mises à jour futures

Un script `deploy.sh` est fourni à la racine du projet pour automatiser les étapes
récurrentes (composer install, build assets, migrations, cache, redémarrage des
workers) :

```bash
cd ~/secondmainv2
git pull
bash deploy.sh
```

## Dépannage courant

| Symptôme | Cause probable |
|---|---|
| Page blanche / erreur 500 | `APP_KEY` manquante (`php artisan key:generate`), ou permissions `storage/`/`bootstrap/cache` |
| "could not find driver" MySQL | Extension `pdo_mysql` non activée dans la version PHP sélectionnée |
| Images/CSS/JS absents | `npm run build` non exécuté avant l'upload, ou document root mal configuré |
| Images uploadées cassées (404) | `php artisan storage:link` non exécuté |
| Modifications de code sans effet | Cache de config/routes obsolète → `php artisan config:clear && php artisan route:clear` puis recacher |
| Boosts/queue jamais traités | Cron non configuré ou chemin absolu incorrect dans la tâche cron |
