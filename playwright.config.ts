import { existsSync } from "node:fs";
import { resolve } from "node:path";
import { defineConfig, devices } from "@playwright/test";

const laragonPhp =
    "C:\\laragon\\bin\\php\\php-8.3.33-Win32-vs16-x64\\php.exe";
const phpBinary =
    process.env.PHP_BINARY ??
    (existsSync(laragonPhp) ? laragonPhp : "php");
const baseURL = "http://127.0.0.1:8412";
const databasePath = resolve("database", "e2e.sqlite");

export default defineConfig({
    testDir: "./e2e",
    timeout: 90_000,
    fullyParallel: false,
    workers: 1,
    reporter: [["list"], ["html", { open: "never" }]],
    use: {
        baseURL,
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
    },
    expect: {
        timeout: 15_000,
    },
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
    ],
    webServer: {
        command: `"${phpBinary}" artisan serve --host=127.0.0.1 --port=8412 --env=e2e`,
        url: baseURL,
        reuseExistingServer: false,
        timeout: 60_000,
        env: {
            APP_ENV: "e2e",
            DB_CONNECTION: "sqlite",
            DB_DATABASE: databasePath,
            CACHE_STORE: "database",
        },
    },
});
