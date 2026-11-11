# CLAUDE.md

Guidance for Claude Code in this repository. **This file is a living log of the project's key changes** (see "Documentation rules").

## What this project is

An inventory system for tracking material assets, built around the MVO role (Ukrainian "матеріально відповідальна особа", the person financially responsible for assets). Stack: **Laravel 13 / PHP 8.3 + Filament 5** (admin panel at `/admin`). The UI is in Ukrainian (`APP_LOCALE=uk`). The core entity is `Asset`, surrounded by reference tables, movement documents and user roles.

Stack: Laravel 13, Filament 5, PhpSpreadsheet 5 (xlsx parsing), Vite 8 + Tailwind 4 (Filament theme), PHPUnit 12, Pint. Queues and sessions use the `database` driver.

**Environments and DB:** development is done locally on **SQLite**; production runs **MySQL** (`.env.example` contains the MySQL settings, DB `inventory_db`). Tests use SQLite `:memory:`. Code and migrations must therefore work on both engines: avoid engine-specific SQL (raw queries, `JSON` functions, `ENUM`, SQLite `ALTER` quirks) and verify migrations on both before deploying.

See also: [README.md](README.md) — a cheat sheet of Filament patterns, decisions taken and their rationale (Russian/Ukrainian). Before any non-trivial change, check whether the decision is already described there.

## Commands

```bash
composer setup        # install, .env, key:generate, migrate, npm install, build, hooks, playwright browser
composer dev          # serve + queue:listen + vite (concurrently)
composer test         # config:clear + php artisan test
php artisan test --filter=Name
vendor/bin/pint       # code formatting
php artisan assets:match-statement {file.csv}   # one-off parser for an inventory statement

npm run lint          # ESLint (JS)
npm run build         # frontend build (required before e2e)
npm run test:e2e      # Playwright e2e (chromium)
npm run test:e2e:ui   # Playwright in UI mode (for debugging)
npm run hooks:install # enable git hooks (core.hooksPath = .githooks)
```

Exports/imports run through the queue — a running `queue:listen` is needed to verify them (included in `composer dev`).

**PHP is not on PATH on this machine** (it lives in Laragon: `C:\laragon\bin\php\...`). In scripts and hooks use the wrapper `node scripts/php.mjs <args>` (looks for PHP in PATH → `PHP_BINARY` → Laragon), e.g. `node scripts/php.mjs artisan test`.

## Quality checks (pre-commit)

The git hook `.githooks/pre-commit` (enabled by `npm run hooks:install`, also part of `composer setup`) **blocks the commit** if any step fails:

1. **Pint** `--test` — only on changed (staged) PHP files. The repo has ~45 older files with style violations, so the whole project is not checked; fix them together with `vendor/bin/pint` in a separate commit.
2. **ESLint** (`eslint.config.js`).
3. **PHPUnit** (`php artisan test`).
4. **`npm run build`** — the frontend must build.
5. **Playwright e2e** (`tests/e2e`) — the mandatory frontend check.

Bypass only as a last resort: `git commit --no-verify`.

### Playwright e2e

- Config: `playwright.config.js`; tests: `tests/e2e/*.spec.js` (currently smoke tests: login page, wrong password, every `/admin/*` section opens without 5xx or JS errors, the seeded asset is listed).
- A separate SQLite database `database/e2e.sqlite` (git-ignored) is recreated from scratch on every run (`migrate:fresh --seeder=E2ESeeder`), so working data is never touched. Data comes from `database/seeders/E2ESeeder.php`: admin `admin@e2e.test` and editor `editor@e2e.test` (password `password` for both), the main asset `E2E Тестовий актив` with documents, and an "own" (`E2E Свій <status>`) and a "foreign" (`E2E Чужий <status>`) asset for every status. The assets list is paginated by 5 and sorted by creation date, so find rows with `searchTable()` from `tests/e2e/helpers.js`.
- Server: built-in `php -S` via `scripts/e2e-server.mjs` on port 8001 (`E2E_PORT`); it is single-threaded, hence `workers: 1`. Login happens once in `tests/e2e/global-setup.js` (storageState).
- Requires a built frontend (`public/build`) and the browser: `npm run e2e:install`. `.npmrc` has `ignore-scripts=true`, so nothing runs automatically on `npm install`.
- New panel page/resource → add it to `PAGES` in `smoke.spec.js`; new scenarios that need data → extend `E2ESeeder`.

