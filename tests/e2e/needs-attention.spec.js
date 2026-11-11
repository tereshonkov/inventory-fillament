import { test, expect } from '@playwright/test';
import { ADMIN_STATE, EDITOR_STATE } from './global-setup.js';

// Підписи статусів, які потребують уваги (AssetsNeedingAction::STATUSES).
const NEEDS_ACTION = ['не введено', 'проводиться передача', 'проводиться списання', 'ремонт', 'втрачено'];

const queue = (page) => page.locator('.fi-wi-widget', { hasText: 'Потребує уваги' });
const rows = (page) => queue(page).locator('.fi-ta-row');

async function openDashboard(page) {
    await page.goto('/admin');
    // Віджет підвантажується лениво, а PHP-сервер однопотоковий — даємо великий запас.
    await expect(rows(page).first()).toBeVisible({ timeout: 45_000 });
    // Графіки теж ліниві: без них розкладка ще «стрибне», і вимірювання позицій буде хибним.
    await expect(page.locator('.fi-wi-stats-overview').first()).toBeVisible({ timeout: 45_000 });
    await expect(page.locator('.fi-wi-chart canvas')).toHaveCount(2, { timeout: 45_000 });
}

test.describe('«Потребує уваги» — адміністратор', () => {
    test.use({ storageState: ADMIN_STATE });
    test.describe.configure({ timeout: 90_000 });

    test.beforeEach(async ({ page }) => {
        await openDashboard(page);
    });

    test('віджет під графіками, на всю ширину, з заголовком і колонками', async ({ page }) => {
        const widget = queue(page);
        await expect(widget.getByText('Потребує уваги').first()).toBeVisible();
        for (const column of ['Інв. №', 'Назва', 'Статус', 'Очікує']) {
            await expect(widget.getByText(column, { exact: true })).toBeVisible();
        }

        const queueBox = await widget.boundingBox();
        const chartBox = await page.locator('.fi-wi-chart').last().boundingBox();
        const statsBox = await page.locator('.fi-wi-stats-overview').first().boundingBox();

        expect(queueBox.y).toBeGreaterThan(chartBox.y + chartBox.height - 2);
        expect(Math.abs(queueBox.width - statsBox.width)).toBeLessThan(4);
    });

    test('по замовчуванню 5 рядків, у кожному — статус, що потребує уваги (без «на балансі»)', async ({ page }) => {
        await expect(rows(page)).toHaveCount(5);

        const badges = await rows(page).locator('.fi-badge').allInnerTexts();
        expect(badges.length).toBeGreaterThanOrEqual(5);
        for (const badge of badges) {
            expect(NEEDS_ACTION).toContain(badge.trim());
        }
        await expect(queue(page).getByText('на балансі')).toHaveCount(0);
    });

    test('клік по рядку відкриває картку активу', async ({ page }) => {
        await rows(page).first().click();

        await expect(page).toHaveURL(/\/admin\/assets\/\d+$/);
        await expect(page.locator('.fi-header-heading')).toBeVisible();
    });

    test('є пагінація: активів, що потребують уваги, більше п’яти', async ({ page }) => {
        // E2ESeeder: 5 статусів × 2 активи (свій і чужий) = 10.
        await expect(queue(page).getByText(/10/).first()).toBeVisible();
        await expect(queue(page).locator('.fi-pagination')).toBeVisible();
    });
});

test.describe('«Потребує уваги» — editor', () => {
    test.use({ storageState: EDITOR_STATE });
    test.describe.configure({ timeout: 90_000 });

    test('editor бачить у черзі лише свої активи', async ({ page }) => {
        await openDashboard(page);

        // E2ESeeder: у editor по одному «своєму» активу на кожен із 5 статусів → одна сторінка.
        await expect(rows(page)).toHaveCount(5);

        const text = await queue(page).innerText();
        expect(text).toContain('E2E Свій');
        expect(text).not.toContain('E2E Чужий');
    });
});
