import { defineConfig } from "vitepress";

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Duitku Laravel",
  description: "Duitku Payment Gateway SDK for Laravel",
  base: "/",
  head: [["link", { rel: "icon", href: "/favicon.ico" }]],
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: "Home", link: "/" },
      { text: "Guide", link: "/guide/introduction" },
    ],

    sidebar: [
      {
        text: "Getting Started",
        items: [
          { text: "Introduction", link: "/guide/introduction" },
          { text: "Installation", link: "/guide/installation" },
          { text: "Configuration", link: "/guide/configuration" },
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
          { text: "Error Handling", link: "/guide/error-handling" },
          { text: "Blade Components", link: "/guide/blade-components" },
        ],
      },
    ],

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
