import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string().max(170),
    pubDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    author: z.string().default('Martin Jespersgaard'),
    image: z.string().optional(),
    imageAlt: z.string().optional(),
    tags: z.array(z.string()).default([]),
    draft: z.boolean().default(false),
  }),
});

const cases = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './content/cases' }),
  schema: z.object({
    title: z.string(),
    description: z.string().max(170),
    industry: z.string(),
    technologies: z.array(z.string()).default([]),
    published: z.coerce.date(),
    image: z.string().optional(),
    imageAlt: z.string().optional(),
  }),
});

const pages = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './content/pages' }),
  schema: z.object({
    title: z.string(),
    description: z.string().max(170),
    heading: z.string().optional(),
    lead: z.string().optional(),
    cta: z.object({ label: z.string(), href: z.string() }).optional(),
    secondaryCta: z.object({ label: z.string(), href: z.string() }).optional(),
    items: z
      .array(z.object({ title: z.string(), text: z.string(), href: z.string().optional() }))
      .default([]),
    skills: z.array(z.string()).default([]),
    packages: z
      .array(z.object({ title: z.string(), text: z.string(), price: z.string(), features: z.array(z.string()).default([]),
        detail: z
          .object({
            intro: z.string(),
            duration: z.string(),
            included: z.array(z.string()),
            notIncluded: z.array(z.string()).default([]),
            note: z.string().optional(),
          })
          .optional(),
      }))
      .default([]),
    image: z.string().optional(),
    portrait: z.string().optional(),
    portraitAlt: z.string().optional(),
    email: z.string().optional(),
    phone: z.string().optional(),
    linkedin: z.string().optional(),
  }),
});

export const collections = { blog, cases, pages };
