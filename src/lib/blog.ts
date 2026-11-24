import { getCollection } from 'astro:content';

export function tagSlug(tag: string): string {
  return tag
    .toLowerCase()
    .replace(/æ/g, 'ae')
    .replace(/ø/g, 'oe')
    .replace(/å/g, 'aa')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export async function getPublishedPosts() {
  return (await getCollection('blog', (p) => !p.data.draft)).sort(
    (a, b) => b.data.pubDate.valueOf() - a.data.pubDate.valueOf(),
  );
}

export async function getTagCounts() {
  const counts = new Map<string, number>();
  for (const p of await getPublishedPosts()) {
    for (const t of p.data.tags) counts.set(t, (counts.get(t) ?? 0) + 1);
  }
  return [...counts.entries()]
    .map(([tag, count]) => ({ tag, count, slug: tagSlug(tag) }))
    .sort((a, b) => a.tag.localeCompare(b.tag, 'da'));
}
