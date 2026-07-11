# Deploy auf DomainFactory (Ordner `Projekte/wkr4`)

Laravel 13 / PHP 8.3 · MySQL `db96617_82` · Admin (Filament) unter `/admin`.

---

## 0. Voraussetzungen im DF-Kundenmenü
- **PHP-Version** für `wkr4` (bzw. die Domain) auf **8.3** stellen.
- **MySQL-DB** `db96617_82` existiert; **Passwort** für User `db96617_82` vergeben (notieren).
- **SSH-Zugang** aktiv (für Composer/Artisan). Composer ist auf DF verfügbar (`composer`), sonst `php composer.phar`.

## 1. Code nach `wkr4`
Per SSH ins Projektverzeichnis, dann klonen (empfohlen) oder per SFTP hochladen:
```bash
cd ~/Projekte
git clone git@github.com:pmerhi/wkz.git wkr4        # oder in vorhandenen wkr4 hinein
cd wkr4
```
> `vendor/`, `node_modules/`, `.env`, `public/build` sind bewusst **nicht** im Repo.

## 2. Abhängigkeiten
```bash
composer install --no-dev --optimize-autoloader
php artisan filament:upgrade         # publiziert die Filament-Admin-Assets nach public/
```

## 3. `.env` anlegen
```bash
cp .env.production.example .env
# .env editieren: DB_PASSWORD, MAIL_*, APP_URL prüfen
php artisan key:generate             # setzt APP_KEY
```

## 4. Frontend-Assets (Tailwind/Vite)
Das öffentliche Layout ist inline-gestylt (kein Build nötig), **aber** Welcome-/einige
Filament-Custom-Seiten nutzen `app.css`. Node ist auf DF-Shared meist nicht vorhanden →
**lokal bauen und `public/build` hochladen**:
```bash
# lokal:
npm ci && npm run build
# dann public/build/ per SFTP nach wkr4/public/build/ hochladen
```

## 5. Datenbank füllen  ⚠️ wichtig
Die Prod-DB ist leer. Zulassungsstellen, Kürzel, Ratgeber usw. liegen in der DB
(FAQ/AGB liegen als Dateien im Code). Kompletten Dump der lokalen DB einspielen:
```bash
# lokal (Mac): Dump ziehen
mysqldump -u wunsch -p wunsch > wkr.sql
# hochladen, dann auf dem Server importieren:
mysql -u db96617_82 -p db96617_82 < wkr.sql
# danach evtl. neue Migrations nachziehen:
php artisan migrate --force
```
> Alternativ ohne Dump: `php artisan migrate --force` + eigene Import-Commands/Seeder –
> aber der Dump ist der schnellste, vollständige Weg (inkl. deiner gepflegten Inhalte).

## 6. Storage & Rechte
```bash
php artisan storage:link
chmod -R ug+rw storage bootstrap/cache
```

## 7. DocumentRoot auf `public/`
Laravel darf **nur** `wkr4/public` ausliefern (sonst ist `.env` erreichbar!).
- **Bevorzugt:** im DF-Menü den **DocumentRoot** der Domain auf `…/wkr4/public` setzen.
- **Falls nicht möglich:** eine `.htaccess` im Domain-Root, die alles nach `wkr4/public` umschreibt.

## 8. Caches für Produktion
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
> Nach **jeder** `.env`- oder Routen-Änderung erneut `config:cache` / `route:cache`.

## 9. Admin-Zugang anlegen
```bash
php artisan make:filament-user      # E-Mail + Passwort für /admin
```

## 10. Go-Live-Prüfung
- **SSL** (Let's Encrypt im DF-Menü) für die Domain aktiv; `APP_URL` = `https://…`.
- `https://…/robots.txt` und `https://…/sitemap.xml` erreichbar.
- **Alt-URL-Weiterleitungen** stichprobenartig testen (alle 2.596 lösen auf, siehe URL-Migration).
- Matomo/Tracking prüfen (jetzt korrekte `APP_URL`, keine `https://wunsch`-Reste mehr).

## 11. Optional: Scheduler
Nur falls Konsolen-Jobs (z. B. `enrichment:*`) laufen sollen – DF-Cronjob:
```
*/5 * * * *  cd ~/Projekte/wkr4 && php artisan schedule:run >> /dev/null 2>&1
```

---
### Update-Deploy (später)
```bash
cd ~/Projekte/wkr4
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:upgrade
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
