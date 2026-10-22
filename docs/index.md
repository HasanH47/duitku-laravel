---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "Duitku Laravel"
  text: "Professional Payment SDK"
  tagline: "Integrasi komunitas independen untuk Duitku. Dokumentasi v3.x; pilih versi yang sesuai dengan package kamu."
  image:
    src: /logo.png
    alt: Duitku Laravel
  actions:
    - theme: brand
      text: Get Started
      link: /guide/introduction
    - theme: alt
      text: "Dokumentasi v2 (arsip)"
      link: /v2/
    - theme: alt
      text: View on GitHub
      link: https://github.com/HasanH47/duitku-laravel

features:
  - icon: ⚡️
    title: Event Driven
    details: Gunakan Laravel Events untuk menangani callback pembayaran secara asinkron dan terorganisir.
  - icon: 🛡️
    title: Type Safe
    details: Dilengkapi dengan Custom Exceptions dan DTO untuk memastikan data yang Anda olah selalu valid.
  - icon: 🚀
    title: High Performance
    details: Mendukung Parallel Inquiry untuk Disbursement massal menggunakan HTTP Pool Laravel.
  - icon: 📦
    title: Blade Components
    details: Pasang widget Duitku POP secepat kilat dengan Blade Component bawaan.
---

Package ini bukan SDK resmi dan tidak berafiliasi atau mendapat endorsement dari Duitku. Lihat [cakupan kompatibilitas](/compatibility) dan [hasil verifikasi sandbox](./reviews/2026-10-22-sandbox).
