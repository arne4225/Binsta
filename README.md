# Binsta

A small Instagram-achtige app voor het delen van code snippets: posts met syntax highlighting en een kleurenschema naar keuze, een feed met likes/comments/forks, gebruikersprofielen en zoeken.

## Vereisten

- PHP 8.1 of hoger, met de `pdo_mysql` en `fileinfo` extensies
- Composer
- MySQL of MariaDB, bereikbaar op `127.0.0.1`

## Installatie

1. Installeer de dependencies:

   ```
   composer install
   ```

2. Zorg dat er een lege database bestaat die overeenkomt met de instellingen in [public/index.php](public/index.php) (standaard: database `Binsta`, user `root`, geen wachtwoord). Pas de connectiegegevens in dat bestand aan als jouw lokale MySQL-setup anders is ingesteld.

   ```
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS Binsta"
   ```

3. Vul de database met testdata (gebruikers, posts, likes en comments):

   ```
   php seeder.php
   ```

   De seeder is idempotent: je kan hem opnieuw draaien om terug te gaan naar een schone teststate. Let op — hij **wist** de bestaande `user`, `post`, `postlike` en `comment` tabellen voordat hij opnieuw seedt.

   RedBeanPHP draait in "fluid mode" (`R::freeze(false)`), dus tabellen en kolommen worden automatisch aangemaakt zodra de app of de seeder ze voor het eerst gebruikt — er is geen aparte migratiestap nodig.

## De app draaien

Start de ingebouwde PHP-webserver met `public/` als document root:

```
php -S localhost:8000 -t public
```

Open daarna `http://localhost:8000` in je browser. De app draait ook prima achter Apache met mod_rewrite (zie [public/.htaccess](public/.htaccess)) — zorg dat de document root dan naar `public/` wijst.

> Belangrijk: als je een nieuw controllerbestand toevoegt onder `controllers/`, moet je `composer dump-autoload` draaien voordat de route werkt. Composer's classmap wordt namelijk niet automatisch opnieuw gegenereerd.

## Inloggen met testdata

Na het draaien van `seeder.php` kun je inloggen met elk van deze accounts (wachtwoord voor iedereen: `geheim123`):

| Gebruikersnaam | Naam |
|---|---|
| `arne` | Arne Veltman |
| `lisa_codes` | Lisa de Vries |
| `devsander` | Sander Bakker |
| `julia.dev` | Julia Jansen |
| `mo_scripts` | Mo El Amrani |

## Wat je kunt testen

- **Registreren / inloggen / uitloggen** — `/user/register`, `/user/login`, `/user/logout`.
- **Profiel bewerken** (`/user/profile`, via "Instellingen" in het accountmenu): naam en biografie wijzigen, een profielfoto uploaden (jpg/png/gif/webp, max 5MB), wachtwoord wijzigen.
- **Een post maken** (`/post/create`, via "Nieuwe post"): kies een programmeertaal, een kleurenschema (Dark, Light, Dracula, Monokai of Solarized Light) en plak een code snippet, met optioneel een bijschrift.
- **Feed** (`/feed/index`): de meest recente posts van alle gebruikers, met syntax highlighting in het gekozen kleurenschema.
  - **Liken**: klik op de hart-knop onder een post om te liken/unliken.
  - **Reageren**: typ een reactie onder een post.
  - **Forken**: klik op "⑂ Fork" bij een post om er een eigen, bewerkbare kopie van te maken — de nieuwe post toont een link terug naar de originele auteur.
  - Klik op de naam/avatar van een gebruiker om naar hun profiel door te klikken.
- **Gebruikersprofiel** (`/user/show?id=…`): bio, profielfoto en de persoonlijke feed van die gebruiker.
- **Zoeken**: de zoekbalk in de header zoekt gebruikers op gebruikersnaam of naam.

Er is geen geautomatiseerde testsuite voor dit project — testen gebeurt door de bovenstaande flows handmatig te doorlopen in de browser.

## Beveiliging

- Alle formulieren met een POST-actie zijn beveiligd met een CSRF-token (centraal gecontroleerd in `public/index.php`); een POST zonder geldig token krijgt een 403.
- Wachtwoorden worden gehasht met `password_hash` (bcrypt/argon, afhankelijk van je PHP-build).
- Sessiecookies zijn `HttpOnly` en `SameSite=Lax`, en `Secure` zodra de app over HTTPS draait.
- Alle databasequeries lopen via RedBeanPHP's parameter-binding (geen losse string-concatenatie), en Twig's autoescaping staat aan.
