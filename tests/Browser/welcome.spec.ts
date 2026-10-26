import { expect, test } from '@playwright/test';

test('the public landing page is usable', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/Mariwaze/);
    await expect(
        page.getByRole('heading', {
            level: 1,
            name: /Préparez votre voyage en mer avec un copilote fiable/i,
        }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: /créer mon compte/i }),
    ).toBeVisible();
});