## Architecture

### Data model (`app/Models`)

- `Asset` — name, inventory_number, serial_number, year, notes, `status` (enum `AssetStatus`); relations: `location`, `type` (`AssetType`), `custodian` (Employee — the responsible person, MVO), `holder` (Employee — the actual holder), `incomings`, `transfers`, `writeOffs`.
- `AssetIncoming` (receipt), `AssetTransfer` (transfer to a `Department`), `AssetWriteOff` (write-off) — asset movement documents; **two-phase**: creating one starts the process, filling `completed_at` finishes it and changes the asset status.
- `Employee` (status: `EmployeeStatus`), `User` (role: `UserRole`, linked via `employee_id`), `Location`, `AssetType`, `Department` — reference data / personnel.
- Models use the `#[Fillable([...])]` attribute (Laravel 13 style); casts are defined via the `casts()` method.

### Enums (`app/Enums`)

`AssetStatus` (9 states: balance, transferring, transferred, lost, written_off, not_put_in_to_operation, writing_off, capitalize, repair), `IncomingType` (new / already_in_use), `EmployeeStatus`, `UserRole` (admin / editor / viewer). All except `UserRole` implement `HasLabel`/`HasColor`; labels are in Ukrainian.

### Roles and access

- **admin** — full access, creates assets, sees all 9 statuses, the only role that can import.
- **editor** — works only with assets where `custodian_id === user->employee_id` (row-level scoping via `getEloquentQuery()` in resources + Policy); forms offer only 4 "simple" statuses (balance, not_put_in_to_operation, repair, lost).
- **viewer** — read-only.
- Every model has a Policy in `app/Policies`. Movement documents are scoped through `Asset`. New model/resource = new Policy + scoping.

### Filament (`app/Filament`)

- Panel: `app/Providers/Filament/AdminPanelProvider.php` (id `admin`, brand "MVO", navigation groups: Облік майна / Довідники / Персонал / Адміністрування, database notifications polled every 30s, global non-native `DatePicker` with `uk` locale, "create another" disabled).
- Resource layout: `Resources/<Name>/{Resource, Pages, Schemas (Form/Infolist), Tables, RelationManagers}`. Resources and widgets are auto-discovered.
- `Concerns/RedirectsAfterSave` — trait for Edit pages: after saving, redirect to View (if the resource has one), otherwise to index.
- `ViewAsset` / `AssetInfolist`: the page heading is the asset name (subheading `№ <inventory number>`), so the infolist does not repeat the name; the root schema is one column (`columns(1)`) — with the default two columns the sections sit side by side. The header is one `Section` with two `Group` columns (equal height); do not use `Flex` with two sections there, their heights differ.
- Relation managers on `Asset`: Incomings, Transfers, WriteOffs; on `Employee`: AssetsHolder. Read-only on View pages via `isReadOnly()`.
- `AssetIncomingForm` mixes `Asset` + `AssetIncoming` fields: creation goes through a custom `handleRecordCreation()`, while `EditAssetIncoming` overrides `form()` (only `AssetIncoming` fields) and has an `afterSave()` with the completion logic.
- Dashboard widgets: `StatsOverview`, `AssetActivityChart` (6 months), `AssetStatusChart`; `NotIntroducedAssets` is commented out in the panel.
- `Exports/AssetExporter` — asynchronous Excel export; `Imports/AssetImporter` — Filament Importer (logic in `resolveRecord()`, admin only; its button in `ListAssets` is currently commented out).

### xlsx import services (`app/Services`)

