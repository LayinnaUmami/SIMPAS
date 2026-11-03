import { closeSync, existsSync, openSync } from "node:fs";
import { resolve } from "node:path";
import { spawnSync } from "node:child_process";

const projectRoot = resolve(import.meta.dirname, "..");
const databasePath = resolve(projectRoot, "database", "e2e.sqlite");
const configuredPath = process.env.E2E_DB_PATH
    ? resolve(projectRoot, process.env.E2E_DB_PATH)
    : databasePath;

if (configuredPath !== databasePath) {
    throw new Error(
        `E2E dibatasi ke database SQLite ${databasePath}; target lain ditolak.`,
    );
}

if (!existsSync(databasePath)) {
    closeSync(openSync(databasePath, "wx"));
}

const laragonPhp = "C:\\laragon\\bin\\php\\php-8.3.33-Win32-vs16-x64\\php.exe";
const phpBinary =
    process.env.PHP_BINARY ??
    (existsSync(laragonPhp) ? laragonPhp : "php");

const result = spawnSync(
    phpBinary,
    ["scripts/migrate-e2e.php"],
    {
        cwd: projectRoot,
        env: {
            ...process.env,
            APP_ENV: "e2e",
            DB_CONNECTION: "sqlite",
            DB_DATABASE: databasePath,
            CACHE_STORE: "database",
        },
        stdio: "inherit",
        windowsHide: true,
    },
);

if (result.error) {
    throw result.error;
}

if (result.status !== 0) {
    process.exit(result.status ?? 1);
}
