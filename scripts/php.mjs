#!/usr/bin/env node
// Знаходить PHP (PATH, PHP_BINARY або Laragon на Windows) і запускає його з переданими аргументами.
// Використання: node scripts/php.mjs artisan test
import { spawnSync } from 'node:child_process';
import { existsSync, readdirSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

export function resolvePhp() {
    if (process.env.PHP_BINARY && existsSync(process.env.PHP_BINARY)) {
        return process.env.PHP_BINARY;
    }

    if (spawnSync('php', ['-v'], { stdio: 'ignore' }).status === 0) {
        return 'php';
    }

    // Laragon (Windows): беремо найновішу встановлену версію.
    const laragon = process.env.LARAGON_PHP_DIR || 'C:\\laragon\\bin\\php';
    if (existsSync(laragon)) {
        const versions = readdirSync(laragon).sort().reverse();
        for (const version of versions) {
            const candidate = join(laragon, version, 'php.exe');
            if (existsSync(candidate)) {
                return candidate;
            }
        }
    }

    throw new Error('PHP не знайдено: додайте його в PATH або задайте змінну PHP_BINARY.');
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    const result = spawnSync(resolvePhp(), process.argv.slice(2), { stdio: 'inherit' });
    process.exit(result.status ?? 1);
}
