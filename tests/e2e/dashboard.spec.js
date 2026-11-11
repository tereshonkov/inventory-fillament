import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './global-setup.js';

test.describe('сторінка «Огляд майна»', () => {
    test.use({ storageState: ADMIN_STATE });
    test.describe.configure({ timeout: 90_000 });

    test.beforeEach(async ({ page }) => {
        await page.goto('/admin');

        // Усі три віджети підвантажуються лениво. Однопотоковий PHP-сервер (та завантажена машина) може бути
        // повільним, тому чекаємо їх з великим запасом — далі кожен тест працює з уже завантаженим дашбордом.
        await expect(page.locator('.fi-wi-stats-overview').first()).toBeVisible({ timeout: 45_000 });
        await expect(page.locator('.fi-wi-chart canvas')).toHaveCount(2, { timeout: 45_000 });
    });

    test('заголовок, вкладка браузера та пункт меню називаються «Огляд майна»', async ({ page }) => {
        await expect(page.locator('.fi-header-heading')).toHaveText('Огляд майна');
        await expect(page).toHaveTitle(/Огляд майна/);
        await expect(page.locator('.fi-sidebar').getByText('Огляд майна')).toBeVisible();
        await expect(page.getByText('Інфопанель')).toHaveCount(0);
    });

    test('віджети йдуть у порядку: цифри → активність → статуси', async ({ page }) => {
        const stats = page.locator('.fi-wi-stats-overview').first();
        const activity = page.getByText('Активність за останні 6 місяців');
        const statuses = page.getByText('Розподіл за статусом');

        await expect(stats).toBeVisible();
        await expect(activity).toBeVisible();
        await expect(statuses).toBeVisible();

        const statsBox = await stats.boundingBox();
        const activityBox = await activity.boundingBox();
        const statusesBox = await statuses.boundingBox();

        expect(statsBox.y).toBeLessThan(activityBox.y);
        // На широкому екрані графіки в одному ряду, графік активності — зліва.
        expect(Math.abs(activityBox.y - statusesBox.y)).toBeLessThan(8);
        expect(activityBox.x).toBeLessThan(statusesBox.x);
    });

    test('на широкому екрані два графіки займають порівну ширини (підписи статусів не обрізаються)', async ({ page }) => {
        const activity = page.locator('.fi-wi-chart', { hasText: 'Активність за останні 6 місяців' });
        const statuses = page.locator('.fi-wi-chart', { hasText: 'Розподіл за статусом' });

        const a = await activity.boundingBox();
        const s = await statuses.boundingBox();

        expect(a.width / s.width).toBeGreaterThan(0.9);
        expect(a.width / s.width).toBeLessThan(1.1);
        // Статуси — достатньо широкі для підписів на кшталт «проводиться списання — 2» (у третині ширини, ≈280px у вікні Playwright 1280px, вони обрізалися).
        expect(s.width).toBeGreaterThan(400);
    });

    test('картки двох графіків однакової висоти на будь-якій ширині', async ({ page }) => {
        const card = (title) => page.locator('.fi-wi-chart', { hasText: title }).locator('.fi-section').first();

        for (const width of [1280, 1440, 1700]) {
            await page.setViewportSize({ width, height: 900 });

            const a = await card('Активність за останні 6 місяців').boundingBox();
            const s = await card('Розподіл за статусом').boundingBox();

            expect(Math.abs(a.height - s.height), `ширина ${width}`).toBeLessThan(2);
        }
    });

    test('плитки складу підписані й показують 6 типів у фіксованому порядку', async ({ page }) => {
        const stats = page.locator('.fi-wi-stats-overview').first();

        await expect(stats.getByText('Наявність на складах')).toBeVisible();
        await expect(stats.getByText('Склад-327')).toBeVisible();

        const tiles = stats.locator('.fi-wi-stats-overview-stat');
        await expect(tiles).toHaveCount(6);

        // Дані E2ESeeder: 2 ПК на Склад-327, 3 монітори на Склад-915; ноутбуків і робочих станцій на складах немає.
        const expected = [
            ['ПК', '2 шт'],
            ['Ноутбук', '0 шт'],
            ['Робоча станція', '0 шт'],
            ['Монітор', '3 шт'],
            ['ДБЖ', '0 шт'],
            ['БФП', '0 шт'],
        ];

        for (const [index, [label, value]] of expected.entries()) {
            await expect(tiles.nth(index)).toContainText(label);
            await expect(tiles.nth(index)).toContainText(value);
        }
    });

    test('плитки комплектуючих на складах окремо від плиток активів', async ({ page }) => {
        const block = page.locator('.fi-wi-stats-overview', { hasText: 'Комплектуючі на складах' });

        await expect(block.getByText('Склад-327')).toBeVisible();

        const tiles = block.locator('.fi-wi-stats-overview-stat');
        await expect(tiles).toHaveCount(5);

        // Дані E2ESeeder: 1 несправний монітор на Склад-915, 2 ДБЖ на Склад-327.
        const expected = [
            ['ПК (комплектуючі)', '0 шт'],
            ['Монітор (комплектуючі)', '1 шт'],
            ['Ноутбук (комплектуючі)', '0 шт'],
            ['ДБЖ (комплектуючі)', '2 шт'],
            ['Принтер (комплектуючі)', '0 шт'],
        ];

        for (const [index, [label, value]] of expected.entries()) {
            await expect(tiles.nth(index)).toContainText(label);
            await expect(tiles.nth(index)).toContainText(value);
        }

        // Плитки активів не змінилися: монітор-комплектуюча в них не потрапляє.
        const assets = page.locator('.fi-wi-stats-overview', { hasText: 'Наявність на складах' });
        await expect(assets.locator('.fi-wi-stats-overview-stat').nth(3)).toContainText('3 шт');
    });

    test('графік активності: лінія з цілими поділками осі та поясненням, що рахуються документи', async ({ page }) => {
        const widget = page.locator('.fi-wi-chart', { hasText: 'Активність за останні 6 місяців' });

        await expect(widget.getByText('не активів')).toBeVisible();
        await expect(widget.locator('canvas')).toBeVisible();

        // Конфіг графіка Filament віддає в x-data: тип і опції осі (формат вихідного HTML може змінитися — тоді тест підкаже).
        const config = await widget.locator('[x-data^="chart("]').getAttribute('x-data');
        expect(config).toContain("type: 'line'");
        expect(config).toContain('beginAtZero');
        expect(config).toContain('precision');
    });

    test('розподіл за статусом: горизонтальні бари з кількістю в підписі, від найбільшої групи', async ({ page }) => {
        const widget = page.locator('.fi-wi-chart', { hasText: 'Розподіл за статусом' });

        await expect(widget.locator('canvas')).toBeVisible();

        const config = await widget.locator('[x-data^="chart("]').getAttribute('x-data');
        expect(config).toContain("type: 'bar'");
        expect(config).toContain('indexAxis');
        // Тонкі заокруглені «таблетки» поверх світлої доріжки на всю ширину (довжина доріжки = найбільша група).
        expect(config).toContain('Найбільша група');
        expect(config).toContain('minBarLength');
        expect(config).toContain('borderRadius');
        expect(config).toContain('grouped');

        // E2ESeeder: «на балансі» — 11 активів (найбільша група), решта статусів по 2.
        const labels = [...config.matchAll(/на балансі — (\d+)/g)].map((m) => Number(m[1]));
        expect(labels).toEqual([11]);
        expect(config.indexOf('на балансі — 11')).toBeLessThan(config.indexOf('ремонт — 2'));

        // Колір стовпчика «на балансі» збігається з кольором success-бейджа (зелений).
        expect(config).toContain('#22c55e');
        // Легенди немає (підписи — на осі).
        await expect(widget.locator('.fi-wi-chart-legend, [class*="legend"]')).toHaveCount(0);
    });

    test('віджети не опитують сервер кожні 5 секунд', async ({ page }) => {
        // За замовчуванням віджети рендерять wire:poll.5s; опитування лишається тільки в сповіщень (30s).
        // Віджети підвантажуються лениво — спершу чекаємо всі три (інакше перевірка порожня).
        await expect(page.locator('.fi-wi-stats-overview').first()).toBeVisible();
        await expect(page.locator('.fi-wi-chart canvas')).toHaveCount(2);
        await expect(page.locator('[wire\\:poll\\.5s]')).toHaveCount(0);
        await expect(page.locator('[wire\\:poll\\.30s]')).toHaveCount(1);
    });
});
