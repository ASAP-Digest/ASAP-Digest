#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import doctrine from 'doctrine';
import { parse } from 'acorn';

/**
 * Checks if a JSDoc comment block contains required tags.
 * @param {string} comment - The comment block content.
 * @param {string[]} requiredTags - Array of required tag names (e.g., ['summary', 'response']).
 * @returns {{missingTags: string[], hasTags: boolean}} - Object indicating if tags are present and which are missing.
 */
function checkRequiredTags(comment, requiredTags) {
  try {
    const ast = doctrine.parse(comment, { unwrap: true, sloppy: true });
    const presentTags = new Set(ast.tags.map(tag => tag.title));
    const missingTags = requiredTags.filter(tag => !presentTags.has(tag));
    return { missingTags, hasTags: missingTags.length === 0 };
  } catch (e) {
    // Ignore parsing errors for non-JSDoc comments
    return { missingTags: requiredTags, hasTags: false }; // Assume tags are missing if parsing fails
  }
}

/**
 * Checks if an AST node represents an export of an HTTP method function.
 * Acorn produces an AST (Abstract Syntax Tree). We traverse this tree.
 * @param {object} node - The AST node from Acorn.
 * @returns {boolean} - True if the node is `export const METHOD = ...` or `export function METHOD(...)`.
 */
function isHttpMethodExport(node) {
  const httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

  // Check for `export const METHOD = ...`
  if (node.type === 'ExportNamedDeclaration' && node.declaration && node.declaration.type === 'VariableDeclaration') {
    for (const declarator of node.declaration.declarations) {
      if (declarator.id && declarator.id.type === 'Identifier' && httpMethods.includes(declarator.id.name)) {
        return true;
      }
    }
  }

  // Check for `export function METHOD(...)`
  if (node.type === 'ExportNamedDeclaration' && node.declaration && node.declaration.type === 'FunctionDeclaration') {
    if (node.declaration.id && node.declaration.id.type === 'Identifier' && httpMethods.includes(node.declaration.id.name)) {
      return true;
    }
  }
  return false;
}

// --- Main Script ---
const filesToCheck = process.argv.slice(2);
let hasErrors = false;
const requiredTags = ['summary', 'response']; // Define required tags

if (filesToCheck.length === 0) {
  console.log('No API route files staged for linting.');
  process.exit(0);
}

console.log('Linting OpenAPI annotations for:', filesToCheck.join(', '));

filesToCheck.forEach(file => {
  try {
    const filePath = path.resolve(process.cwd(), file); // Ensure absolute path if needed
    const content = fs.readFileSync(filePath, 'utf8');

    // Use Acorn to parse the code and find JSDoc comments before HTTP method exports
    const ast = parse(content, {
        ecmaVersion: 'latest',
        sourceType: 'module',
        locations: true, // Get line numbers
        onComment: (block, text, start, end) => {
            if (block) { // Only process block comments /** ... */
                // Find the node immediately following the comment
                // Note: This is a simplified way to associate comments. Robust association
                // might require more complex AST traversal or specific libraries.
                const nextNodeIndex = ast.body.findIndex(node => node.start > end);
                if (nextNodeIndex !== -1) {
                    const nextNode = ast.body[nextNodeIndex];
                    if (isHttpMethodExport(nextNode)) {
                        const { missingTags, hasTags } = checkRequiredTags(text, requiredTags);
                        if (!hasTags) {
                            console.error(`ERROR: Missing OpenAPI tags in ${file} (before line ${nextNode.loc.start.line}): Required [${requiredTags.join(', ')}], Missing [${missingTags.join(', ')}]`);
                            hasErrors = true;
                        }
                    }
                }
            }
        }
    });

    // Additional check: Traverse AST body directly to find exports without preceding comments
    // This part is more complex to implement correctly without a robust comment association library.
    // For now, we rely on the onComment handler finding comments immediately before exports.

  } catch (err) {
    console.error(`Error processing file ${file}:`, err);
    hasErrors = true; // Treat file processing errors as linting errors
  }
});

if (hasErrors) {
  console.error('OpenAPI annotation linting failed.');
  process.exit(1);
} else {
  console.log('OpenAPI annotation linting passed.');
  process.exit(0);
} 