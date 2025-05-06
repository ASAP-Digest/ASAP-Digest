# JavaScript Project Scripts

This directory contains utility scripts for managing the JS+JSDoc project structure.

## Scripts

### 1. Fix Svelte Imports (`fix-svelte-imports.js`)

This script adds `// @ts-ignore - Svelte component import` comments before Svelte component imports in JavaScript and Svelte files to prevent type checking errors.

**Usage:**
```bash
node scripts/fix-svelte-imports.js
```

**What it does:**
- Scans all `.js` and `.svelte` files in the `src` directory
- Identifies imports of Svelte components (ending with `.svelte`)
- Adds a `// @ts-ignore - Svelte component import` comment before each Svelte import if not already present

### 2. Convert TypeScript Files (`convert-typescript-files.js`)

This script helps convert TypeScript (`.ts`) files to JavaScript (`.js`) files with JSDoc comments.

**Usage:**
```bash
node scripts/convert-typescript-files.js
```

**What it does:**
- Scans all `.ts` files in the `src` directory (excluding `.d.ts` declaration files)
- Creates a JavaScript version with basic type annotations converted to JSDoc
- Skips files that already have a JavaScript equivalent

## Setup Notes

These scripts use ES modules. The project has `"type": "module"` set in `package.json`, which requires ES module syntax (import/export) instead of CommonJS (require/module.exports).

## Recommended Workflow for Converting to JS+JSDoc

1. **First, fix Svelte imports**: `node scripts/fix-svelte-imports.js`
2. **Then convert TypeScript files**: `node scripts/convert-typescript-files.js`
3. **Manually check** the converted files to ensure proper JSDoc annotations
4. **Run type checking** to catch any issues: `pnpm check` or `pnpm svelte-check`
5. **Update jsconfig.json** if needed (see docs/jsconfig-reference.md)

Note: The TypeScript to JavaScript conversion is basic and may require manual adjustments. 