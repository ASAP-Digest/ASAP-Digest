#!/usr/bin/env node

/**
 * Simple script to validate that documentation follows Mintlify standards
 * This simulates part of what would happen with the Documentation Update Trigger
 */

const fs = require('fs');
const path = require('path');

console.log('🔍 Validating Mintlify documentation...');

// Validate docs.json
try {
  const docsConfigPath = path.join(__dirname, '..', 'docs', 'docs.json');
  const docsConfig = JSON.parse(fs.readFileSync(docsConfigPath, 'utf8'));
  
  console.log('✅ docs.json is valid JSON');
  
  // Check if our new authentication pages are properly included
  const apiTab = docsConfig.navigation.tabs.find(tab => tab.tab === 'API Reference');
  const authGroup = apiTab?.groups.find(group => group.group === 'Authentication');
  
  if (authGroup && authGroup.pages.includes('api-reference/auth/introduction') && 
      authGroup.pages.includes('api-reference/auth/wp-session-check')) {
    console.log('✅ Authentication endpoints are properly configured in navigation');
  } else {
    console.warn('⚠️ Authentication endpoints may not be properly configured in navigation');
  }
} catch (error) {
  console.error('❌ Error validating docs.json:', error.message);
  process.exit(1);
}

// Validate our MDX files
const filesToCheck = [
  path.join(__dirname, '..', 'docs', 'api-reference', 'auth', 'introduction.mdx'),
  path.join(__dirname, '..', 'docs', 'api-reference', 'auth', 'wp-session-check.mdx'),
  path.join(__dirname, '..', 'docs', 'api-reference', 'wordpress', 'active-sessions.mdx')
];

for (const file of filesToCheck) {
  try {
    const content = fs.readFileSync(file, 'utf8');
    
    // Check for frontmatter
    if (!content.startsWith('---')) {
      console.warn(`⚠️ ${path.basename(file)} is missing frontmatter`);
    } else {
      console.log(`✅ ${path.basename(file)} has frontmatter`);
    }
    
    // Check for required sections based on documentation standards
    if (file.includes('introduction.mdx')) {
      if (content.includes('# Authentication Overview')) {
        console.log(`✅ ${path.basename(file)} has an authentication overview section`);
      } else {
        console.warn(`⚠️ ${path.basename(file)} is missing an authentication overview section`);
      }
    } else {
      if (content.includes('## Overview') || content.includes('# Overview')) {
        console.log(`✅ ${path.basename(file)} has an overview section`);
      } else {
        console.warn(`⚠️ ${path.basename(file)} is missing an overview section`);
      }
    }
    
    // Check code blocks have language specified
    const codeBlockMatches = content.match(/```[a-z]*/g) || [];
    const validLanguages = ['javascript', 'typescript', 'json', 'bash', 'php', 'html', 'css', 'jsx', 'tsx', 'svelte', 'mermaid'];
    const hasUnspecifiedLanguage = codeBlockMatches.some(match => {
      const lang = match.replace('```', '');
      return lang === '' || !validLanguages.includes(lang);
    });
    
    if (hasUnspecifiedLanguage) {
      console.warn(`⚠️ ${path.basename(file)} has code blocks without language specification or with unsupported language`);
    } else if (codeBlockMatches.length > 0) {
      console.log(`✅ ${path.basename(file)} has proper language specification in code blocks`);
    }
  } catch (error) {
    if (error.code === 'ENOENT') {
      console.error(`❌ File not found: ${path.basename(file)}`);
    } else {
      console.error(`❌ Error validating ${path.basename(file)}:`, error.message);
    }
  }
}

console.log('\n🎉 Documentation validation completed!');
console.log('Note: This script simulates part of the Documentation Update Trigger protocol');
console.log('For a full validation, run the OpenAPI generation and linting scripts');

// In a real implementation, this would exit with a proper exit code
// process.exit(errorCount > 0 ? 1 : 0); 