---
name: jespersgaard-blog
description: "Use when the user runs /jespersgaard-blog followed by text, notes or links and wants a Danish blog post for martin-consulting: writes the post in content/blog, creates a cover image, renders diagrams as PNG and verifies the build. Invoke for blog, blogindlæg, jespersgaard-blog."
---

# jespersgaard-blog

Repo: `/home/martin/projects/martin-consulting` (Astro). Input after the command is either raw text/notes or URLs.

## Steps

1. **Gather source.** For URLs, `web_fetch` each one (use `start_index` to read truncated pages). If a link cannot be read, say so in the final reply. Add missing context from own knowledge, but do not invent facts, quotes or numbers.
2. **Write the post** in Danish at `content/blog/<slug>.md` (slug = lowercase ASCII, hyphens; filename becomes the URL). Frontmatter follows `src/content.config.ts`:
   ```yaml
   ---
   title: ...
   description: ...        # max 170 characters
   pubDate: YYYY-MM-DD     # today
   author: Martin Jespersgaard
   image: /images/blog/<slug>.png
   imageAlt: ...
   tags: [..]
   draft: false
   ---
   ```
   No H1 in the body (title comes from frontmatter). Start with a short intro, use `##` sections, tables where comparing, and a conclusion. Match the tone of existing posts in `content/blog/`. Do NOT add a "Kilder"/sources section or links to the source articles unless the user asks.
3. **Cover image** `public/images/blog/<slug>.png`, 1200x630, in the style of the existing covers (view `public/images/blog/ai-fejl.png`): dark gradient `#0f172a` to `#1e3a8a`, teal circle top right (`#14b8a6`, opacity .25), blue circle bottom right (`#3b82f6`, opacity .45), white bold title at x=80, subtitle, teal bar (`#14b8a6`), author name. Write an SVG to /tmp, then convert with `node -e "require('sharp')('/tmp/cover.svg').png().toFile('public/images/blog/<slug>.png')"` from the repo root. View the result.
4. **Diagrams** where they help understanding (processes, architectures, decision trees, comparisons; typically 2-5, never decorative). The site has no Mermaid support, so render to PNG:
   - Install in /tmp: `npm i @mermaid-js/mermaid-cli`; config `{"args":["--no-sandbox"]}` passed with `-p`.
   - `npx mmdc -i d.mmd -o d.png -p p.json -b white -s 2`
   - If chrome fails with missing `libasound.so.2`: `sudo -A apt-get install -y libasound2t64`.
   - Avoid double quotes inside edge labels (parse errors). Keep diagrams roughly landscape: split wide flows into two, simplify tall decision trees. View each PNG before use.
   - Save to `public/images/blog/<slug>/<name>.png` and embed with `![descriptive alt](/images/blog/<slug>/<name>.png)`. Never leave mermaid code blocks in the post.
5. **Blog index and tags.** `src/pages/blog/index.astro` lists all non-draft posts and shows a tag cloud (`src/components/TagCloud.astro`); each tag gets a static page at `/blog/tag/<slug>/` (`src/pages/blog/tag/[tag].astro`, helpers in `src/lib/blog.ts`). Both are generated from frontmatter `tags`, so no manual edit is needed. Reuse existing tags (check `grep -h "^tags" content/blog/*.md`) and keep spelling/casing identical; use 2-4 tags. Tell the user the site must be rebuilt/redeployed (or `npm run dev` restarted) before the post is visible.
6. **Verify.** Run `npx astro build` in the repo root; confirm no errors and that `dist/blog/<slug>/index.html` exists and `dist/blog/index.html` contains the slug. Remove temp files in /tmp.
7. **Report briefly** in Danish: file paths, images made, anything unreadable. Do not commit unless asked.
