# Architecture

LifeTools is a single-node PHP application with locally served CSS and JavaScript. PDO SQLite handles every interactive read and write. A small logical record model stores UUIDs, owners, kinds, revisions, UTC timestamps, tombstones, indexed range keys, normalized search and JSON payloads. Module schemas define the allowable payloads. SQLite also maintains a derived reverse-link index and local operational tables. The mirror holds the authoritative logical records, including users, audits, tombstones and encrypted attachments. Local queue, rate limits and indexes can be reconstructed or reset during recovery.

~~~mermaid
sequenceDiagram
    participant U as Browser
    participant A as PHP request
    participant S as SQLite
    participant W as Sync worker
    participant M as MySQL backup
    U->>A: Save with current record revision
    A->>S: Commit record and durable queue revision together
    S-->>A: Commit complete
    A-->>U: Saved
    W->>S: Fetch ready job and current SQLite row
    W->>M: Upsert UUID and commit
    alt MySQL accepts the write
        W->>S: Remove job only if revision still matches
    else MySQL is unavailable
        W->>S: Keep job and record bounded retry backoff
    end
~~~

SQLite uses WAL and an immediate write transaction. Each UUID has at most one queued job, which tracks its newest revision. The worker locks across local processes and across connections to the same mirror. It reads the latest SQLite value and writes it even if a mirror revision is higher. Higher revisions are logged as drift. A process killed after committing MySQL can replay the same UUID. The conditional acknowledgment keeps a concurrently queued revision. A delete is an ordinary upsert with a tombstone.

MySQL and MariaDB use the same portable schema with dialect-specific DDL and upsert statements. The first connection checks migration version, the backup table's columns and ownership fingerprint. The worker caps retries at 30 seconds. Page rendering and write requests never connect to MySQL. The browser's opportunistic worker uses a separate authenticated request, releases the PHP session first, and processes a bounded batch. The scheduled CLI is the unattended path. PDO's connect and InnoDB lock timeouts do not guarantee a hard deadline for an established network connection.

The verification command compares canonical SHA-256 hashes across all logical rows, including ciphertext. Reconciliation reports missing, changed and extra IDs. It re-queues the first two and removes extras while holding the mirror writer lock. Verification during concurrent updates can observe transient drift. Rerun after the queue drains.

Timed event instants use **YYYY-MM-DDTHH:MM:SSZ**. All-day dates use **YYYY-MM-DD** and inclusive end dates. Recurrence is evaluated in the event's time zone. Missing dates such as February 31 and nonexistent spring-forward times are skipped. A moved occurrence keeps its original series identity and duration. A skipped occurrence still consumes one count when it was a valid scheduled date. Money is stored as an integer, a currency code and the minor-unit scale.

The installer holds a local lock, writes external secrets and publishes a prepared marker before committing the initial administrator, seed and installation fingerprint. If it is interrupted before the commit, setup can be retried. If it is interrupted after the commit, the prepared marker plus SQLite fingerprint keeps setup locked. The installer and backup paths are documented in [SECURITY.md](SECURITY.md).
