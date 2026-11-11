/* global document, window -- використовуються всередині page.evaluate (виконується в браузері) */
import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './global-setup.js';

// Ширини з плану: широкий екран, ноутбук/планшет, телефон.
const VIEWPORTS = [
    { name: 'desktop 1440', width: 1440, height: 900 },
    { name: 'tablet 1024', width: 1024, height: 768 },
    { name: 'phone 390', width: 390, height: 844 },
];

const widgetsLoaded = async (page) => {
    // Віджети дашборда ліниві, а PHP-сервер однопотоковий — даємо великий запас.
    await expect(page.locator('.fi-wi-stats-overview').first()).toBeVisible({ timeout: 45_000 });
    await expect(page.locator('.fi-wi-chart canvas')).toHaveCount(2, { timeout: 45_000 });
    await expect(page.locator('.fi-wi-table .fi-ta-row').first()).toBeVisible({ timeout: 45_000 });
};

/** Сторінка не має горизонтальної прокрутки (таблиці можуть прокручуватися всередині своєї картки). */
const hasNoHorizontalScroll = (page) =>
    page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth && document.body.scrollWidth <= window.innerWidth);

const top = async (locator) => (await locator.boundingBox()).y;

test.describe('адаптив: «Огляд майна»', () => {
    test.use({ storageState: ADMIN_STATE });
    test.describe.configure({ timeout: 120_000 });

    test('без горизонтальної прокрутки на всіх ширинах; порядок віджетів залежить від ширини', async ({ page }) => {
        await page.goto('/admin');
        await widgetsLoaded(page);

        for (const { name, width, height } of VIEWPORTS) {
            await page.setViewportSize({ width, height });

            expect(await hasNoHorizontalScroll(page), name).toBe(true);

            const stats = await top(page.locator('.fi-wi-stats-overview').first());
            const queue = await top(page.locator('.fi-wi-table'));
            const chart = await top(page.locator('.fi-wi-chart').first());

            expect(stats, name).toBeLessThan(queue);

            if (width < 768) {
                // Телефон: черга дій одразу після цифр, вторинна аналітика (графіки) — нижче.
                expect(queue, name).toBeLessThan(chart);
            } else {
                // Від md: цифри → графіки → черга.
                expect(chart, name).toBeLessThan(queue);
            }
        }
    });

    test('на телефоні плитки складу по дві в ряд, а не чотири високі картки', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin');
        await widgetsLoaded(page);

        const tiles = page.locator('.fi-wi-stats-overview .fi-wi-stats-overview-stat');
        await expect(tiles).toHaveCount(4);

        const first = await tiles.nth(0).boundingBox();
        const second = await tiles.nth(1).boundingBox();
        const third = await tiles.nth(2).boundingBox();

        expect(Math.abs(first.y - second.y)).toBeLessThan(4); // перші дві — в одному ряду
        expect(third.y).toBeGreaterThan(first.y + first.height - 2); // третя — у наступному
    });

    test('на телефоні черга не має зайвих колонок: «Інв. №» і «МВО» сховані', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin');
        await widgetsLoaded(page);

        const queue = page.locator('.fi-wi-table');
        await expect(queue.getByText('Назва', { exact: true })).toBeVisible();
        await expect(queue.getByText('Статус', { exact: true })).toBeVisible();
        await expect(queue.getByText('Інв. №', { exact: true })).toBeHidden();
        await expect(queue.getByText('МВО', { exact: true })).toBeHidden();
    });
});

test.describe('адаптив: картка активу', () => {
    test.use({ storageState: ADMIN_STATE });
    test.describe.configure({ timeout: 120_000 });

    test('без горизонтальної прокрутки; заголовок і статус видно в першому екрані', async ({ page }) => {
        await page.goto('/admin/assets/1');
        await expect(page.locator('.fi-tabs-item').first()).toBeVisible({ timeout: 45_000 });

        for (const { name, width, height } of VIEWPORTS) {
            await page.setViewportSize({ width, height });

            expect(await hasNoHorizontalScroll(page), name).toBe(true);

            const heading = await page.locator('.fi-header-heading').boundingBox();
            const badge = await page.locator('.fi-badge', { hasText: 'на балансі' }).first().boundingBox();

            // Назва та поточний статус — до вторинних даних і без прокрутки.
            expect(heading.y + heading.height, `${name}: заголовок`).toBeLessThan(height);
            expect(badge.y + badge.height, `${name}: статус`).toBeLessThan(height);
        }
    });

    test('на телефоні всі три вкладки історії вміщуються в ширину екрана', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/admin/assets/1');
        await expect(page.locator('.fi-tabs-item').first()).toBeVisible({ timeout: 45_000 });

        const tabs = page.locator('.fi-tabs-item');
        await expect(tabs).toHaveCount(3);

        for (let i = 0; i < 3; i++) {
            const box = await tabs.nth(i).boundingBox();
            expect(box.x, `вкладка ${i + 1}`).toBeGreaterThanOrEqual(0);
            expect(box.x + box.width, `вкладка ${i + 1}`).toBeLessThanOrEqual(390);
        }
    });
});
