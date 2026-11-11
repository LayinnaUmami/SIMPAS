import { expect, test } from "@playwright/test";

const adminCredentials = {
    username: "e2e-admin",
    password: "E2E-Admin-Test-2026",
};

const waliCredentials = {
    username: "e2e-wali-mobile",
    password: "E2E-Wali-Test-2026",
};

async function login(
    page: import("@playwright/test").Page,
    username: string,
    password: string,
) {
    await page.goto("/login");
    await page.getByLabel("Kode Akun / NIS").fill(username);
    await page.getByLabel("Password", { exact: true }).fill(password);
    await page.getByRole("button", { name: "Masuk" }).click();
}

async function isiFormSantri(
    page: import("@playwright/test").Page,
    data: { nis: string; nama: string },
) {
    await page.locator("#nis").fill(data.nis);
    await page.locator("#nama").fill(data.nama);
    await page.locator("#jenis_kelamin").selectOption("Putri");
    await page.locator("#status_aktif").selectOption("Aktif");
    await page.locator("#jenis-diniyah").selectOption("Diniyah");
    await page.locator("#level-diniyah").selectOption("1 Wustho");
}

test("login admin berhasil dan password salah ditolak", async ({ page }) => {
    await test.step("Given admin membuka halaman login", async () => {
        await page.goto("/login");
        await expect(page.getByRole("heading", { name: "Selamat datang kembali" })).toBeVisible();
    });

    await test.step("When admin mengirim password yang salah", async () => {
        await page.getByLabel("Kode Akun / NIS").fill(adminCredentials.username);
        await page.getByLabel("Password", { exact: true }).fill("password-salah");
        await page.getByRole("button", { name: "Masuk" }).click();
    });

    await test.step("Then sistem menolak login dengan pesan kesalahan", async () => {
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByRole("alert")).toContainText(
            "Kode akun/NIS atau password salah.",
        );
    });

    await test.step("When admin mengirim kredensial yang benar", async () => {
        await login(page, adminCredentials.username, adminCredentials.password);
    });

    await test.step("Then admin berhasil masuk ke dashboard", async () => {
        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.getByText("Admin / Pengurus")).toBeVisible();
    });
});

test("admin membuat akun wali baru, menautkan anak kedua, dan wali hanya melihat data anak", async ({
    page,
}) => {
    const runId = Date.now().toString();
    const waliUsername = `e2e-wali-${runId}`;
    const namaSantriPertama = `Santri E2E Pertama ${runId}`;
    const namaSantriKedua = `Santri E2E Kedua ${runId}`;

    await test.step("Given admin masuk dan membuka formulir tambah santri", async () => {
        await login(page, adminCredentials.username, adminCredentials.password);
        await expect(page).toHaveURL(/\/dashboard$/);
        await page.goto("/santri/create");
        await expect(page.getByRole("heading", { name: "Tambah Santri" })).toBeVisible();
    });

    await test.step("When admin menambahkan santri dengan akun wali baru", async () => {
        await isiFormSantri(page, {
            nis: `E2E-${runId}-001`,
            nama: namaSantriPertama,
        });
        await page.locator("#nama_wali").fill("Wali E2E Baru");
        await page.locator("#wali_username").fill(waliUsername);
        await page.locator("#wali_password").fill("E2E-Wali-Baru-2026");
        await Promise.all([
            page.waitForURL(/\/santri\/\d+$/, { timeout: 15_000 }),
            page.getByRole("button", { name: "Simpan" }).click(),
        ]);
    });

    await test.step("Then akun wali baru tampil di halaman Akun Pengguna", async () => {
        await page.goto("/akun");
        await expect(page.getByRole("heading", { name: "Kelola Akun Pengguna" })).toBeVisible();
        await expect(page.getByText(waliUsername, { exact: true })).toBeVisible();
        await expect(page.getByText(namaSantriPertama, { exact: true })).toBeVisible();
    });

    await test.step("When admin menambahkan santri kedua dan memilih wali yang sama", async () => {
        await page.goto("/santri/create");
        await isiFormSantri(page, {
            nis: `E2E-${runId}-002`,
            nama: namaSantriKedua,
        });
        const waliOption = page
            .locator("#wali_id option")
            .filter({ hasText: waliUsername });
        const waliId = await waliOption.getAttribute("value");
        expect(waliId).toBeTruthy();
        await page.locator("#wali_id").selectOption(waliId!);
        await expect(page.locator("#wali_username")).toBeDisabled();
        await Promise.all([
            page.waitForURL(/\/santri\/\d+$/, { timeout: 15_000 }),
            page.getByRole("button", { name: "Simpan" }).click(),
        ]);
    });

    await test.step("Then wali yang ada memiliki dua anak tanpa akun wali baru", async () => {
        await page.goto("/akun");
        await expect(page.getByText(namaSantriPertama, { exact: true })).toBeVisible();
        await expect(page.getByText(namaSantriKedua, { exact: true })).toBeVisible();
        await expect(
            page.locator("tbody tr").filter({ hasText: waliUsername }),
        ).toHaveCount(1);
    });

    await test.step("When wali masuk menggunakan akun yang baru dibuat", async () => {
        await page.getByRole("button", { name: "Keluar" }).click();
        await login(page, waliUsername, "E2E-Wali-Baru-2026");
    });

    await test.step("Then wali melihat dua anak dan tidak melihat tombol ubah data", async () => {
        await expect(page).toHaveURL(/\/wali\/dashboard$/);
        const anakSelector = page.locator("#wali-santri-select");
        await expect(anakSelector).toBeVisible();
        await expect(anakSelector.locator("option")).toHaveCount(2);
        await expect(anakSelector).toContainText(namaSantriPertama);
        await expect(anakSelector).toContainText(namaSantriKedua);
        await page.getByRole("link", { name: "Data Santri" }).click();
        await expect(page).toHaveURL(/\/santri\/\d+$/);
        await expect(page.getByRole("link", { name: "Edit", exact: true })).toHaveCount(0);
        await expect(page.getByRole("button", { name: /ubah data|edit/i })).toHaveCount(0);
    });
});

