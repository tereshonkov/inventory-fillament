import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './global-setup.js';

// Головний актив E2ESeeder створюється першим — id = 1.
const MAIN_ASSET_URL = '/admin/assets/1';

test.describe('картка активу', () => {
    test.use({ storageState: ADMIN_STATE });

    test.beforeEach(async ({ page }) => {
        await page.goto(MAIN_ASSET_URL);
    });

    test('заголовок містить назву, підзаголовок — інвентарний номер', async ({ page }) => {
        await expect(page.locator('.fi-header-heading')).toHaveText('E2E Тестовий актив');
        await expect(page.locator('.fi-header-subheading')).toContainText('№ 101000001');
    });

    test('статус та ключові факти стоять у верхній зоні, вище секції відповідальності', async ({ page }) => {
        const badge = page.locator('.fi-badge', { hasText: 'на балансі' }).first();
        const responsibility = page.getByText('Відповідальність та ідентифікація');

        await expect(badge).toBeVisible();
        await expect(responsibility).toBeVisible();

        const badgeBox = await badge.boundingBox();
        const sectionBox = await responsibility.boundingBox();
        expect(badgeBox.y).toBeLessThan(sectionBox.y);
    });

    test('рік показується без роздільника тисяч', async ({ page }) => {
        await expect(page.getByText('2024', { exact: true })).toBeVisible();
        // Роздільник залежить від локалі: пробіл, NBSP (160), вузький NBSP (8239), кома або крапка.
        const separators = String.fromCharCode(160, 8239);
        await expect(page.locator('main')).not.toContainText(new RegExp(`2[\\s${separators},.]024`));
    });

    test('службова інформація згорнута й розкривається', async ({ page }) => {
        const label = page.getByText('Створено', { exact: true });

        await expect(label).toBeHidden();

        await page.getByText('Службова інформація').click();

        await expect(label).toBeVisible();
    });

    test('шапка — одна картка з обома зонами (однакова висота за побудовою)', async ({ page }) => {
        const headerCard = page.locator('.fi-section', { hasText: 'Інвентарний номер' }).filter({ hasText: 'Локація' });

        await expect(headerCard).toHaveCount(1);
    });

    test('картки інфосписку не вкладені в одну велику рамку, історія — окрема картка', async ({ page }) => {
        // Загальна сітка сторінки без власної рамки (інакше всі картки опиняються в одній великій).
        await expect(page.locator('.fi-page-content > .fi-grid').first()).toHaveCSS('border-top-width', '0px');

        // Блок вкладок історії — окрема картка з рамкою, яка не містить карток інфосписку.
        const history = page.locator('.fi-grid-col', { has: page.locator('.fi-sc-tabs') });
        await expect(history).toHaveCSS('border-top-width', '1px');
        await expect(history.getByText('Відповідальність та ідентифікація')).toHaveCount(0);
        await expect(history.getByText('Службова інформація')).toHaveCount(0);
    });

    test('сторінка відкривається без JS-помилок', async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await page.goto(MAIN_ASSET_URL);
        await page.waitForLoadState('networkidle');

        expect(errors).toEqual([]);
    });
});

test.describe('сторінка списку активів', () => {
    test.use({ storageState: ADMIN_STATE });

    test('вкладки та таблиця лишаються в одній рамці', async ({ page }) => {
        await page.goto('/admin/assets');

        await expect(page.locator('.fi-page-content > .fi-grid').first()).toHaveCSS('border-top-width', '1px');
    });
});

test.describe('картка активу з дуже довгою назвою', () => {
    test.use({ storageState: ADMIN_STATE });

    test('заголовок показує не більше 4 слів, повна назва — у картці', async ({ page }) => {
        await page.goto('/admin/assets/2'); // E2ESeeder::LONG_NAME_ASSET_ID

        const heading = page.locator('.fi-header-heading');
        await expect(heading).toHaveText('Системний блок у зборі…');
        expect((await heading.innerText()).replace('…', '').trim().split(/\s+/).length).toBeLessThanOrEqual(4);

        // Повна назва доступна в картці (обмежена трьома рядками), а не в заголовку.
        await expect(page.getByText('Повна назва')).toBeVisible();
        await expect(page.getByText('Другий абзац із додатковим описом комплектації').first()).toBeAttached();
    });

    test('«Повна назва» і «Примітки»: підпис над значенням, а не на третину рядка збоку', async ({ page }) => {
        await page.goto('/admin/assets/2');

        for (const label of ['Повна назва', 'Примітки']) {
            const entry = page.locator('.fi-in-entry', { has: page.getByText(label, { exact: true }) });
            const labelBox = await entry.getByText(label, { exact: true }).boundingBox();
            const valueBox = await entry.locator('.fi-in-entry-content, .fi-in-text').first().boundingBox();

            expect(Math.abs(valueBox.x - labelBox.x)).toBeLessThan(8);
            expect(valueBox.y).toBeGreaterThan(labelBox.y);
        }
    });
});