They parse real documents by fixed cell coordinates / marker texts, so they are very sensitive to the file layout:

- `IncomingDocumentImporter` — detects the document type (`НАКЛАДНА` in A9 → incoming; `Акт внутрішнього переміщення` in A7 → transfer) and parses rows (incoming from row 21, transfer from row 16). Called from `ListAssetIncomings`.
- `AssetActivationImporter` — "Акт введення в експлуатацію" (commissioning act): finds blocks and their boundaries (header / "Всього:" / "Проведено огляд"), reads inventory number (A), quantity (D), serial number (AL); activates assets. Called from `ListAssets`.

## Conventions

- Asset names can be very long (several paragraphs). In headings and compact widgets use `$asset->short_name` (max 4 words), never `name`; the full name belongs on the view card.
- **CLAUDE.md is written exclusively in English, and so are all future edits to it.** Replies to the user are in the language the user writes in.
- UI texts, labels and business-logic comments in the application code are in Ukrainian.
- Asset status changes automatically through page lifecycle hooks (Create/Edit), not manually — do not break this link.
- Reference data is **not auto-created** during import (avoids duplicates); "dirty" rows are only those with an empty `name` or an unresolvable `status`.
- Formatting — Pint; tests — PHPUnit in `tests/Feature` and `tests/Unit` (currently only examples — minimal coverage).

## Plans

Implementation plans live in `docs/` as `plan-{name}.md`. Current plans:

- [docs/plan-design-review.md](docs/plan-design-review.md) — redesign of the "Огляд майна" dashboard and the asset view card (steps 0–8).

How to work with plans:

- Look into `docs/` for `plan-*.md` files. A plan or step is implemented only if it is marked `[x]`; anything marked `[ ]` is not done yet.
- **Do not start an unimplemented plan on your own.** Execute it only when the user commands it (e.g. "do step 1", "continue the plan"). Execute exactly the requested step(s), one step = one commit.
- After finishing a step, mark it in the plan file: tick it in the "Progress" list and update the step's `Статус` line (`[x]`, date, commit subject — not the hash, which is unknown before the commit). Add a change log entry here as usual.
- If a step turns out to be wrong or needs changes, update the plan file first and tell the user.

## Known issues / TODO

- No duplicate protection when re-importing Assets (open question about an empty `serial_number`).
- PHPUnit coverage is minimal: factory/seeder tests and `ExampleTest` only; no tests for resources, policies or widgets yet (the plan adds some). The frontend is covered by e2e smoke tests.
- ~45 PHP files do not conform to Pint (only changed files are checked in pre-commit).
- `User::canAccessPanel()` returns `true` for everyone.
- `DatabaseSeeder` only creates a test user.

## Documentation rules

All key project changes are documented here. After every significant change (new model/migration/resource, role or access change, new business logic, import/export, dependencies, infrastructure) Claude **adds an entry to the "Change log"** below and, if needed, updates the relevant sections above. Minor edits (styles, texts, indentation) are not logged.

**Language: everything in this file is written in English only**, including all future edits and log entries.

Entry format: `- YYYY-MM-DD — what changed and why (key files)`. Newest entries on top.

## Change log

