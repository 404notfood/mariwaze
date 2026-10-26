import path from 'node:path';
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        channel: process.env.PLAYWRIGHT_CHANNEL || undefined,
        baseURL: 'http://127.0.0.1:4173',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: [
        {
            command:
                'php tests/Browser/prepare.php && php -S 127.0.0.1:4173 -t public tests/Browser/server.php',
            url: 'http://127.0.0.1:4173',
            reuseExistingServer: false,
            timeout: 120_000,
            env: {
                APP_ENV: 'testing',
                APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
                APP_URL: 'http://127.0.0.1:4173',
                DB_CONNECTION: 'sqlite',
                DB_DATABASE: path.resolve('storage/browser-tests.sqlite'),
                DB_URL: '',
                CACHE_STORE: 'array',
                QUEUE_CONNECTION: 'sync',
                SESSION_DRIVER: 'file',
                SESSION_SECURE_COOKIE: 'false',
                MAIL_MAILER: 'array',
            },
        },
        {
            command: 'npm run dev -- --host 127.0.0.1 --port 5173',
            url: 'http://127.0.0.1:5173/@vite/client',
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
    ],
});
