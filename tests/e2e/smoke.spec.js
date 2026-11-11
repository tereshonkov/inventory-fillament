import { test, expect } from '@playwright/test';
import { ADMIN, ADMIN_STATE } from './global-setup.js';
import { searchTable } from './helpers.js';

// Усі розділи панелі, які мають відкриватися без помилок.
const PAGES = [
    ['Дашборд', '/admin'],
    ['Активи', '/admin/assets'],
    ['Надходження', '/admin/asset-incomings'],
    ['Передачі', '/admin/asset-transfers'],
    ['Списання', '/admin/asset-write-offs'],
    ['Комплектуючі', '/admin/asset-components'],
    ['Типи техніки', '/admin/asset-types'],
    ['Локації', '/admin/locations'],
    ['Підрозділи', '/admin/departments'],
    ['Співробітники', '/admin/employees'],
    ['Користувачі', '/admin/users'],
];

/** Збирає JS-помилки сторінки та відповіді сервера 5xx. */
function collectProblems(page) {
    const problems = [];
    page.on('pageerror', (error) => problems.push(`pageerror: ${error.message}`));
    page.on('response', (response) => {
        if (response.status() >= 500) {
            problems.push(`HTTP ${response.status()}: ${response.url()}`);
        }
    });
    return problems;
}

test('сторінка входу відкривається', async ({ page }) => {
    const problems = collectProblems(page);

    await page.goto('/admin/login');

    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    expect(problems).toEqual([]);
});

test('невірний пароль не пускає в панель', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').fill(ADMIN.email);
    await page.locator('input[type="password"]').fill('wrong-password');
    await page.locator('button[type="submit"]').click();

    await expect(page).toHaveURL(/\/admin\/login/);
});

test.describe('панель під адміністратором', () => {
    // Сесія адміна створюється один раз у global-setup.
    test.use({ storageState: ADMIN_STATE });

    for (const [title, path] of PAGES) {
        test(`відкривається: ${title}`, async ({ page }) => {
            const problems = collectProblems(page);

            const response = await page.goto(path);

            expect(response?.status()).toBeLessThan(400);
            await expect(page.locator('main, .fi-main').first()).toBeVisible();
            await page.waitForLoadState('networkidle');
            expect(problems).toEqual([]);
        });
    }

    test('тестовий актив видно у списку активів', async ({ page }) => {
        await page.goto('/admin/assets');

        // У списку пагінація по 5 і сортування за датою створення — шукаємо через пошук таблиці.
        await searchTable(page, 'E2E Тестовий');

        await expect(page.getByText('E2E Тестовий актив').first()).toBeVisible();
    });
});
