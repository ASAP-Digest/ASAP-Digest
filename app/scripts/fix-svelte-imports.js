#!/usr/bin/env node

/**
 * Script to add @ts-ignore comments to all Svelte component imports
 * Run with: node scripts/fix-svelte-imports.js
 */

import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import { glob } from 'glob';

// ES Module replacements for __dirname, __filename
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Regular expression to match Svelte component imports
const svelteImportRegex = /^import\s+(?:(?:\w+(?:\s*,\s*\{\s*[^}]*\s*\})?)|(?:\{\s*[^}]*\s*\}))\s+from\s+['"]([^'"]*\.svelte)['"];?$/gm;

// Regular expression to detect if an import already has a @ts-ignore comment
const tsIgnoreRegex = /\/\/\s*@ts-ignore/;

/**
 * Process a single file to add @ts-ignore comments to Svelte imports
 * @param {string} filePath - Path to the file to process
 * @returns {Promise<boolean>} - True if file was modified
 */
async function processFile(filePath) {
  try {
    const content = await fs.readFile(filePath, 'utf8');
    const lines = content.split('\n');
    
    let modified = false;
    const newLines = [];
    
    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      
      // Check if this is a Svelte import
      if (svelteImportRegex.test(line)) {
        // Check if the previous line already has @ts-ignore
        const prevLine = i > 0 ? lines[i-1] : '';
        if (!tsIgnoreRegex.test(prevLine)) {
          newLines.push('// @ts-ignore - Svelte component import');
          modified = true;
        }
      }
      
      newLines.push(line);
    }
    
    if (modified) {
      await fs.writeFile(filePath, newLines.join('\n'));
      console.log(`Modified: ${filePath}`);
    }
    
    return modified;
  } catch (error) {
    console.error(`Error processing ${filePath}:`, error);
    return false;
  }
}

/**
 * Main function to process all JavaScript and Svelte files
 */
async function main() {
  try {
    const files = await glob('src/**/*.{js,svelte}');
    console.log(`Found ${files.length} JavaScript and Svelte files to process`);
    
    let modifiedCount = 0;
    
    for (const file of files) {
      if (await processFile(file)) {
        modifiedCount++;
      }
    }
    
    console.log(`Processed ${files.length} files, modified ${modifiedCount} files`);
  } catch (error) {
    console.error('Error processing files:', error);
    process.exit(1);
  }
}

main(); 