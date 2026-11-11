#!/usr/bin/env node
// Піднімає вбудований PHP-сервер для Playwright (webServer). Фронт має бути зібраний: npm run build.
import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { resolvePhp } from './php.mjs';
import { assertE2eDatabase, e2eEnv, E2E_PORT } from './e2e-env.mjs';

assertE2eDatabase();

const router = resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');

const server = spawn(resolvePhp(), ['-S', `127.0.0.1:${E2E_PORT}`, '-t', resolve('public'), router], {
    env: e2eEnv(),
    cwd: resolve('public'), // router Laravel шукає index.php відносно cwd
    stdio: 'inherit',
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill());
}
server.on('exit', (code) => process.exit(code ?? 0));
