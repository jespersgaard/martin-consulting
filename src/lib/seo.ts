export const SITE = {
  name: 'Martin Jespersgaard',
  tagline: 'AI, Power Platform og digitalisering der skaber resultater',
  description:
    'Erfaren IT-konsulent med baggrund inden for softwareudvikling, projektledelse, produktledelse, Power Platform, integrationer og AI.',
  url: 'https://www.jespersgaard.com',
  locale: 'da_DK',
  email: 'martin@jespersgaard.com',
  linkedin: 'https://www.linkedin.com/in/jespersgaard/',
  defaultImage: '/images/og-default.png',
};

export const NAV = [
  { label: 'Forside', href: '/' },
  { label: 'Om mig', href: '/om-mig/' },
  { label: 'Ydelser', href: '/ydelser/' },
  { label: 'Erfaring', href: '/erfaring/' },
  { label: 'Blog', href: '/blog/' },
  { label: 'Kontakt', href: '/kontakt/' },
];

export function absoluteUrl(path: string, site: URL | string = SITE.url): string {
  return new URL(path, site).href;
}

export function canonicalUrl(pathname: string, site: URL | string = SITE.url): string {
  const clean = pathname.endsWith('/') ? pathname : `${pathname}/`;
  return absoluteUrl(clean, site);
}

export interface SeoInput {
  title: string;
  description: string;
  pathname: string;
  image?: string;
  type?: 'website' | 'article';
  publishedTime?: Date;
  modifiedTime?: Date;
  tags?: string[];
  noindex?: boolean;
}

export function buildSeo(input: SeoInput, site: URL | string = SITE.url) {
  const fullTitle =
    input.pathname === '/' ? `${SITE.name} – IT-konsulent | AI & Power Platform` : `${input.title} | ${SITE.name}`;
  const image = absoluteUrl(input.image ?? SITE.defaultImage, site);
  return {
    title: fullTitle,
    description: input.description,
    canonical: canonicalUrl(input.pathname, site),
    image,
    type: input.type ?? 'website',
    publishedTime: input.publishedTime?.toISOString(),
    modifiedTime: input.modifiedTime?.toISOString(),
    tags: input.tags ?? [],
    robots: input.noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large',
  };
}

export function personSchema(site: URL | string = SITE.url) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Person',
    '@id': `${absoluteUrl('/', site)}#person`,
    name: SITE.name,
    url: absoluteUrl('/', site),
    image: absoluteUrl('/images/profile/martin.webp', site),
    jobTitle: 'Selvstændig IT-konsulent',
    description: SITE.description,
    email: SITE.email,
    sameAs: [SITE.linkedin],
    knowsAbout: ['Microsoft Copilot', 'AI', 'Power Platform', 'Digitalisering', 'Automatisering', 'IT-projektledelse', 'Produktledelse', 'Integrationer'],
    address: { '@type': 'PostalAddress', addressCountry: 'DK' },
  };
}

interface ArticleInput {
  title: string;
  description: string;
  pathname: string;
  image?: string;
  datePublished: Date;
  dateModified?: Date;
  author?: string;
  tags?: string[];
}

function articleBase(type: 'BlogPosting' | 'Article', a: ArticleInput, site: URL | string) {
  const url = canonicalUrl(a.pathname, site);
  return {
    '@context': 'https://schema.org',
    '@type': type,
    mainEntityOfPage: { '@type': 'WebPage', '@id': url },
    headline: a.title,
    description: a.description,
    image: absoluteUrl(a.image ?? SITE.defaultImage, site),
    datePublished: a.datePublished.toISOString(),
    dateModified: (a.dateModified ?? a.datePublished).toISOString(),
    inLanguage: 'da-DK',
    keywords: a.tags?.join(', '),
    author: { '@type': 'Person', name: a.author ?? SITE.name, url: absoluteUrl('/', site) },
    publisher: { '@type': 'Person', name: SITE.name, url: absoluteUrl('/', site) },
  };
}

export const blogPostingSchema = (a: ArticleInput, site: URL | string = SITE.url) =>
  articleBase('BlogPosting', a, site);

export const articleSchema = (a: ArticleInput, site: URL | string = SITE.url) =>
  articleBase('Article', a, site);

export function formatDate(d: Date): string {
  return new Intl.DateTimeFormat('da-DK', { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
}

export function professionalServiceSchema(site: URL | string = SITE.url) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ProfessionalService',
    '@id': `${absoluteUrl('/', site)}#service`,
    name: `${SITE.name} – IT-konsulent`,
    url: absoluteUrl('/', site),
    description: SITE.description,
    image: absoluteUrl(SITE.defaultImage, site),
    areaServed: { '@type': 'Country', name: 'Danmark' },
    founder: { '@id': `${absoluteUrl('/', site)}#person` },
    knowsLanguage: 'da',
    email: SITE.email,
    serviceType: ['AI og Copilot', 'Power Platform', 'Digitalisering', 'Automatisering', 'IT-projektledelse', 'Produktledelse', 'Integrationer'],
  };
}

export function breadcrumbSchema(crumbs: { name: string; path: string }[], site: URL | string = SITE.url) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: crumbs.map((c, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: c.name,
      item: canonicalUrl(c.path, site),
    })),
  };
}

export function breadcrumbsFor(pathname: string, title: string) {
  const parts = pathname.split('/').filter(Boolean);
  const crumbs = [{ name: 'Forside', path: '/' }];
  if (parts.length === 0) return crumbs;
  const parent = parts[0] === 'blog' ? { name: 'Blog', path: '/blog/' } : parts[0] === 'cases' ? { name: 'Erfaring', path: '/erfaring/' } : null;
  if (parts.length === 1) {
    crumbs.push({ name: title, path: pathname });
  } else {
    if (parent) crumbs.push(parent);
    crumbs.push({ name: title, path: pathname });
  }
  return crumbs;
}
