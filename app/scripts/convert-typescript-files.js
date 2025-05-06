#!/usr/bin/env node

/**
 * Script to find and convert TypeScript files to JavaScript with JSDoc comments
 * Run with: node scripts/convert-typescript-files.js
 */

import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import { glob } from 'glob';
import { exec } from 'child_process';
import { promisify } from 'util';

// ES Module replacements for __dirname, __filename
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Only promisify exec as it doesn't have a native Promise API
const execAsync = promisify(exec);

/**
 * Process a TypeScript file and convert it to JavaScript with JSDoc
 * @param {string} filePath - Path to the TypeScript file
 * @returns {Promise<boolean>} - True if conversion was successful
 */
async function convertFile(filePath) {
  try {
    // Skip .d.ts files - we want to keep those
    if (filePath.endsWith('.d.ts')) {
      console.log(`Skipping declaration file: ${filePath}`);
      return false;
    }

    const content = await fs.readFile(filePath, 'utf8');
    
    // Target JavaScript file path
    const jsFilePath = filePath.replace(/\.ts$/, '.js');
    
    // Check if JS file already exists
    try {
      await fs.access(jsFilePath);
      console.log(`JavaScript file already exists: ${jsFilePath}. Skipping conversion.`);
      return false;
    } catch {
      // File does not exist, proceed with conversion
    }

    // Simple conversion for basic TypeScript files
    // This is very basic and won't handle all TypeScript features
    let jsContent = content
      // Remove type annotations
      .replace(/:\s*[A-Za-z0-9_\[\]<>.|]+(\s*\|\s*[A-Za-z0-9_\[\]<>.|]+)*(\s*=\s*)/g, ' = ')
      // Remove return type annotations
      .replace(/\):\s*[A-Za-z0-9_\[\]<>.|]+(\s*\|\s*[A-Za-z0-9_\[\]<>.|]+)*/g, ')')
      // Remove interface declarations
      .replace(/interface\s+[A-Za-z0-9_]+\s*(\extends\s+[A-Za-z0-9_]+\s*)?{[^}]*}/g, '')
      // Remove import type statements
      .replace(/import\s+type\s*{[^}]*}\s*from\s*['"][^'"]*['"]/g, '')
      // Remove type keyword from imports
      .replace(/import\s+{([^}]*)}\s+from/g, (match, imports) => {
        return `import {${imports.replace(/\btype\s+/g, '')}} from`;
      });

    // Add JSDoc for functions
    jsContent = jsContent.replace(/function\s+([A-Za-z0-9_]+)\s*\(([^)]*)\)/g, (match, funcName, params) => {
      return `/**\n * ${funcName} function\n * @param {any} params - Function parameters\n * @returns {any} - Function result\n */\nfunction ${funcName}(${params})`;
    });

    await fs.writeFile(jsFilePath, jsContent);
    console.log(`Converted: ${filePath} -> ${jsFilePath}`);
    
    return true;
  } catch (error) {
    console.error(`Error converting ${filePath}:`, error);
    return false;
  }
}

/**
 * Main function to find and convert TypeScript files
 */
async function main() {
  try {
    const files = await glob('src/**/*.ts');
    console.log(`Found ${files.length} TypeScript files (excluding .d.ts files)`);
    
    let convertedCount = 0;
    
    for (const file of files) {
      if (await convertFile(file)) {
        convertedCount++;
      }
    }
    
    console.log(`Processed ${files.length} files, converted ${convertedCount} files`);
    console.log('Note: The conversion is basic. You may need to manually adjust the JSDoc comments.');
  } catch (error) {
    console.error('Error processing files:', error);
    process.exit(1);
  }
}

main(); 