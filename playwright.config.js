import { defineConfig, devices } from '@playwright/test';
import { E2E_URL } from './scripts/e2e-env.mjs';

export default defineConfig({
    testDir: './tests/e2e',
    // PHP built-in server однопотоковий, тому тести йдуть послідовно.
    workers: 1,
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    timeout: 30_000,
    globalSetup: './tests/e2e/global-setup.js',
    use: {
        baseURL: E2E_URL,
        locale: 'uk-UA',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: 'node scripts/e2e-server.mjs',
        url: `${E2E_URL}/admin/login`,
        reuseExistingServer: false,
        stdout: 'ignore',
        stderr: 'ignore', // PHP -S пише access-лог у stderr; для налагодження приберіть
        timeout: 60_000,
    },
});
