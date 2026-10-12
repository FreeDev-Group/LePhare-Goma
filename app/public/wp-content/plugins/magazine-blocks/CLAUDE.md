# Magazine Blocks

WordPress Gutenberg blocks plugin for magazine/newspaper sites. PHP namespace `MagazineBlocks\` → `includes/`. Package manager: **pnpm 9.x only**.

## Key Facts
- Text domain: `magazine-blocks` (same in both PHP and `block.json`)
- Block namespace: `magazine-blocks` → `magazine-blocks/heading`, `magazine-blocks/button`, etc.
- Entry: `magazine-blocks.php` → `MagazineBlocks::init()` (Singleton)
- PHP min: 7.0 | WP min: 5.4
- 31 blocks total

## Project Rules
- pnpm only — no npm, no yarn
- PHP PHPCS ruleset: **WPEverest-Core** (`phpcs.xml`) — not standard WPCS

## Block Architecture

Two layers per block (both required):

**Editor** → `src/blocks/blocks/{name}/` — `index.ts`, `components/`, `styles.scss`, `block.json`
**Server** → `includes/BlockTypes/{Name}.php` — extends `Abstracts\Block`

Registration reads `dist/{block_name}/block.json` (compiled), not `src/`. Always run `pnpm build` after changing `block.json`. See `.claude/docs/architecture.md` for the full registration flow.

## AbstractBlock API
- `get_attribute($key, $default, $sanitize)` — `$sanitize=true` strips to `[a-zA-Z0-9_-]`
- `build_html($content)` — override for dynamic blocks
- `get_html_attrs()` — override to add extra HTML attributes
- `build_html_attributes()` — auto-sets id/class via `get_default_html_attrs()`; never replicate manually
- `cn(...$args)` — classnames helper

## block.json Required Fields
```json
"name": "magazine-blocks/{slug}",
"category": "magazine-blocks",
"textdomain": "magazine-blocks",
"supports": { "className": false, "customClassName": false }
```
Always include `"clientId": { "type": "string" }` — required by `{{WRAPPER}}` selector system.

## CSS Selector Macros
`{{WRAPPER}}` → `.mzb-{name}-{clientId}` | `{{VALUE}}` → attribute value

## TS Path Aliases
`@blocks/components` · `@blocks` · `@blocks/hooks` · `@blocks/helpers` · `@blocks/utils` · `@admin`
Never use `../../` across directory boundaries.

## PHP Constants
`MAGAZINE_BLOCKS_VERSION` · `MAGAZINE_BLOCKS_PLUGIN_FILE` · `MAGAZINE_BLOCKS_PLUGIN_DIR` · `MAGAZINE_BLOCKS_PLUGIN_DIR_URL`
`MAGAZINE_BLOCKS_ASSETS` · `MAGAZINE_BLOCKS_ASSETS_DIR_URL` · `MAGAZINE_BLOCKS_DIST_DIR_URL`
`MAGAZINE_BLOCKS_LANGUAGES` · `MAGAZINE_BLOCKS_UPLOAD_DIR` · `MAGAZINE_BLOCKS_UPLOAD_DIR_URL`

## Commands
```
pnpm run setup          # pnpm install + composer install + dump-autoload (first-time)
pnpm dev                # watch all bundles (daily driver)
pnpm start              # blocks + dashboard webpack-dev-server (HMR)
pnpm start:blocks       # blocks only
pnpm start:dashboard    # dashboard only
pnpm start:frontend     # frontend utilities only
pnpm build              # full production build
pnpm lint               # ESLint TS/TSX
pnpm format             # Prettier check
composer run phpcs      # PHPCS WPEverest-Core
composer run phpcbf     # auto-fix PHP
composer dump-autoload  # after adding new PHP classes
grunt release           # full release zip
```

## Skills
| Task | Skill |
|---|---|
| Any block work | `magazine-blocks-block-dev` |
| PHP outside BlockTypes | `magazine-blocks-php-dev` |
| Input, REST, AJAX, DB | `magazine-blocks-security` |
| Dev commands / release | `magazine-blocks-dev-cycle` |
| Before any PR | `magazine-blocks-code-review` |

## Agents
`magazine-blocks-block-scaffolder` — scaffold a complete new block end-to-end
