# Verification checklist

| Area | Verification |
| --- | --- |
| PHP SQLite suite | 21 checks passed on Windows PHP 8.5.10 and 8.3.35, including recurrence, tamper detection, ownership, save retry and seed idempotence. |
| SQLite and dual installations | Extracted the ZIP under a subdirectory and installed both modes through Apache 2.4.58 with PHP 8.5.10. MariaDB 10.4.32 was the mirror. MySQL-only configuration failed validation. |
| MySQL outage and reconciliation | Ten HTTP writes committed with MariaDB stopped. The queue drained when it returned and verification matched row hashes. The dual PHP suite covered a higher mirror revision, repair, tombstone replay and schema drift. |
| Ownership and encryption | HTTP checks covered a second administrator, default export omissions, denied cross-user reveals, a masked vault editor, private journal search and recovery of the original ciphertext. |
| Backup and restore | Verified the snapshot checksum and restored an earlier SQLite state. A nonempty MySQL dump rebuilt a missing SQLite database and its restored vault field decrypted. The SQLite snapshot also rebuilt a missing primary database and an empty MariaDB mirror. |
| Apache protection and archive | A direct HTTP download matched the ZIP byte-for-byte. Every entry was under **lifetools/**. Config, database and storage requests returned 403. The ZIP contained no installed configuration, databases, keys, uploads or backups. |
| Browser | Eight Chrome checks passed. They covered setup, quick-add, offline autosave retry, eight core screens at 390 px in light and dark themes, live system-theme switching, and a reveal that cleared after 30 seconds. All 35 module list and creation screens were exercised over HTTP. |
| Update | Re-extracted the release over an installed dual-mode copy. Sign-in, existing records and the setup lock survived. This exercised a code overwrite at the same schema version. |

## Performance

On the Windows test machine, with 16 logical CPU threads, 16 GiB RAM, Apache 2.4.58, PHP 8.5.10 and OPcache enabled, we inserted 10,000 fictional events, 1,000 contacts, 5,000 list items and 1,000 journal entries. Events were distributed across 1,800 dates. A local HTTP client warmed each authenticated route once and measured five responses through the last byte. Median response time was **41.1 ms** for Timeline, **48.9 ms** for the month view and **25.3 ms** for global search.

With MariaDB stopped, 1,000 sequential HTTP event creates took **134.0 ms at p95**, including the SQLite commit and HTTP round trip. Once MariaDB was reachable, CLI workers drained the 1,067 outstanding rows in **25.89 seconds**. That total includes 67 existing jobs and CLI startup. Hash verification passed after the drain. Steady-state mirror lag and browser paint time were outside the measured scope.

## Remaining acceptance checks and limitations

- Stock XAMPP with PHP 8.2 and MySQL 8.4 were unavailable on the test machine. MariaDB 10.4.32 and XAMPP's Apache and PHP 8.5.10 were tested.
- The replay test re-enqueues an already mirrored tombstone. It does not forcibly kill a worker during a database commit.
- The browser's opportunistic sync runs as a separate authenticated request. The worker uses bounded batches and connection timeouts, with no hard deadline for established MySQL I/O. The scheduled CLI is the unattended path.
- Timeline lists event series. Calendar expands occurrences. Confidential journal text is searched inside the corresponding owner-only journal. Import and private-content export are absent.
- Whole-file encryption and in-place encryption key rotation are absent. Clipboard clearing depends on browser permissions.

