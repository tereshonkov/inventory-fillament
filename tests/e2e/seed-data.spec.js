import { test, expect } from '@playwright/test';
import { searchTable } from './helpers.js';

// Перевіряє, що e2e-сідер дає editor'у лише «свої» активи (основа для скоупів у наступних кроках плану).
test('editor бачить у списку лише свої активи', async ({ page }) => {
    await page.goto('/admin/login');
    await page.locator('input[type="email"]').fill('editor@e2e.test');
    await page.locator('input[type="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin(\/)?$/, { timeout: 60_000 });

    await page.goto('/admin/assets');

    await searchTable(page, 'E2E Свій balance');
    await expect(page.getByText('E2E Свій balance').first()).toBeVisible();

    await searchTable(page, 'E2E Чужий');
    // Рядки таблиці (текст пошукового індикатора теж містить фразу, тому getByText не підходить).
    await expect(page.locator('.fi-ta-row')).toHaveCount(0);
});
