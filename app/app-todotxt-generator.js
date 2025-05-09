/**
 * app-todotxt-generator.js
 * 
 * @description Generates a prioritized todo.txt formatted file for the app directory
 *              based on file scanning, code analysis, and optionally a roadmap integration.
 * 
 * @input App codebase from current directory
 * @output app-todos.txt - The generated, prioritized todo.txt file
 * 
 * @usage Run `node ./app-todotxt-generator.js`
 * 
 */

// --- Node.js Built-in Modules --- 
const fs = require('fs').promises;
const path = require('path');
const os = require('os');
const { execSync } = require('child_process');

// --- Configuration ---
const APP_DIR = __dirname; // Current directory (should be app/)
const OUTPUT_PATH = path.join(APP_DIR, 'app-todos.txt');

// Constants for prioritizing different types of todos
const PRIORITY_LEVELS = {
    FIXME: 'A',       // Critical issues to fix
    TODO: 'B',        // Standard todos
    OPTIMIZE: 'C',    // Optimization opportunities 
    REFACTOR: 'D',    // Refactoring opportunities
    FUTURE: 'E'       // Future enhancements
};

// File patterns to search
const FILE_PATTERNS = [
    '**/*.svelte',
    '**/*.js',
    '**/*.ts',
    '**/*.css',
    '!**/node_modules/**',
    '!**/.svelte-kit/**',
    '!**/build/**'
];

// Comment pattern matchers
const TODO_PATTERNS = [
    { regex: /\/\/\s*FIXME:?\s*(.*)/g, type: 'FIXME' },
    { regex: /\/\/\s*TODO:?\s*(.*)/g, type: 'TODO' },
    { regex: /\/\/\s*OPTIMIZE:?\s*(.*)/g, type: 'OPTIMIZE' },
    { regex: /\/\/\s*REFACTOR:?\s*(.*)/g, type: 'REFACTOR' },
    { regex: /\/\/\s*FUTURE:?\s*(.*)/g, type: 'FUTURE' },
    { regex: /<!--\s*FIXME:?\s*(.*?)-->/g, type: 'FIXME' },
    { regex: /<!--\s*TODO:?\s*(.*?)-->/g, type: 'TODO' },
    { regex: /<!--\s*OPTIMIZE:?\s*(.*?)-->/g, type: 'OPTIMIZE' },
    { regex: /<!--\s*REFACTOR:?\s*(.*?)-->/g, type: 'REFACTOR' },
    { regex: /<!--\s*FUTURE:?\s*(.*?)-->/g, type: 'FUTURE' }
];

/**
 * Finds all files matching the specified patterns
 * 
 * @param {string} baseDir - Base directory to search
 * @param {Array<string>} patterns - Glob patterns to include/exclude
 * @returns {Promise<Array<string>>} Array of matching file paths
 */
async function findFiles(baseDir, patterns) {
    let command = 'find ' + baseDir;
    
    // Filter by file types we're interested in
    command += ' -type f \\( -name "*.svelte" -o -name "*.js" -o -name "*.ts" -o -name "*.css" \\)';
    
    // Exclude patterns
    command += ' | grep -v "node_modules" | grep -v ".svelte-kit" | grep -v "build"';
    
    try {
        const result = execSync(command, { encoding: 'utf-8' });
        return result.trim().split('\n').filter(Boolean);
    } catch (error) {
        console.error('Error finding files:', error);
        return [];
    }
}

/**
 * Extracts todo comments from a file
 * 
 * @param {string} filePath - Path to the file
 * @returns {Promise<Array<object>>} Array of todo objects
 */
async function extractTodos(filePath) {
    try {
        const content = await fs.readFile(filePath, 'utf-8');
        const todos = [];
        
        for (const pattern of TODO_PATTERNS) {
            let match;
            // Reset regex lastIndex to ensure we start from the beginning
            pattern.regex.lastIndex = 0;
            
            while ((match = pattern.regex.exec(content)) !== null) {
                const description = match[1].trim();
                const lineNum = content.substring(0, match.index).split('\n').length;
                
                todos.push({
                    type: pattern.type,
                    description: description,
                    priority: PRIORITY_LEVELS[pattern.type],
                    file: path.relative(APP_DIR, filePath),
                    line: lineNum
                });
            }
        }
        
        return todos;
    } catch (error) {
        console.error(`Error reading file ${filePath}:`, error);
        return [];
    }
}