- 2026-11-11 — Long asset names: `Asset::short_name` accessor (whitespace/paragraphs squished, max 4 words, hard cap 60 chars, `…` suffix); the asset view heading uses it, the full name is shown as "Повна назва" in the infolist (3-line clamp + tooltip). `E2ESeeder` got an asset with a long multi-paragraph name (id 2, `LONG_NAME_ASSET_ID`). Tests: `AssetShortNameTest` (unit), `ViewAssetTest`, `asset-card.spec.js`.
- 2026-11-11 — Step 1 follow-up after review: the "single frame" rule in `resources/css/filament/admin/theme.css` (tabs + table in one bordered container) is now scoped to list pages (`.fi-resource-list-records-page`); on view pages it wrapped all infolist cards into one big card. On view pages only the relation-manager block (tabs + table) is a separate card. The asset header is a single `Section` with two `Group` columns instead of two `Flex` cards of different height. Tests added to `tests/e2e/asset-card.spec.js` (page grid has no border, history block is its own card, list page keeps its single frame).
- 2026-11-11 — Plan step 1 (asset view card): `ViewAsset` heading = asset name, subheading = `№ <inventory number>`; `AssetInfolist` rebuilt as a single-column schema: two-zone `Flex` header (copyable monospace inventory number + large status badge | location, type, year with inline labels), "Відповідальність та ідентифікація" section (inline labels, 2 columns from `lg`), collapsed "Службова інформація" (relative timestamps with full-date tooltip); year no longer formatted with a thousands separator; placeholders kept only for custodian/holder/serial/notes. Tests: `tests/Feature/Filament/ViewAssetTest.php`, `tests/e2e/asset-card.spec.js`.
- 2026-11-11 — Plan step 0 (test foundation): `HasFactory` on all domain models; factories for Employee, Location, AssetType, Department, Asset (`status()`, `custodian()`, `heldBy()` states), AssetIncoming, AssetTransfer, AssetWriteOff (`completed()` states) and role states on `UserFactory` (`admin()`, `editor($employee)`, `viewer()`); `E2ESeeder` now seeds admin + editor, the dashboard stat-tile types/warehouses, the main asset with documents and an own/foreign asset for every status; new tests `FactoriesTest`, `E2ESeederTest` (PHPUnit) and `seed-data.spec.js` (editor sees only own assets); `smoke.spec.js` finds the asset through the table search (list is paginated by 5); installed the `laravel-filament-v5` skill.
- 2026-11-11 — Added `docs/plan-design-review.md` (dashboard + asset card redesign plan, steps 0–8, none implemented yet) and a "Plans" section here describing how plans are tracked and executed on command.
- 2026-11-11 — Installed the third-party skill `laravel:code-review-requests` (from `jpcaparas/superpowers-laravel`, via `npx skills add`) into `.agents/skills/laravel-code-review-requests`; it is available through the `.claude/skills` junction and recorded in `skills-lock.json`.
- 2026-11-11 — Translated CLAUDE.md into English and made English the mandatory language for this file and all future edits (replies to the user stay in the user's language).
- 2026-11-11 — Added Playwright (`@playwright/test`, chromium) with e2e smoke tests of the panel (`tests/e2e`, `playwright.config.js`, `E2ESeeder`, separate DB `database/e2e.sqlite`); ESLint (`eslint.config.js`); pre-commit hook `.githooks/pre-commit` (Pint on staged files → ESLint → PHPUnit → vite build → Playwright), enabled via `npm run hooks:install` (also in `composer setup`); `scripts/php.mjs` wrapper to locate PHP (Laragon, not on PATH).
- 2026-11-11 — Project skills live in `.agents/skills` (`filament-pro`, `laravel-best-practices`, `laravel-filament-v5-ui-ux`, `ui-craft-dense-dashboard`); a directory junction `.claude/skills` → `.agents/skills` was added for Claude Code (a real symlink could not be created on Windows without admin rights; on a new machine recreate with `New-Item -ItemType Junction -Path .claude\skills -Target <abs>\.agents\skills`).
- 2026-11-11 — Documented environments: local development on SQLite, production on MySQL (migrations/queries must be compatible with both).
- 2026-11-11 — Created CLAUDE.md: project analysis, architecture, roles, commands, log-keeping rules.
- Earlier (from git history): added `AssetActivationImporter` (commissioning-act import); widgets `AssetActivityChart` and `AssetStatusChart`; xlsx import of incoming documents (waybill + transfer act) via `IncomingDocumentImporter`; Policies for `AssetType` and `Location`; assets table filters (searchable, preload, modal layout); titles and hidden date columns in the Incomings/Transfers/WriteOffs relation managers; asynchronous asset export + database notifications; Filament Importer for assets; roles, row-level scoping and Policies; two-phase movement documents (receipt/transfer/write-off).
