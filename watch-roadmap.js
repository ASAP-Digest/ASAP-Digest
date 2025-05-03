// watch-roadmap.js
// This script watches the md-docs/ROADMAP_TASKS.md file for changes
// and runs the generate-todotxt.js script automatically.
// Requires 'chokidar': Run `pnpm add -D chokidar`
// Usage: Run `node watch-roadmap.js` in your terminal (potentially in the background).

const chokidar = require('chokidar');
const { exec } = require('child_process');
const path = require('path');

// Define paths relative to this script's location (project root)
const roadmapPath = path.join(__dirname, 'md-docs/ROADMAP_TASKS.md');
const generationScriptPath = path.join(__dirname, 'generate-todotxt.js');

console.log(`[Watcher] Watching ${roadmapPath} for changes...`);

// Initialize watcher.
const watcher = chokidar.watch(roadmapPath, {
  persistent: true,        // Keep watching even if script seems idle
  ignoreInitial: true,     // Don't run the script when the watcher starts
  awaitWriteFinish: {       // Helps prevent running multiple times on rapid saves
    stabilityThreshold: 500, // Milliseconds to wait for file size stability
    pollInterval: 100        // How often to check
  }
});

// Add event listeners.
watcher
  .on('change', filePath => {
    console.log(`[Watcher] Detected change in ${filePath}. Running generate-todotxt.js...`);
    // Execute the generation script using Node
    exec(`node "${generationScriptPath}"`, (error, stdout, stderr) => {
      if (error) {
        console.error(`[Watcher] Error running script: ${error.message}`);
        return;
      }
      if (stderr) {
        console.error(`[Watcher] Script stderr: ${stderr}`);
      }
      // Log the output from generate-todotxt.js
      console.log(`[Watcher] Script stdout:
${stdout}`); 
      console.log('[Watcher] generate-todotxt.js finished.');
    });
  })
  .on('error', error => console.error(`[Watcher] Error occurred: ${error}`))
  .on('ready', () => console.log('[Watcher] Initial scan complete. Ready for changes.'));

console.log('[Watcher] Process started. Press CTRL+C to stop watching.'); 