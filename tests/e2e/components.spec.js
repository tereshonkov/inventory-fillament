import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './global-setup.js';
import { searchTable } from './helpers.js';

// Знаходить актив через пошук у списку й відкриває його картку.
async function openAsset(page, name) {
    await page.goto('/admin/assets');
    await searchTable(page, name);
    await page.getByRole('link', { name }).first().click();
    await expect(page.locator('.fi-header-heading')).toContainText(name);
}

test.describe('комплектуючі робочої станції', () => {
    test.use({ storageState: ADMIN_STATE });

    test('admin додає три монітори на картці робочої станції', async ({ page }) => {
        await openAsset(page, 'E2E Робоча станція');

        await page.getByRole('tab', { name: /Комплектуючі/ }).click();

        for (const serial of ['E2E-MON-1', 'E2E-MON-2', 'E2E-MON-3']) {
            await page.getByRole('button', { name: 'Додати', exact: true }).click();
            const modal = page.getByRole('dialog');
            await modal.getByRole('combobox', { name: /^Вид/ }).selectOption({ label: 'Монітор' });
            await modal.getByLabel('Серійний номер').fill(serial);
            await modal.getByRole('button', { name: 'Створити', exact: true }).click();
            await expect(page.locator('.fi-ta-row', { hasText: serial })).toBeVisible();
        }

        await expect(page.locator('.fi-ta-row', { hasText: 'Монітор' })).toHaveCount(4); // 3 щойно додані + 1 монітор із E2ESeeder
    });

    test('на картці активу іншої категорії вкладки комплектуючих немає', async ({ page }) => {
        await page.goto('/admin/assets/1');

        await expect(page.locator('.fi-header-heading')).toHaveText('E2E Тестовий актив');
        await expect(page.getByRole('tab', { name: /Комплектуючі/ })).toHaveCount(0);
    });
});
