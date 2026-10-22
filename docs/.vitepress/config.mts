import { defineConfig } from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Duitku Laravel",
  description: "Duitku Payment Gateway SDK for Laravel",
  base: process.env.DOCS_BASE_PATH || "/",
  head: [["link", { rel: "icon", href: "/favicon.ico" }]],
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: "Home", link: "/" },
      { text: "Guide", link: "/guide/introduction" },
      {
        text: "Versi dokumentasi",
        items: [
          { text: "3.x — terbaru", link: "/" },
          { text: "2.x — arsip", link: "/v2/" },
        ],
      },
    ],

    sidebar: {
      "/v2/": [
        {
          text: "Dokumentasi v2 (arsip)",
          items: [
            { text: "Home", link: "/v2/" },
            { text: "Introduction", link: "/v2/guide/introduction" },
            { text: "Installation", link: "/v2/guide/installation" },
            { text: "Configuration", link: "/v2/guide/configuration" },
            { text: "Payments", link: "/v2/guide/usage-payments" },
            { text: "Duitku POP", link: "/v2/guide/usage-pop" },
            { text: "Disbursement", link: "/v2/guide/usage-disbursement" },
            { text: "Callback System", link: "/v2/guide/callback-system" },
            { text: "Error Handling", link: "/v2/guide/error-handling" },
            { text: "Blade Components", link: "/v2/guide/blade-components" },
          ],
        },
      ],
      "/": [
        {
          text: "Getting Started",
          items: [
            { text: "Introduction", link: "/guide/introduction" },
            { text: "Installation", link: "/guide/installation" },
            { text: "Configuration", link: "/guide/configuration" },
            { text: "Migrasi major", link: "/guide/upgrading" },
          ],
        },
        {
          text: "Core Usage",
          items: [
            { text: "Payments", link: "/guide/usage-payments" },
            { text: "Duitku POP", link: "/guide/usage-pop" },
            { text: "Disbursement", link: "/guide/usage-disbursement" },
          ],
        },
        {
          text: "Advanced",
          items: [
            { text: "Callback System", link: "/guide/callback-system" },
            { text: "Diagnosis dan Testing", link: "/guide/testing" },
            { text: "Pemantauan Duitku", link: "/guide/maintenance" },
            { text: "Versi dokumentasi", link: "/guide/versions" },
            { text: "Kompatibilitas", link: "/compatibility" },
            { text: "Error Handling", link: "/guide/error-handling" },
            { text: "Blade Components", link: "/guide/blade-components" },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: "github", link: "https://github.com/HasanH47/duitku-laravel" },
    ],

    footer: {
      message: "Released under the MIT License.",
      copyright: "Copyright © 2026-present Hasan H",
    },

    search: {
      provider: "local",
    },
  },
});