/**
 * Formats a todo in todo.txt format
 * 
 * @param {object} todo - Todo item object
 * @returns {string} Formatted todo.txt line
 */
function formatTodoLine(todo) {
    // Format: (A) Task description +project @context due:YYYY-MM-DD
    let line = '';
    
    // Add priority if available
    if (todo.priority) {
        line += `(${todo.priority}) `;
    }
    
    // Add description
    line += todo.description;
    
    // Add context for the type of todo
    line += ` @${todo.type.toLowerCase()}`;
    
    // Add file location as a project tag
    const fileExt = path.extname(todo.file).substring(1) || 'noext';
    line += ` +${fileExt}`;
    
    // Add source file as a special tag
    line += ` src:${todo.file}:${todo.line}`;
    
    return line;
}

/**
 * Analyzes imports in JavaScript/Svelte files for potential missing dependencies
 * 
 * @param {string} filePath - Path to the file
 * @returns {Promise<Array<object>>} Array of dependency-related todos
 */
async function analyzeImports(filePath) {
    // This function checks for patterns that might indicate missing or problematic imports
    // Only process JS/TS/Svelte files
    if (!['.js', '.ts', '.svelte'].includes(path.extname(filePath))) {
        return [];
    }
    
    try {
        const content = await fs.readFile(filePath, 'utf-8');
        const todos = [];
        
        // Check for commented-out imports
        const commentedImportRegex = /\/\/\s*(import .* from .*)/g;
        let match;
        
        while ((match = commentedImportRegex.exec(content)) !== null) {
            const lineNum = content.substring(0, match.index).split('\n').length;
            todos.push({
                type: 'TODO',
                description: `Check commented import: ${match[1]}`,
                priority: PRIORITY_LEVELS['TODO'],
                file: path.relative(APP_DIR, filePath),
                line: lineNum
            });
        }
        
        return todos;
    } catch (error) {
        console.error(`Error analyzing imports in ${filePath}:`, error);
        return [];
    }
}

/**
 * Main function to scan the codebase and generate todo.txt
 */
async function generateTodoTxt() {
    try {
        console.log('Searching for files in', APP_DIR);
        const files = await findFiles(APP_DIR, FILE_PATTERNS);
        console.log(`Found ${files.length} files to scan`);
        
        let allTodos = [];
        
        // Process each file
        let progress = 0;
        for (const file of files) {
            progress++;
            if (progress % 50 === 0 || progress === files.length) {
                console.log(`Processing file ${progress}/${files.length}`);
            }
            
            // Extract explicit TODO comments
            const todos = await extractTodos(file);
            allTodos = allTodos.concat(todos);
            
            // Analyze imports for additional potential todos
            const importTodos = await analyzeImports(file);
            allTodos = allTodos.concat(importTodos);
        }
        
        console.log(`Found ${allTodos.length} TODOs in the codebase`);
        
        // Sort todos by priority
        allTodos.sort((a, b) => {
            // First by priority level
            if (a.priority !== b.priority) {
                return a.priority.localeCompare(b.priority);
            }
            // Then by file
            if (a.file !== b.file) {
                return a.file.localeCompare(b.file);
            }
            // Finally by line number
            return a.line - b.line;
        });
        
        // Format todos
        const formattedTodos = allTodos.map(formatTodoLine);
        
        // Add header with timestamp
        const timestamp = new Date().toISOString();
        const header = [
            `# App TODOs generated on ${timestamp}`,
            `# Format: (PRIORITY) Description @type +filetype src:filepath:line`,
            `# Priority: A=FIXME, B=TODO, C=OPTIMIZE, D=REFACTOR, E=FUTURE`,
            `# Total: ${formattedTodos.length} items`,
            ''
        ];
        
        // Write to file
        await fs.writeFile(OUTPUT_PATH, [...header, ...formattedTodos].join(os.EOL));
        
        console.log(`Generated app-todos.txt at ${OUTPUT_PATH} with ${formattedTodos.length} items`);
    } catch (error) {
        console.error('Error generating todo.txt:', error);
    }
}

// If this script is run directly, execute the main function
if (require.main === module) {
    generateTodoTxt();
}

// Export for potential use in other scripts
module.exports = {
    generateTodoTxt
}; 