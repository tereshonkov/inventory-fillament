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

    test('сторінка відкривається без JS-помилок', async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await page.goto(MAIN_ASSET_URL);
        await page.waitForLoadState('networkidle');

        expect(errors).toEqual([]);
    });
});
