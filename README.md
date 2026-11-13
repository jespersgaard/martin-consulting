# martin-consulting

Statisk konsulenthjemmeside for Martin Jespersgaard, bygget med Astro, TypeScript, Tailwind CSS v4 og Content Collections. Intet CMS, ingen database, ingen eksterne tjenester.

## Kommandoer

| Handling | Kommando |
|---|---|
| Installation | `npm install` |
| Udvikling (http://localhost:4321) | `npm run dev` |
| Build (output i `dist/`) | `npm run build` |
| Preview af build | `npm run preview` |

`npm run build` kører først `astro check` (typecheck) og derefter `astro build`.

## Mappestruktur

```
martin-consulting/
├── astro.config.mjs        # site-URL, sitemap, Tailwind
├── content/
│   ├── blog/               # Blogindlæg (.md)
│   ├── cases/              # Cases (.md)
│   └── pages/              # home, about, services, experience, contact (.md)
├── public/
│   ├── favicon.svg
│   └── images/
│       ├── blog/  cases/  profile/
│       └── og-default.png  # Standard Open Graph-billede (1200x630)
└── src/
    ├── content.config.ts   # Schemas for blog, cases og pages
    ├── components/         # Header, Footer, Hero, PostCard, PageView
    ├── layouts/BaseLayout.astro   # SEO, Open Graph, Twitter, JSON-LD, dark mode
    ├── lib/seo.ts          # SEO-hjælpefunktioner og schema.org (Person, BlogPosting, Article)
    ├── pages/              # Routes, robots.txt.ts, 404
    └── styles/global.css   # Tailwind og farver (#2563EB, #0F172A, #14B8A6)
```

**Før lancering:** ret domænet i `astro.config.mjs` (`site`) og `SITE` i `src/lib/seo.ts` (domæne, e-mail, LinkedIn).

## Tilføj en ny artikel

Opret en fil i `content/blog/`, fx `content/blog/min-artikel.md`. Filnavnet bliver URL'en (`/blog/min-artikel/`).

```md
---
title: Titel på artiklen
description: Kort beskrivelse til søgeresultater (maks. 170 tegn).
pubDate: 2025-12-01
updatedDate: 2025-12-10   # valgfri
author: Martin Jespersgaard
image: /images/blog/min-artikel.png
imageAlt: Beskrivelse af billedet
tags: [AI, Copilot]
draft: false              # true = vises ikke
---

Brødtekst i Markdown.
```

Cases tilføjes på samme måde i `content/cases/` med felterne `title`, `description`, `industry`, `technologies`, `published`, `image`.

## Tilføj billeder

1. Læg filen i `public/images/blog/`, `public/images/cases/` eller `public/images/profile/`.
2. Reference i frontmatter: `image: /images/blog/navn.png`.
3. I Markdown-teksten: `![Alternativ tekst](/images/blog/navn.png)`.

Anbefalet størrelse til forsidebilleder og Open Graph: 1200x630 px (PNG eller JPG, helst under 200 KB). Brug PNG/JPG til billeder, der skal deles på sociale medier; SVG vises ikke dér.

> Billeder i `public/` kopieres uændret til `dist/`. Ønsker du automatisk optimering via Astro Assets (`<Image />`), skal billederne ligge i `src/assets/` og importeres.

## Upload til Simply.com via FTP

1. Kør `npm run build`. Hele sitet ligger nu i `dist/`.
2. Find FTP-oplysninger i Simply.com kontrolpanel (vært, brugernavn, adgangskode). Brug SFTP/FTPS hvis muligt.
3. Åbn en FTP-klient (fx FileZilla) og forbind.
4. Gå til webroden på serveren (typisk `/public_html/` – tjek dit kontrolpanel).
5. Upload **indholdet** af `dist/` (ikke selve mappen `dist`), så `index.html` ligger direkte i webroden. Overskriv eksisterende filer.
6. Slet filer på serveren, der ikke længere findes i `dist/`.
7. Test forsiden, en blogartikel, `/robots.txt` og `/sitemap-index.xml`.

Hver ny udgivelse kræver nyt build og ny upload.

## Deploy via GitHub (Simply.com "Importer fra Git")

`./scripts/deploy.sh` bygger sitet og pusher indholdet af `dist/` til det offentlige repo `jespersgaard/martin-consulting-site`. Kræver `gh` (logget ind). I Simply.com bruges *Importer fra Git* med URL `https://github.com/jespersgaard/martin-consulting-site.git` og destinationsmappen `/public_html`.

## Kontaktformular

Formularen på `/kontakt/` sender til `public/send.php`, som bruges af PHP på Simply.com (`mail()`). Filen kopieres automatisk til `dist/` og uploades sammen med resten. Modtageren sættes i `$to` øverst i `send.php`. Formularen virker kun på serveren hos Simply.com, ikke i `npm run dev` eller `npm run preview`. Spam begrænses med flere lag: selvhostet open source-captcha [ALTCHA](https://altcha.org) (proof-of-work, ingen cookies, ingen eksterne tjenester; kræver PHP 8.1+), skjult honeypot-felt, kontrol af afsendersted, maks. 2 links i beskeden og maks. 5 beskeder pr. time pr. IP. ALTCHA-biblioteket ligger i `public/vendor/` (installeret med `composer require altcha-org/altcha`) og følger med i deploy. Den hemmelige nøgle oprettes automatisk som `.send-secret` på serveren og skjules af `.htaccess`.
