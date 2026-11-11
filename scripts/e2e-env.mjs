// Спільне оточення для e2e: окрема SQLite-база, щоб не чіпати робочі дані розробки.
import { mkdirSync, existsSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

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
