import { spawnSync } from 'node:child_process';
import { chromium } from '@playwright/test';
import { resolvePhp } from '../../scripts/php.mjs';
import { assertE2eDatabase, e2eEnv, E2E_URL } from '../../scripts/e2e-env.mjs';

export const ADMIN = { email: 'admin@e2e.test', password: 'password' };
export const ADMIN_STATE = 'tests/e2e/.auth/admin.json';
export const EDITOR = { email: 'editor@e2e.test', password: 'password' };
export const EDITOR_STATE = 'tests/e2e/.auth/editor.json';
export const VIEWER = { email: 'viewer@e2e.test', password: 'password' };
export const VIEWER_STATE = 'tests/e2e/.auth/viewer.json';

// Перед прогоном перестворюємо e2e-базу з нуля, наповнюємо мінімальними даними
// і один раз логінимось адміністратором (стан сесії переживає всі тести).
export default async function globalSetup() {
    // Не чіпаємо жодну базу, окрім e2e.sqlite (migrate:fresh знищує всі таблиці).
    assertE2eDatabase();

    const result = spawnSync(
        resolvePhp(),
        ['artisan', 'migrate:fresh', '--force', '--seeder=E2ESeeder', '--no-interaction'],
        { env: e2eEnv(), stdio: 'inherit' },
    );

    if (result.status !== 0) {
        throw new Error('Не вдалося підготувати e2e-базу (migrate:fresh --seeder=E2ESeeder).');
    }

    const browser = await chromium.launch();

    for (const [user, statePath] of [[ADMIN, ADMIN_STATE], [EDITOR, EDITOR_STATE], [VIEWER, VIEWER_STATE]]) {
        const context = await browser.newContext({ baseURL: E2E_URL });
        const page = await context.newPage();

        await page.goto('/admin/login');
        await page.locator('input[type="email"]').fill(user.email);
        await page.locator('input[type="password"]').fill(user.password);
        await page.locator('button[type="submit"]').click();
        await page.waitForURL(/\/admin(\/)?$/, { timeout: 60_000 });
        await context.storageState({ path: statePath });
        await context.close();
    }

    await browser.close();
}
