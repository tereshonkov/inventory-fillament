// Спільне оточення для e2e: окрема SQLite-база, щоб не чіпати робочі дані розробки.
import { spawnSync } from 'node:child_process';
import { mkdirSync, existsSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { resolvePhp } from './php.mjs';

export const E2E_PORT = process.env.E2E_PORT || '8001';
export const E2E_URL = `http://127.0.0.1:${E2E_PORT}`;
export const E2E_DB = resolve('database', 'e2e.sqlite');

export function e2eEnv() {
    if (!existsSync(E2E_DB)) {
        mkdirSync(resolve('database'), { recursive: true });
        writeFileSync(E2E_DB, '');
    }

    return {
        ...process.env,
        APP_ENV: 'local',
        APP_DEBUG: 'true',
        APP_URL: E2E_URL,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: E2E_DB,
        SESSION_DRIVER: 'file',
        CACHE_STORE: 'file',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
    };
}

/**
 * Safety check: the app must really use the e2e SQLite file under the e2e environment.
 * With a cached config (bootstrap/cache/config.php) Laravel ignores environment variables and would use
 * the database from .env instead — and `migrate:fresh` in the e2e setup would wipe it.
 */
export function assertE2eDatabase() {
    const code = 'echo json_encode([config("database.default"), config("database.connections.sqlite.database")]);';
    const result = spawnSync(resolvePhp(), ['artisan', 'tinker', `--execute=${code}`], { env: e2eEnv(), encoding: 'utf8' });

    let actual = null;
    try {
        actual = JSON.parse(result.stdout.trim());
    } catch {
        // handled below
    }

    if (!actual || actual[0] !== 'sqlite' || resolve(actual[1]) !== E2E_DB) {
        throw new Error(
            `e2e aborted: the app is configured with ${JSON.stringify(actual)} instead of ["sqlite","${E2E_DB}"]. ` +
                'The configuration is probably cached — run `php artisan config:clear`.',
        );
    }
}
