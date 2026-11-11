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

    test('картка станції показує склад за видами з даними кожної одиниці', async ({ page }) => {
        await openAsset(page, 'E2E Робоча станція');

        const composition = page.getByTestId('asset-composition');
        await expect(page.getByText('Склад станції')).toBeVisible();

        // Порядок видів: монітор → клавіатура → ДБЖ; принтера і мишки немає — груп для них теж немає.
        const kinds = await composition.locator('[data-kind]').evaluateAll((nodes) => nodes.map((n) => n.dataset.kind));
        expect(kinds).toEqual(['monitor', 'keyboard', 'ups']);

        const monitor = composition.locator('[data-kind="monitor"] tr', { hasText: 'E2E-MON-SEED' });
        await expect(monitor).toContainText('Склад-915');
        await expect(monitor).toContainText('Несправна');
        await expect(monitor).toContainText('Нотатка E2E монітора');

        // Порожня локація — беруться дані станції.
        const keyboard = composition.locator('[data-kind="keyboard"] tr', { hasText: 'E2E-KB-SEED' });
        await expect(keyboard).toContainText('E2E Склад');
        await expect(keyboard).toContainText('як у станції');
        await expect(keyboard).toContainText('Справна');

        // Нотатка монітора не потрапляє до інших одиниць.
        await expect(composition.locator('[data-kind="ups"]')).not.toContainText('Нотатка E2E монітора');
    });

    test('правка однієї одиниці не змінює інші', async ({ page }) => {
        await openAsset(page, 'E2E Робоча станція');
        await page.getByRole('tab', { name: /Комплектуючі/ }).click();

        const row = page.locator('.fi-ta-row', { hasText: 'E2E-KB-SEED' });
        await row.getByRole('button', { name: 'Змінити' }).click();

        const modal = page.getByRole('dialog');
        await modal.getByRole('switch', { name: 'Справна' }).click();
        await modal.getByRole('button', { name: 'Зберегти зміни' }).click();
        await expect(page.getByRole('dialog')).toHaveCount(0);

        await page.reload();
        const composition = page.getByTestId('asset-composition');
        await expect(composition.locator('[data-kind="keyboard"] tr', { hasText: 'E2E-KB-SEED' })).toContainText('Несправна');
        await expect(composition.locator('[data-kind="monitor"] tr', { hasText: 'E2E-MON-SEED' })).toContainText('Несправна');
        await expect(composition.locator('[data-kind="ups"] tr').first()).toContainText('Справна');
    });

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

test.describe('загальний список комплектуючих', () => {
    test.use({ storageState: ADMIN_STATE });

    test('фільтр за видом показує одиниці лише цього виду, інвентарний номер і станцію', async ({ page }) => {
        await page.goto('/admin/asset-components?filters[kind][value]=monitor');

        const rows = page.locator('.fi-ta-row');
        const seeded = rows.filter({ hasText: 'E2E-MON-SEED' });

        await expect(seeded).toBeVisible();
        await expect(seeded).toContainText('Склад-915');
        await expect(seeded).toContainText('101000777');
        await expect(seeded).toContainText('E2E Робоча станція');
        await expect(rows.filter({ hasText: 'E2E-KB-SEED' })).toHaveCount(0);
    });

    test('пошук за серійним номером знаходить одиницю, рядок веде на картку станції', async ({ page }) => {
        await page.goto('/admin/asset-components');
        await searchTable(page, 'E2E-KB-SEED');

        const rows = page.locator('.fi-ta-row');
        await expect(rows).toHaveCount(1);
        await expect(rows.first()).toContainText('Клавіатура');
        await expect(rows.first()).toContainText('як у станції');

        await rows.first().locator('a').first().click();
        await expect(page.locator('.fi-header-heading')).toContainText('E2E Робоча станція');
    });
});
