#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import doctrine from 'doctrine';
import { parse } from 'acorn';

// --- Helper Functions ---

/**
 * Recursively finds all files matching a pattern in a directory.
 * @param {string} dir - The directory to search.
 * @param {RegExp} pattern - The regex pattern to match filenames.
 * @returns {string[]} - Array of matching file paths.
 */
function findFilesRecursive(dir, pattern) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    if (stat && stat.isDirectory()) {
      results = results.concat(findFilesRecursive(filePath, pattern));
    } else if (pattern.test(filePath)) {
      results.push(filePath);
    }
  });
  return results;
}

/**
 * Extracts OpenAPI path details from JSDoc tags.
 * Basic implementation, assumes specific tag formats.
 */
function extractOpenApiData(jsdocAst) {
  const data = {
    parameters: [],
    responses: {},
    requestBody: null,
    summary: '',
    description: jsdocAst.description || '',
    'x-codeSamples': [] 
  };

  jsdocAst.tags.forEach(tag => {
    switch (tag.title) {
      case 'summary':
        data.summary = tag.description || '';
        break;
      case 'description':
        data.description = tag.description || '';
        break; 
      case 'param':
        // Basic parsing, assumes format like: {Type} [name=default] - Description (in/path/query)
        const param = {
          name: tag.name || '',
          in: tag.description ? (tag.description.match(/\((in|path|query|header|cookie)\)/)?.[1] || 'query') : 'query', // Default to query
          description: tag.description ? tag.description.replace(/\((in|path|query|header|cookie)\)/, '').trim() : '',
          required: !(tag.type && tag.type.type === 'OptionalType'),
          schema: { type: (tag.type && tag.type.name) ? tag.type.name.toLowerCase() : 'string' } // Very basic type mapping
        };
        // Adjust for path parameters needing required=true
        if (param.in === 'path') param.required = true;
        data.parameters.push(param);
        break;
      case 'requestbody': // Use lowercase tag consistently
        // Assumes description is JSON schema or reference
        try {
          data.requestBody = JSON.parse(tag.description);
        } catch (e) {
          console.warn('Could not parse @requestBody JSON: ', tag.description);
          // Fallback to a simple description
          data.requestBody = { 
            description: tag.description || 'Request body details',
            content: { 'application/json': {} } // Assume JSON content type if parsing fails
          };
        }
        break;
      case 'response':
        // Assumes format like: {StatusCode} {ContentType} Description or {StatusCode} Description
        // Or assumes description is JSON schema or reference
        const parts = (tag.description || '').match(/^\{?(\d{3})\}?\s*(?:\{(.*?)\})?\s*(.*)$/);
        if (parts) {
          const statusCode = parts[1];
          const contentType = parts[2] || 'application/json'; // Default to JSON
          const description = parts[3] || 'Response';
          let schema = { type: 'string' }; // Default schema
          try {
              // Attempt to parse schema if description looks like JSON
              if (description.trim().startsWith('{') && description.trim().endsWith('}')) {
                  schema = JSON.parse(description);
              }
          } catch (e) {
             console.warn(`Could not parse @response description as JSON schema for status ${statusCode}: `, description);
          }

          if (!data.responses[statusCode]) data.responses[statusCode] = {};
          data.responses[statusCode] = {
            description: description.trim().startsWith('{') ? `Response for status code ${statusCode}` : description, // Avoid using schema as description
            content: {
              [contentType]: { schema: schema }
            }
          };
        } else {
             console.warn('Could not parse @response format: ', tag.description);
        }
        break;
       case 'codesample': // Example for Mintlify x-codeSamples
         // Assumes format: {lang} [label] Source code here
         const codeSampleMatch = (tag.description || '').match(/^\{?([^}]+)\}?\s*(?:\[([^\]]+)\])?\s*([\s\S]*)$/);
         if (codeSampleMatch) {
             data['x-codeSamples'].push({
                 lang: codeSampleMatch[1],
                 label: codeSampleMatch[2] || codeSampleMatch[1], // Use lang as label if not provided
                 source: codeSampleMatch[3].trim()
             });
         } else {
             console.warn('Could not parse @codesample format: ', tag.description);
         }
         break;
      // Add cases for @tags, @security etc.
    }
  });
   // Clean up empty code samples array
  if (data['x-codeSamples'].length === 0) {
    delete data['x-codeSamples'];
  }
  return data;
}

/**
 * Converts SvelteKit route path params to OpenAPI format.
 * Example: /[id]/sub/[slug] -> /{id}/sub/{slug}
 */
function convertRoutePathToOpenAPI(routePath) {
    // Remove leading /src/routes and trailing /+server.js
    let apiPath = routePath.replace(/^.*\/routes\/api|\/\+server\.js$/g, '') || '/';
    // Replace SvelteKit param syntax [param] with OpenAPI {param}
    apiPath = apiPath.replace(/\[(\.\.\.)?([^\]]+)\]/g, '{$2}');
    return apiPath;
}

// --- Main Script ---

const apiRoutesDir = path.resolve(process.cwd(), 'src/routes/api');
const outputFile = path.resolve(process.cwd(), '../docs/api-reference/openapi.json'); 
const filePattern = /\+server\.js$/;

