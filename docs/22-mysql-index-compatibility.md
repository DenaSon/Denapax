# MySQL/MariaDB first-deploy index limit (SQLSTATE 1071)

**Context:** 2026-10-19 first Runflare deployment encountered:

```
SQLSTATE[42000]: 1071 Specified key was too long; max key length is 1000 bytes
alter table `users` add unique `users_email_unique`(`email`)
```

In `utf8mb4`, an indexed default `varchar(255)` may require **1020 bytes**, which exceeds a **1000-byte** index cap. The initial `Migration table not found` message is normal on a never-migrated database; `php artisan migrate --force` creates the migration repository itself.

## Permanent code fix

- `AppServiceProvider::boot()` sets Laravel schema's default string length to **191** for all subsequent migrations. An indexed `varchar(191)` requires at most **764 UTF-8 bytes**; all other explicitly chosen long **non-indexed** content columns remain unchanged.
- Both `mysql` and `mariadb` connections set **InnoDB** as the table engine so foreign keys and transactions retain expected semantics even if a provider's default engine is MyISAM.
- Spatie `roles`/`permissions` composite unique (`name` + `guard_name`) use lengths **100 + 32**, requiring at most **528 UTF-8 bytes** plus any numeric team key.
- The catalog product's indexed slug is **191**, matching the `ProductEditor` validation limit. Other intentionally long non-indexed display fields are left untouched.
- CI tests check actual SQLite-generated string column lengths and the intended MySQL/MariaDB engine config; **this is not proof** of running migrations on Runflare MySQL.

## Safe recovery after an interrupted first migration

**Never run `migrate:fresh`, `db:wipe`, arbitrary `DROP TABLE`, or reset credentials on a database containing important data.** A failed MySQL `CREATE TABLE` / `ALTER TABLE` can leave a partially created empty `users` table because schema DDL is not necessarily transactional.

1. After the new commit is deployed, verify the Laravel container reads the intended **database** (not a wrong production DB), and take a backup/snapshot of the target database first.
2. Inspect actual tables and rows using a **read-only** SQL client in Runflare MySQL:

   ```sql
   SHOW TABLES;
   SHOW TABLE STATUS LIKE 'users';
   SELECT COUNT(*) AS user_count FROM users;
   SELECT migration, batch FROM migrations ORDER BY batch, migration;
   ```

   If `users` or `migrations` is missing, skip that query and inspect existing tables first. On first failure before migration #1 completes, the `migrations` repository may be empty and `users` may have been created without its unique index.
3. **Only if positively confirmed this is a newly provisioned, disposable/empty target** with no valuable rows or dependent tables, the operator can remove the specific orphaned **empty** `users` table using the database console, **after backup**, then retry `php artisan migrate --force`. If any live customer/staff data exists, **do not drop** anything; plan a non-destructive repair after checking column/index state.
4. Run from the deployed `/var/www`:

   ```bash
   php artisan optimize:clear
   php artisan migrate:status
   php artisan migrate --force
   php artisan migrate:status
   php artisan db:seed --force
   ```

   Then provision the initial admin using `php artisan denapax:admin staff@example.com` (real staff address). Do **not** run `DemoProductsSeeder` in production.

If the error recurs on a **different** index, record the table, SQL, server engine and `SHOW VARIABLES LIKE 'version%'` / `SHOW VARIABLES LIKE 'default_storage_engine'`, with secrets redacted. Do not blindly relax SQL modes or switch to `utf8` instead of `utf8mb4`.

**Release boundary:** Real Runflare MySQL/Telegram/server access, worker+scheduler, receipts and full payments/E2E, backup/restore remain Phase 6 #11/#24/#25. The code change alone does not prove production migration success.
