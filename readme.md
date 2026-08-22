# Boltfolio

A fast, dark-first WordPress portfolio theme built for developers who care about performance.
Showcases plugins and open-source projects through a dedicated `project` post type, ships its own
Gutenberg block suite, and includes a documentation layout with TOC scroll-spy and code copy buttons.

## Highlights

- **Performance-first**: zero jQuery, zero external assets, system font stack, one small stylesheet,
  filemtime cache busting, deferred JS (~1 KB).
- **Project CPT** (`project`) with `project_type` taxonomy and `github_url` / `live_url` meta,
  fully REST-enabled for the block editor.
- **Documentation Layout** page template: sticky sidebar nav from the page hierarchy, numbered
  "On this page" TOC rail with IntersectionObserver scroll-spy, prev/next pager across sibling docs,
  auto copy-to-clipboard on all `<pre>` blocks.
- **Master Block Suite** (`blocks/`): Consolidated Multi-Block Suite architecture — one
  `package.json`, one build command for every block. Ships the `boltfolio/callout` dynamic block
  (info / tip / warning) rendered server-side via `render.php`.
- Full template hierarchy: front page, project archive/single with related projects, search, 404,
  breadcrumbs, accessible mobile navigation overlay.

## Requirements

| Tool        | Version |
| ----------- | ------- |
| WordPress   | 6.0+    |
| PHP         | 7.4+    |
| Node.js     | 20+ (for block development only) |

## Installation

1. Copy the `boltfolio` folder into `wp-content/themes/` and activate it.
   Blocks work out of the box — `build/` assets are committed.
2. (Optional) Rebuild blocks after editing sources:

```bash
cd boltfolio/blocks
npm install
npm run build      # production
npm start          # watch mode
```

### Adding a new block to the suite

```bash
cd boltfolio/blocks
npx @wordpress/create-block@latest my-block \
	--no-plugin \
	--target-dir=src/my-block \
	--namespace=boltfolio \
	--variant=dynamic
npm run build
```

No PHP changes needed — `functions.php` auto-registers every manifest under
`blocks/build/{slug}/block.json`.

## Project content model

Create projects under **Projects → Add New** in wp-admin:

- **Excerpt** → card description
- **Project Types** taxonomy → e.g. *WordPress Plugin*, *Open Source*
- Custom fields / REST attributes: `github_url`, `live_url`
- **Order** (page attributes) → higher numbers float first on the homepage

Documentation lives as hierarchical pages using the **“Documentation Layout”** template;
child pages automatically appear in the sidebar and pager.

## License

GPL-2.0-or-later