test("wali tidak dapat membuka detail santri milik wali lain", async ({ page }) => {
    let urlSantriWaliLain = "";

    await test.step("Given admin mencari tautan detail santri milik wali lain", async () => {
        await login(page, adminCredentials.username, adminCredentials.password);
        await page.goto("/santri");
        const barisSantri = page.getByRole("row").filter({
            hasText: "Anak Wali Lain",
        });
        await expect(barisSantri).toBeVisible();
        const tautanSantri = barisSantri.getByTitle("Lihat Detail");
        urlSantriWaliLain = (await tautanSantri.getAttribute("href")) ?? "";
        expect(urlSantriWaliLain).not.toBe("");
        await page.getByRole("button", { name: "Keluar" }).click();
    });

    await test.step("When wali masuk dengan akun yang hanya terhubung ke anaknya", async () => {
        await login(page, waliCredentials.username, waliCredentials.password);
        await expect(page).toHaveURL(/\/wali\/dashboard$/);
    });

    await test.step("When wali membuka URL detail santri milik wali lain", async () => {
        const response = await page.goto(urlSantriWaliLain);
        expect(response?.status()).toBe(403);
    });

    await test.step("Then sistem menolak akses ke detail tersebut", async () => {
        await expect(page.getByText("Anda tidak dapat melihat data Santri ini.")).toBeVisible();
    });
});

test("halaman login dan navigasi wali nyaman digunakan pada viewport mobile", async ({
    page,
}) => {
    await page.setViewportSize({ width: 375, height: 667 });

    await test.step("Given pengguna membuka login pada viewport 375x667", async () => {
        await page.goto("/login");
    });

    await test.step("Then halaman tidak memiliki scroll horizontal dan tombol Masuk terlihat", async () => {
        const lebarDokumen = await page.evaluate(
            () => document.documentElement.scrollWidth,
        );
        expect(lebarDokumen).toBeLessThanOrEqual(375);
        await expect(page.getByRole("button", { name: "Masuk" })).toBeInViewport();
    });

    await test.step("When wali login dan membuka navigasi sidebar", async () => {
        await login(page, waliCredentials.username, waliCredentials.password);
        await expect(page).toHaveURL(/\/wali\/dashboard$/);
        await page.locator("#topbar [data-toggle-sidebar]").click();
    });

    await test.step("Then wali dapat membuka navigasi dan berpindah halaman", async () => {
        await expect(page.locator("#sidebar")).toHaveClass(/open/);
        await page.locator("#sidebar").getByRole("link", { name: "Ranking" }).click();
        await expect(page).toHaveURL(/\/wali\/ranking$/);
        await expect(page.locator("#main")).toBeVisible();
    });
});