let openApiSpec = {}; // Initialize empty

// --- Read Existing Spec File ---
try {
  if (fs.existsSync(outputFile)) {
    console.log(`Reading existing OpenAPI spec from ${outputFile}...`);
    const existingContent = fs.readFileSync(outputFile, 'utf8');
    openApiSpec = JSON.parse(existingContent);
    // Basic validation
    if (!openApiSpec.openapi || !openApiSpec.info) {
        console.warn('Existing spec file seems invalid, starting fresh.');
        throw new Error('Invalid structure');
    }
    // Ensure paths object exists
    openApiSpec.paths = openApiSpec.paths || {}; 
     console.log('Existing spec loaded successfully.');
  } else {
     console.log(`No existing spec file found at ${outputFile}, creating a new one.`);
     throw new Error('File not found'); // Treat as needing initialization
  }
} catch (err) {
   console.log('Initializing default OpenAPI structure.');
   // Initialize with default structure if file doesn't exist or is invalid
   openApiSpec = {
     openapi: '3.0.0', // Using 3.0.0 for broader compatibility, can be 3.1.0
     info: {
       title: 'ASAP Digest API',
       version: '1.0.0', // TODO: Get version from package.json
       description: 'API for ASAP Digest application'
     },
     paths: {},
     // Add default servers, components, securitySchemes if desired
     servers: [ { url: 'http://localhost:5173' } ], // Example server
     components: { schemas: {}, securitySchemes: {} },
     security: []
   };
}

// --- Process API Route Files ---
console.log(`Searching for API routes in ${apiRoutesDir}...`);
const apiFiles = findFilesRecursive(apiRoutesDir, filePattern);
console.log(`Found ${apiFiles.length} API route files.`);

apiFiles.forEach(file => {
  try {
    const content = fs.readFileSync(file, 'utf8');
    const relativePath = path.relative(process.cwd(), file);
    const apiPath = convertRoutePathToOpenAPI(relativePath);

    console.log(`Processing ${relativePath} -> ${apiPath}`);

    const ast = parse(content, {
        ecmaVersion: 'latest',
        sourceType: 'module',
        onComment: (block, text, start, end) => {
            if (block) {
                const nextNodeIndex = ast.body.findIndex(node => node.start > end);
                if (nextNodeIndex !== -1) {
                    const nextNode = ast.body[nextNodeIndex];
                    let httpMethod = null;

                    if (isHttpMethodExport(nextNode)) {
                         if (nextNode.declaration.type === 'VariableDeclaration') {
                             httpMethod = nextNode.declaration.declarations[0].id.name;
                         } else if (nextNode.declaration.type === 'FunctionDeclaration') {
                             httpMethod = nextNode.declaration.id.name;
                         }
                    }

                    if (httpMethod) {
                        const methodLower = httpMethod.toLowerCase();
                        try {
                           const jsdocAst = doctrine.parse(text, { unwrap: true, sloppy: true });
                           const pathData = extractOpenApiData(jsdocAst);

                           // --- Update Logic ---
                           if (!openApiSpec.paths[apiPath]) {
                               openApiSpec.paths[apiPath] = {}; // Ensure path object exists
                           }
                           // Update or add the method data
                           openApiSpec.paths[apiPath][methodLower] = { 
                               ...(openApiSpec.paths[apiPath][methodLower] || {}), // Preserve existing data for the method if any
                               ...pathData // Overwrite with JSDoc data
                           }; 
                           console.log(`  Updated/Added ${httpMethod} for ${apiPath}`);

                        } catch(parseErr) {
                            console.warn(`  Could not parse JSDoc for ${httpMethod} in ${relativePath}:`, parseErr.message);
                        }
                    }
                }
            }
        }
    });

  } catch (err) {
    console.error(`Error processing file ${file}:`, err);
  }
});

// --- Write Updated Spec to File ---
try {
  // Ensure the output directory exists
  const outputDir = path.dirname(outputFile);
  if (!fs.existsSync(outputDir)){
      fs.mkdirSync(outputDir, { recursive: true });
      console.log(`Created directory ${outputDir}`);
  }

  fs.writeFileSync(outputFile, JSON.stringify(openApiSpec, null, 2));
  console.log(`OpenAPI specification updated successfully at ${outputFile}`);
  process.exit(0);
} catch (err) {
  console.error(`Error writing OpenAPI spec to ${outputFile}:`, err);
  process.exit(1);
}

// --- isHttpMethodExport function (remains the same) ---
function isHttpMethodExport(node) {
  const httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
  if (node.type === 'ExportNamedDeclaration' && node.declaration) {
      if (node.declaration.type === 'VariableDeclaration') {
          for (const declarator of node.declaration.declarations) {
              if (declarator.id && declarator.id.type === 'Identifier' && httpMethods.includes(declarator.id.name)) return true;
          }
      } else if (node.declaration.type === 'FunctionDeclaration') {
          if (node.declaration.id && node.declaration.id.type === 'Identifier' && httpMethods.includes(node.declaration.id.name)) return true;
      }
  }
  return false;
} 