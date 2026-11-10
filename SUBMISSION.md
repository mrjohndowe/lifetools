# LifeTools competition submission

Team: LifeTools  
Stack: PHP, Apache, PDO SQLite, optional MySQL or MariaDB mirror, HTML, CSS and JavaScript.

The release command builds **lifetools-1.0.0.zip**. It contains one **lifetools/** folder and requires no runtime dependency installation. See [README.md](README.md) for the XAMPP and local-server run paths.

- **SQLite:** complete browser onboarding with SQLite only. Run **php bin/lifetools config:check** and **php bin/lifetools db:verify**.
- **Dual:** complete onboarding with SQLite and an existing MySQL or MariaDB database. Schedule **php bin/lifetools sync:drain** and run **php bin/lifetools db:verify** after it catches up.
- **Fictional demo:** onboarding requires you to choose the application login and password. For a disposable demo use **demo-admin** and **Fictional-Only-2026!**. The seeded **Example Planner** managed credential is **Synthetic-vault-only-42!**. It is separate from the application login.

The contradictory CVV sentence in the brief is resolved in favor of the disqualifier, the vault requirements, and SEC-03. There is no CVV storage.

See [CHECKLIST.md](CHECKLIST.md) for verification and [ARCHITECTURE.md](ARCHITECTURE.md) for operational trade-offs.
