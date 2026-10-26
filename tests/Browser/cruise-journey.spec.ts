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

test('préparer Saint-Malo Pointe-à-Pitre en sélectionnant la Guadeloupe plutôt que son homonyme', async ({
    page,
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
    await page.goto('/croisieres/nouvelle');
    await page.getByLabel('Nom de la croisière').fill('Traversée Guadeloupe');
    await page
        .getByRole('combobox', { name: 'Port de départ' })
        .fill('Saint-Malo');
    await page.getByRole('option', { name: /Saint-Malo/ }).click();
    await page
        .getByRole('combobox', { name: 'Destination', exact: true })
        .fill('Pointe-à-Pitre');
    await expect(
        page.getByRole('option', { name: /Pointe à Pitre.*FR/ }),
    ).toBeVisible();
    await page
        .getByRole('button', {
            name: 'Élargir la recherche en ligne',
            exact: true,
        })
        .click();
    const guadeloupe = page.getByRole('option', {
        name: /Pointe-à-Pitre.*Guadeloupe.*GP/,
    });
    await expect(guadeloupe).toBeVisible();
    await page.screenshot({
        path: testInfo.outputPath('recherche-guadeloupe.png'),
        fullPage: true,
    });
    await guadeloupe.click();
    await expect
        .poll(async () =>
            Number(
                await page
                    .getByLabel('Durée (jours)', { exact: true })
                    .inputValue(),
            ),
        )
        .toBeGreaterThan(80);
    const departure = new Date();
    departure.setDate(departure.getDate() + 7);
    await page
        .getByLabel('Date de départ')
        .fill(departure.toISOString().slice(0, 10));
    await page
        .getByRole('button', { name: 'Créer la croisière', exact: true })
        .click();
    await expect(
        page.getByRole('heading', {
            name: 'Traversée Guadeloupe',
            exact: true,
        }),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'Calculer les itinéraires', exact: true })
        .first()
        .click();
    await expect(
        page.getByRole('heading', { name: 'Comprendre cette proposition' }),
    ).toBeVisible();
});

test('créer une croisière en cliquant deux coordonnées GPS sans rechercher de lieux', async ({
    page,
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
    await page.goto('/croisieres/nouvelle');
    await page
        .getByLabel('Nom de la croisière')
        .fill('Navigation par coordonnées');
    const map = page.getByLabel('Carte interactive de préparation');
    await expect(map.locator('.leaflet-control-zoom-in')).toBeVisible();
    await map.click({ position: { x: 230, y: 180 } });
    const departure = page.getByRole('combobox', { name: 'Port de départ' });
    await expect(departure).toHaveValue(/^GPS /);
    const departureValue = await departure.inputValue();
    await expect(
        page.getByRole('button', { name: 'Placer l’arrivée', exact: true }),
    ).toHaveAttribute('aria-pressed', 'true');
    await expect(map.locator('.leaflet-control-zoom-in')).toBeVisible();
    await map.click({ position: { x: 290, y: 240 } });
    const destination = page.getByRole('combobox', {
        name: 'Destination',
        exact: true,
    });
    await expect(destination).toHaveValue(/^GPS /);
    const destinationValue = await destination.inputValue();
    expect(destinationValue).not.toBe(departureValue);
    await page.screenshot({
        path: testInfo.outputPath('selection-gps.png'),
        fullPage: true,
    });
    const date = new Date();
    date.setDate(date.getDate() + 7);
    await page
        .getByLabel('Date de départ')
        .fill(date.toISOString().slice(0, 10));
    await page
        .getByRole('button', { name: 'Créer la croisière', exact: true })
        .click();
    await expect(
        page.getByRole('heading', {
            name: 'Navigation par coordonnées',
            exact: true,
        }),
    ).toBeVisible();
    const cruiseUrl = page.url();
    await page.goto(`${cruiseUrl}/modifier`);
    await expect(departure).toHaveValue(departureValue);
    await expect(destination).toHaveValue(destinationValue);
    await expect(map.locator('.mariwaze-point-marker')).toHaveCount(2);
});

test('le catalogue mondial trouve les noms traduits et filtre les ports du Pacifique', async ({
    page,
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
    await page.goto('/croisieres/nouvelle');
    await page
        .getByRole('combobox', { name: 'Port de départ' })
        .fill('Lisbonne');
    await expect(
        page.getByRole('option', { name: /Lisbon.*PT/ }),
    ).toBeVisible();
    await page.goto('/explorer');
    await page.getByLabel('Pays ou territoire').selectOption('FJ');
    await page.getByRole('button', { name: 'Rechercher', exact: true }).click();
    await expect(page.getByText('Suva Harbor', { exact: true })).toBeVisible();
    await expect(
        page.getByText('Port Horizon', { exact: true }),
    ).not.toBeVisible();
    await page.screenshot({
        path: testInfo.outputPath('catalogue-pacifique.png'),
        fullPage: true,
    });
});
