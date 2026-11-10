# LifeTools

LifeTools is a private personal planner for a single self-hosted installation. It includes a timeline, calendar, contacts, vault, organizers, music library, business records, private journals, files, and a website directory. All runtime assets are local. The optional demo contains fictional records.

## Install on XAMPP

1. Extract the versioned release so that its top-level **lifetools** directory sits under XAMPP's **htdocs**.
2. Start Apache and open **http://localhost/lifetools/** on the server. Complete the setup wizard. Choose SQLite for a standalone installation. For dual mode, also start MariaDB and supply an existing empty database and a dedicated user with permission to create and write tables in it. XAMPP's phpMyAdmin can create these without a terminal.
3. Copy the encryption key to separate offline storage and complete onboarding. The wizard writes the configuration and initial administrator and locks setup.

PHP 8.1+ is required with PDO SQLite, SQLite3, PDO MySQL, OpenSSL, mbstring, Fileinfo and JSON. Apache needs **AllowOverride All** and mod_env. Enable **upload_max_filesize=10M** and **post_max_size=12M** or higher for 10 MB uploads. The installation checks write access to its protected directories. [Apache .htaccess documentation](https://httpd.apache.org/docs/2.4/howto/htaccess.html.en)

The build command places **lifetools-1.0.0.zip** in **dist/**. When this source checkout is served by Apache with the built archive present, **/lifetools/release.php** serves the ZIP directly. See [SECURITY.md](SECURITY.md) for key location, HTTP protections and recovery.

## Local server and commands

Run from the project folder. In XAMPP, use the full path to XAMPP's PHP executable if **php** is unavailable in the terminal. The local server binds to loopback and advances to the next available port if the requested port is occupied. Apache controls its own port.

~~~powershell
php bin/lifetools install
php bin/lifetools config:check
php bin/lifetools config:show
php bin/lifetools start
php bin/lifetools db:migrate
php bin/lifetools db:seed
php bin/lifetools db:verify
php bin/lifetools sync:drain
php bin/lifetools db:backup
php bin/lifetools test
php bin/lifetools release

~~~

**dev** is an alias for **start**. No dependency install is needed. **install** opens the localhost onboarding path, and an interactive **start** with missing configuration does the same. **db:reset** runs a disposable SQLite development database and leaves installed data alone. **test** runs in the selected mode, using its own SQLite file and, in dual mode, a randomly named temporary MySQL schema. Use a test MySQL login permitted to create and drop that schema. Never point tests at production credentials. **db:seed** uses the current year on first seed and reports it. Use **--year 2026** for the reproducible fixture year and **--user LOGIN** to select a workspace. Stable fixture IDs prevent duplicate seeds and preserve trash decisions. To remove individual demo records, use the corresponding module's trash action.

Settings offers redacted configuration, synthetic data, export, migrations, verification, repair, queue drain, backup, and restore. Administrators can add users. Backup and installation actions require re-authentication.

## Site language

LifeTools includes English and Spanish. Use the language button on the setup or sign-in page, or open the account menu after signing in. LifeTools remembers your choice in the current browser. English is the default.

The English text is in [app/lang.php](app/lang.php) and the Spanish text is in [app/lang-es.php](app/lang-es.php). Keep message keys and named placeholders such as `{title}` intact when editing the text. Records, custom choices, and export field names retain their stored values.

## Configuration

The canonical file is **lifetools.config.json**. Search order is **--config PATH**, **LIFETOOLS_CONFIG**, the project root, **config/lifetools.config.json**, then **%APPDATA%/lifetools/config.json** on Windows or **$XDG_CONFIG_HOME/lifetools/config.json** on Linux (defaults to **~/.config**). Relative paths resolve from the app root. The first existing file is validated. Values use CLI flag, then environment, then JSON, then defaults. Process variables take precedence over simple **NAME=value** lines in the optional local **.env**. Empty overrides use the next source. Unknown JSON keys are tolerated.

**config:check** exits 0 for OK, 1 for INVALID, 2 for MISSING and prints the resolved path and install phase. **config:show** redacts secrets. The CLI accepts **--db-mode**, **--sqlite-path**, **--mysql-host**, **--mysql-port**, **--mysql-database**, **--mysql-user**, **--mysql-password**, **--encryption-key**, **--session-secret**, **--port** and **--time-zone**. Use environment or secret files for credentials to keep them out of command history. The generated config uses external **secret:** file references. The template demonstrates **env:** references. A working installation with a lost configuration enters recovery. Restore the original settings and key before booting it.

### SQLite and dual mode

Set **dbMode** or **DB_MODE** to **sqlite** for a standalone install. To add a mirror, use **Settings → Change backup mode**, or configure **mysql** and select **dual** in the file. Restart the app and worker after changing the configuration. Switching into dual mode schedules all SQLite rows, including tombstones. SQLite remains authoritative. **DB_MODE=mysql** fails validation. If the mirror goes offline, the app continues locally and shows the worker's last reported status. A browser with LifeTools open checks and drains the queue in separate requests. Configure a scheduled worker for unattended operation.

### Schedule the mirror on Windows

1. Open Task Scheduler and choose **Create Task**. Use an account that can read the app configuration and private secret files and write to **data** and **storage**.
2. Add a trigger that repeats every minute indefinitely.
3. Set the action program to XAMPP's **php.exe**, arguments to **bin/lifetools sync:drain**, and **Start in** to the LifeTools folder. Set overlapping runs to **Do not start a new instance**. Run the task once and review the dashboard's sync status.
4. On Linux, the equivalent is a one-minute cron entry invoking the same command from the app directory.

**db:verify** exits 1 for missing, changed or extra MySQL rows. **db:verify --repair** reports current drift and queues repair, including removing mirror-only rows. Drain and verify again. In SQLite mode verify exits 0 and reports that MySQL is unconfigured. An incompatible mirror schema requires administrator inspection.

### Back up, restore and update

A backup creates a timestamped folder under **backups/** with a consistent SQLite snapshot, checksums and row counts, and a logical MySQL dump if available. It reports a failed or empty mirror dump. File contents are stored inside encrypted SQLite records and follow the snapshot and mirror. Keep the installation marker, configuration and keys separately in protected storage.

Restore from Settings on a running installation or stop Apache and the scheduled worker before running the following commands. Replace **BACKUP_ID** with the folder name. The MySQL dump option rebuilds SQLite from the last replicated state and can lag the SQLite snapshot. After restoring, run verification with repair to reconcile any newer rows remaining in MySQL.

~~~powershell
php bin/lifetools db:restore BACKUP_ID --force
php bin/lifetools db:restore BACKUP_ID --from-mirror --force
php bin/lifetools db:verify --repair
php bin/lifetools sync:drain --force
php bin/lifetools db:verify

~~~

To update, back up, stop Apache and the worker, and copy the newer release over the app folder while preserving the generated configuration, data, backups, uploads, and storage. Restart. Forward migrations run on boot. An older application refuses a newer schema. The archive builder uses an allowlist and does not include generated config, key files, databases, backups or uploaded data.

See [DEMO.md](DEMO.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [CHECKLIST.md](CHECKLIST.md) for the walkthrough, design, and recorded verification.
