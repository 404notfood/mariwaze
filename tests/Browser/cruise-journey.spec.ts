import { readFile } from 'node:fs/promises';
import { expect, test } from '@playwright/test';

test('préparer une croisière, retenir une route et emporter le carnet hors connexion', async ({
    page,
    context,
}, testInfo) => {
    await page.goto('/login');
    await page.getByLabel('Adresse e-mail').fill('browser@example.test');
    await page
        .getByLabel('Mot de passe', { exact: true })
        .fill('Browser-test-123!');
    await page
        .getByRole('button', { name: 'Se connecter', exact: true })
        .click();
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/croisieres');
    await expect(
        page.getByRole('heading', {
            name: 'Votre prochaine aventure commence ici',
        }),
    ).toBeVisible();
    await page
        .getByRole('link', { name: 'Préparer ma première croisière' })
        .click();
    await expect(page).toHaveURL(/croisieres\/nouvelle/);
    await page.goto('/bateau/configurer');
    await page.getByLabel('Nom du bateau').fill('Étoile de test');
    await page.getByRole('button', { name: 'Enregistrer le bateau' }).click();
    await expect(page).toHaveURL(/\/bateau$/);
    await page.goto('/croisieres/nouvelle');
    await page.getByLabel('Nom de la croisière').fill('Voyage navigateur');
    await page
        .getByRole('combobox', { name: 'Port de départ' })
        .fill('Port Navigo');
    await expect(page.getByRole('option', { name: /Port Navigo/ })).toHaveCount(
        2,
    );
    await page.getByRole('option', { name: /Port Navigo.*FR/ }).click();
    await page
        .getByRole('combobox', { name: 'Destination', exact: true })
        .fill('Port Horizon');
    await page.getByRole('option', { name: /Port Horizon/ }).click();
    const departure = new Date();
    departure.setDate(departure.getDate() + 7);
    await page
        .getByLabel('Date de départ')
        .fill(departure.toISOString().slice(0, 10));
    await page
        .getByRole('button', { name: 'Créer la croisière', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Voyage navigateur', exact: true }),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'Calculer les itinéraires', exact: true })
        .first()
        .click();
    await expect(
        page.getByRole('heading', { name: 'Comprendre cette proposition' }),
    ).toBeVisible();
    await page.getByRole('button', { name: 'Découverte', exact: true }).click();
    const discovery = page.getByRole('article').filter({
        has: page.getByRole('button', { name: 'Découverte', exact: true }),
    });
    await discovery
        .getByRole('button', { name: 'Retenir cet itinéraire' })
        .click();
    await expect(discovery.getByText('Itinéraire retenu')).toBeVisible();
    const checklist = page.getByRole('region', {
        name: 'Checklist de préparation',
    });
    await checklist.getByRole('checkbox').first().check();
    await expect(checklist.getByRole('checkbox').first()).toBeEnabled();
    await page.reload();
    await expect(checklist.getByRole('checkbox').first()).toBeChecked();
    await page.screenshot({
        path: testInfo.outputPath('croisiere.png'),
        fullPage: true,
    });
    const gpxDownload = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Exporter le GPX retenu' }).click();
    const gpx = await gpxDownload;
    const gpxPath = testInfo.outputPath('route.gpx');
    await gpx.saveAs(gpxPath);
    expect(await readFile(gpxPath, 'utf8')).toContain('Découverte');
    const bookletDownload = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Carnet hors connexion' }).click();
    const booklet = await bookletDownload;
    const bookletPath = testInfo.outputPath('carnet.html');
    await booklet.saveAs(bookletPath);
    await context.setOffline(true);
    await page.goto(
        new URL(`file:///${bookletPath.replaceAll('\\', '/')}`).href,
    );
    await expect(
        page.getByRole('heading', { name: 'Voyage navigateur', exact: true }),
    ).toBeVisible();
    await expect(page.getByRole('checkbox').first()).toBeChecked();
    await expect(
        page.getByText(/Port Navigo → Port Horizon/).first(),
    ).toBeVisible();
    await page.screenshot({
        path: testInfo.outputPath('carnet-hors-ligne.png'),
        fullPage: true,
    });
});

test('parcourir les escales conserve les filtres et le trajet sur mobile', async ({
    page,
}, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await page.getByLabel('Adresse e-mail').fill('browser@example.test');
    await page
        .getByLabel('Mot de passe', { exact: true })
        .fill('Browser-test-123!');
    await page
        .getByRole('button', { name: 'Se connecter', exact: true })
        .click();
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/explorer');
    const departure = page.getByRole('combobox', {
        name: 'Départ',
        exact: true,
    });
    await departure.fill('Port Navigo');
    await page.getByRole('option', { name: /Port Navigo.*FR/ }).click();
    const search = page.getByRole('textbox', { name: 'Rechercher une escale' });
    await search.fill('Escale');
    await page.getByLabel('Service au port').selectOption('has_water');
    await page.getByRole('button', { name: 'Rechercher', exact: true }).click();
    await expect(
        page.getByText('1–24 sur 26 escales', { exact: true }),
    ).toBeVisible();
    const pagination = page.getByRole('navigation', {
        name: 'Pagination des escales',
    });
    await pagination.getByRole('link', { name: 'Suivant' }).click();
    await expect(
        page.getByText('25–26 sur 26 escales', { exact: true }),
    ).toBeVisible();
    await expect(page.getByText('Z Escale 26', { exact: true })).toBeVisible();
    await expect(search).toHaveValue('Escale');
    await expect(page.getByLabel('Service au port')).toHaveValue('has_water');
    await expect(departure).toHaveValue('Port Navigo');
    await expect(
        pagination.getByRole('button', { name: 'Suivant' }),
    ).toBeDisabled();
    await page.screenshot({
        path: testInfo.outputPath('exploration-mobile.png'),
        fullPage: true,
    });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
    await page.getByRole('link', { name: 'Réinitialiser les filtres' }).click();
    await expect(search).toHaveValue('');
    await expect(departure).toHaveValue('Port Navigo');
    await page.goBack();
    await expect(search).toHaveValue('Escale');
    await expect(page.getByLabel('Service au port')).toHaveValue('has_water');
    await expect(
        page.getByText('25–26 sur 26 escales', { exact: true }),
    ).toBeVisible();
});

test('la recherche signale une panne et les filtres sont utilisables sur mobile', async ({
    page,
}) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await page.getByLabel('Adresse e-mail').fill('browser@example.test');
    await page
        .getByLabel('Mot de passe', { exact: true })
        .fill('Browser-test-123!');
    await page
        .getByRole('button', { name: 'Se connecter', exact: true })
        .click();
    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/explorer');
    await page.getByLabel('Profondeur minimale (m)').fill('20');
    await page.getByRole('button', { name: 'Rechercher', exact: true }).click();
    await expect(
        page.getByText('Aucune escale ne correspond à cette recherche.'),
    ).toBeVisible();
    await page.getByRole('link', { name: 'Réinitialiser les filtres' }).click();
    await expect(page.getByText('Port Horizon', { exact: true })).toBeVisible();
    await page.route('**/api/places?*', (route) =>
        route.fulfill({ status: 503, body: '{}' }),
    );
    await page
        .getByRole('combobox', { name: 'Départ', exact: true })
        .fill('Port');
    await expect(
        page.getByText(
            'Recherche indisponible. Réessayez en modifiant le nom.',
        ),
    ).toBeVisible();
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBe(true);
});
