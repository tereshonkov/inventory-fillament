import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './global-setup.js';

test.describe('сторінка «Огляд майна»', () => {
    test.use({ storageState: ADMIN_STATE });

    test.beforeEach(async ({ page }) => {
        await page.goto('/admin');
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

    test('на широкому екрані графік активності вдвічі ширший за графік статусів', async ({ page }) => {
        const activity = page.locator('.fi-wi-chart', { hasText: 'Активність за останні 6 місяців' });
        const statuses = page.locator('.fi-wi-chart', { hasText: 'Розподіл за статусом' });

        const a = await activity.boundingBox();
        const s = await statuses.boundingBox();

        expect(a.width / s.width).toBeGreaterThan(1.6);
        expect(a.width / s.width).toBeLessThan(2.4);
    });

    test('віджет цифр не опитує сервер кожні 5 секунд', async ({ page }) => {
        // За замовчуванням Stats Overview рендерить wire:poll.5s; опитування лишається тільки в сповіщень (30s).
        // Виджети підвантажуються лениво — спершу чекаємо, поки віджет цифр з'явиться (інакше перевірка порожня).
        await expect(page.locator('.fi-wi-stats-overview').first()).toBeVisible();
        await expect(page.locator('[wire\\:poll\\.5s]')).toHaveCount(0);
        await expect(page.locator('[wire\\:poll\\.30s]')).toHaveCount(1);
    });
});
