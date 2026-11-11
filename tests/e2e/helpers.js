/** Вводить текст у пошук таблиці Filament і чекає відповіді Livewire. */
export async function searchTable(page, text) {
    const updated = page.waitForResponse((r) => r.url().includes('/livewire') && r.request().method() === 'POST');
    await page.locator('.fi-ta-search-field input').fill(text);
    await updated;
}
