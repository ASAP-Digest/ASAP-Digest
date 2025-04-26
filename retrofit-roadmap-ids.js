#!/usr/bin/env node
/**
 * retrofit-roadmap-ids.js
 * 
 * @description Migrates md-docs/ROADMAP_TASKS.md to ensure compliance with RSVP (Roadmap Syntax & Validation Protocol).\n *              Specifically, it renumbers tasks with allowed prefixes (UI, A11Y, etc.) to guarantee:\n *                1. Unique IDs across the entire file.\n *                2. Sequential and gapless parent task IDs (e.g., UI-1, UI-2, UI-3) based *only* on file order.\n *                3. Correct hierarchical numbering for children/grandchildren (e.g., UI-1.1, UI-1.2, UI-1.2.1).\n *              It preserves all other line content (status emojis, descriptions, tags).\n * @protocol @roadmap-syntax-validation-protocol.mdc - Defines the target ID syntax, rules, and allowed prefixes.\n * @input md-docs/ROADMAP_TASKS.md - The Markdown roadmap file to process.\n * @output md-docs/ROADMAP_TASKS.md (overwritten) - The updated roadmap file with new IDs.\n * @backup md-docs/ROADMAP_TASKS.md.bak - A backup of the original file created before modification.\n * \n * @usage node retrofit-roadmap-ids.js [-v | --verbose] - Run the script (optional verbose logging).\n * \n * @version 1.1.0 - Refined parsing and validation logic.\n */
const fs = require('fs').promises; // Use asynchronous file system operations
const path = require('path'); // For handling file paths reliably

// --- Command Line Argument Parsing ---
// Check for verbose flag (-v or --verbose)
const verbose = process.argv.includes('-v') || process.argv.includes('--verbose');

// --- Configuration Constants ---

// Absolute path to the roadmap file.
const ROADMAP_PATH = path.join(__dirname, 'md-docs/ROADMAP_TASKS.md');
// Absolute path for the backup file.
const BACKUP_PATH = path.join(__dirname, 'md-docs/ROADMAP_TASKS.md.bak');

// --- Protocol-Defined Constants ---

// Regex to match and capture a valid RSVP ID within brackets, allowing for surrounding whitespace.
// Captures the ID itself (e.g., "UI-3.1") in group 1.
// Max depth allowed is 3 (PREFIX-Num.SubNum.SubSubNum).
const RSVP_ID_REGEX = /\[\s+([A-Z0-9]{3,6}-\d+(?:\.\d+){0,2})\s+\]/;

// Array of valid status emojis as defined in the protocol.
const STATUS_EMOJIS = ['⏳','🔄','⏸️','🔬','🧪','✅','❌','🔧'];

// Set of allowed prefixes for task IDs, derived from the protocol.
// Tasks with prefixes not in this set will be ignored during renumbering.
const ALLOWED_PREFIXES = new Set([
  'AUTH', 'UI', 'CORE', 'WIDGET', 'PWA', 'BUG', 'REFACTOR', 'DOCS', 'TEST', 'DB', 'INFRA', 'A11Y'
]);

// Maximum number of validation errors to display to avoid flooding the console.
const MAX_ERRORS_TO_DISPLAY = 20;

// --- Helper Functions ---

/**
 * Extracts the prefix (e.g., "UI") from a task line's ID.
 * Handles varying whitespace within the brackets.
 * @param {string} line - The line containing the task.
 * @param {string | null} fallback - Value to return if no prefix is found.
 * @returns {string | null} The extracted prefix or the fallback value.
 */
function extractPrefix(line, fallback) {
  // Matches the beginning of the ID block (e.g., "[UI-") and captures the prefix.
  // Looks for 2-8 uppercase alphanumeric characters followed by a hyphen, inside brackets with optional space.
  /*
   * Regex Breakdown:
   * \[       : Matches the literal opening square bracket.
   * \s*      : Matches zero or more whitespace characters (space, tab) after the bracket.
   * (        : Start capturing group 1 (this is what gets extracted as the prefix).
   * [A-Z0-9]: Matches any uppercase letter or digit.
   * {2,8}   : Specifies that the preceding character set must occur 2 to 8 times.
   * )        : End capturing group 1.
   * -        : Matches the literal hyphen immediately after the prefix.
   * /        : End of regex pattern.
   */
  // Execute the regex against the input line to find the first match.
  const match = line.match(/\[\s*([A-Z0-9]{2,8})-/);
  // If a match is found, extract the captured prefix (group 1), otherwise use the fallback.
  const prefix = match ? match[1] : fallback;
  return prefix;
}

/**
 * Determines the hierarchical depth of a task based on leading indentation.
 * Assumes 2 spaces per indentation level (tabs are converted to 2 spaces).
 * @param {string} line - The line containing the task.
 * @returns {number | null} Depth level (0 for root, 1 for child, 2 for grandchild) or null if not a list item.
 */
function getDepth(line) {
  // Match leading whitespace (spaces or tabs) before the Markdown list item marker "- ".
  /*
   * Regex Breakdown:
   * ^        : Matches the beginning of the string (ensures indentation is at the start).
   * (        : Start capturing group 1 (the leading whitespace).
   * [ \t]* : Matches zero or more space or tab characters.
   * )        : End capturing group 1.
   * -        : Matches the literal hyphen followed by a literal space.
   * /        : End of regex pattern.
   */
  const match = line.match(/^([ \t]*)- /);
  let depth = null; // Initialize depth
  if (!match) {
    // If the line doesn't start with indentation + "- ", it's not a task we process for depth.
    depth = null; 
  } else {
    // Normalize whitespace: treat each tab as 2 spaces for consistent calculation.
    const ws = match[1].replace(/\t/g, '  ');
    const spaces = ws.length;
    // Determine depth based on the number of normalized spaces.
    if (spaces < 2) depth = 0;       // 0 or 1 space -> Root level
    else if (spaces < 4) depth = 1; // 2 or 3 spaces -> Child level
    else depth = 2;                 // 4+ spaces -> Grandchild level (or deeper, treated as 2)
  }
  return depth;
}

/**
 * Checks if a line has the basic structural markers of a task line required for parsing.
 * This is a preliminary check before more detailed parsing.
 * @param {string} line - The line to check.
 * @returns {boolean} True if the line starts with indentation, dash, space, an emoji, and contains an opening bracket later.
 */
function isTaskLine(line) {
  // Simplified Regex: Checks for essential start pattern:
  // ^[ \t]* : Optional leading spaces/tabs
  // - [ ]*  : Dash, required space, optional space/tab (flexible)
  // [⏳🔄⏸️🔬🧪✅❌🔧]: A valid status emoji
  // .*?\[   : Any characters (non-greedy), followed by an opening bracket
  /*
   * Regex Breakdown:
   * ^[ \t]* : Matches the start of the line with optional leading spaces/tabs.
   * -        : Matches the literal hyphen followed by one or more spaces/tabs.
   * [⏳...]  : Matches one of the allowed status emojis.
   * .*?      : Matches any character (except newline) zero or more times, non-greedily.
   * \[       : Matches the literal opening square bracket somewhere after the emoji.
   * /        : End of regex pattern.
   * Note: This is intentionally loose; it only confirms the *start* looks like a task
   *       and that an opening bracket exists. Actual ID extraction happens later.
   */
  const simplifiedRegex = /^[ \t]*-[ \t]*[⏳🔄⏸️🔬🧪✅❌🔧].*?\[/;
  const result = simplifiedRegex.test(line);
  return result;
}

/**
 * (Optional helper - Currently Unused but kept for potential future use)
 * Extracts a potential prefix from a Markdown heading line (e.g., "## Section [PREFIX-...").
 * @param {string} line - The heading line.
 * @returns {string | null} The extracted prefix or null.
 */
function headingPrefix(line) {
  const m = line.match(/^#+\s+.*\[s*([A-Z0-9]{3,6})-/);
  return m ? m[1] : null;
}

/**
 * Normalizes whitespace and tag formatting in a task line for internal consistency during processing.
 * Ensures IDs are like `[ ID ]` and tags are like ` • [ key:value ]`.
 * Helps make subsequent regex matching more reliable.
 * Note: This modifies the line for *processing* but the final output uses the ID replacement logic.
 * @param {string} line - The original task line.
 * @returns {string} The normalized task line.
 */
function normalizeTaskLine(line) {
  let original = line;
  // Convert tabs to 2 spaces for consistent indentation handling.
  line = line.replace(/\t/g, ' ');
  // Ensure single space after list marker (". ").
  line = line.replace(/^\s*-\s*/, '- ');
  // Ensure single space after status emoji.
  line = line.replace(/- ([⏳🔄⏸️🔬🧪✅❌🔧])\s*/, '- $1 ');
  // Ensure single space inside ID brackets: `[ ID ]`.
  /* 
   * Regex Breakdown (ID Normalization):
   * \\[        : Matches the literal opening bracket.
   * \\s*       : Matches zero or more whitespace characters.
   * (          : Start capturing group 1 (the ID itself).
   * [A-Z0-9] : Matches allowed characters in the prefix.
   * {3,6}    : Prefix length constraint.
   * -          : Matches the literal hyphen.
   * \\d+       : Matches the main task number (one or more digits).
   * (?:        : Start non-capturing group for sub-levels.
   * \\.       : Matches a literal dot.
   * \\d+       : Matches sub-level number (one or more digits).
   * )          : End non-capturing group.
   * {0,2}      : Allow zero, one, or two sub-levels (max depth 3 total).
   * )          : End capturing group 1.
   * \\s*       : Matches zero or more whitespace characters.
   * \\]        : Matches the literal closing bracket.
   * /          : End of regex pattern.
   * Replacement: '[ $1 ]' - Puts exactly one space before and after the captured ID ($1).
   */
  line = line.replace(/\[\s*([A-Z0-9]{3,6}-\d+(?:\.\d+){0,2})\s*\]/, '[ $1 ]');
  // Normalize tags: ` • [key:value]` -> ` • [ key:value ]`.
  /*
   * Regex Breakdown (Tag Normalization):
   * \s*•\s*  : Matches optional space, literal bullet, optional space.
   * \[\s*    : Matches literal bracket, optional space.
   * ([^\]:]+?): Capture group 1: Matches the key (one or more characters that are not `]` or `:`, non-greedy).
   * \s*:\s*  : Matches optional space, literal colon, optional space.
   * ([^\]]+?) : Capture group 2: Matches the value (one or more characters that are not `]`, non-greedy).
   * \s*\]    : Matches optional space, literal closing bracket.
   * /g         : Global flag - replace all occurrences on the line.
   * Replacement: ` • [ ${key.trim()}:${value.trim()} ]` - Reconstructs the tag with trimmed key/value and consistent spacing.
   */
  line = line.replace(/\s*•\s*\[\s*([^\\\]:]+?)\s*:\s*([^\\\]]+?)\s*\]/g, (m, key, value) => {
    return ` • [ ${key.trim()}:${value.trim()} ]`;
  });
  // Remove any trailing whitespace.
  line = line.replace(/\s+$/, '');
  return line;
}

/**
 * Logs a debug message to the console only if verbose mode is enabled.
 * @param {string} message - The debug message to log.
 * @param {Array<string>} debugArray - The array holding debug messages (currently unused but kept for potential future extension).
 */
function logDebug(message, debugArray) {
  // debugArray.push(message); // Kept if we want to store logs
  if (verbose) {
    console.log('[DEBUG]', message);
  }
}

/**
 * The main function to perform the roadmap ID retrofit.
 * Orchestrates parsing, renumbering, validation, and rewriting.
 */
async function retrofitRoadmap() {
  // Create a backup before making any changes.
  console.log(`Creating backup at ${BACKUP_PATH}...`);
  await fs.copyFile(ROADMAP_PATH, BACKUP_PATH);

  // Read the original roadmap content.
  console.log(`Reading roadmap file: ${ROADMAP_PATH}...`);
  const orig = await fs.readFile(ROADMAP_PATH, 'utf8');
  const lines = orig.split(/\r?\n/); // Split into lines.

  // Initialize arrays for logging and error tracking.
  let warnings = [];
  let errors = [];

  // --- PASS 1: Parse and build full hierarchical model ---
  // Goal: Iterate through each line, identify tasks, determine their hierarchy (parent/child/grandchild),
  //       extract key information (original ID, prefix, status), and build a structured representation (`tasks` array)
  //       while also linking children to their parents.
  // Uses helper functions: isTaskLine, getDepth, extractPrefix.
  const tasks = []; // Array to hold all parsed task objects.
  const lineToTask = {}; // Map line index to its corresponding task object for easy lookup.
  let stack = []; // Stack to keep track of the current parent/grandparent during parsing.
  const allOriginalIds = new Set(); // Collect all original IDs found for later validation.

  // Iterate through each line of the original file.
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const originalLine = line; // Keep original for checking done tag later

    // Preliminary check: Skip lines that don't match the basic task structure.
    if (!isTaskLine(originalLine)) { 
      continue; // Skip to next line
    }

    // Parse essential task details using helper functions.
    const depth = getDepth(originalLine); // Determine hierarchy depth (0, 1, or 2).
    const prefix = extractPrefix(originalLine, null); // Extract the prefix (e.g., UI). Returns null if no valid prefix found.
    const idMatch = originalLine.match(/\[\s*([A-Z0-9]{3,6}-\d+(?:\.\d+){0,2})\s*\]/); 
    const origId = idMatch ? idMatch[1] : null; // Store the original ID string.
    const statusMatch = originalLine.match(/^[ \\t]*-\\s*([⏳🔄⏸️🔬🧪✅❌🔧])/); // Extract status emoji
    const statusEmoji = statusMatch ? statusMatch[1] : null;

    // Store the original ID if it exists (used for validation later).
    if (origId) allOriginalIds.add(origId);

    // Create a task object to store parsed information.
    const task = {
      idx: i, // Original line index.
      depth, // Hierarchy depth (0, 1, 2, or null if parsing failed).
      prefix, // ID prefix (e.g., \'UI\', \'A11Y\', or null).
      origId, // Original ID string (e.g., \'A11Y-3\', or null).
      statusEmoji, // Status emoji (e.g., '⏳').
      originalLine, // Store original line content for reference and done tag check.
      children: [], // Array to hold child tasks.
      parent: null, // Reference to the parent task object.
    };

    // --- Establish Parent/Child Relationships using the stack ---
    // The stack helps track the current hierarchy context.
    // stack[0] = current grandparent (if any)
    // stack[1] = current parent (if any)
    if (depth === 0) { // Top-level task (parent).
      stack = [task]; // Reset stack with this new parent.
    } else if (depth === 1 && stack.length >= 1) { // Child task.
      task.parent = stack[0]; // Link to parent (the last item added at depth 0).
      stack[0].children.push(task); // Add this task to parent's children array.
      stack = [stack[0], task]; // Update stack: [parent, this new child].
    } else if (depth === 2 && stack.length >= 2) { // Grandchild task.
      task.parent = stack[1]; // Link to parent (the last item added at depth 1).
      stack[1].children.push(task); // Add this task to parent's children array.
      stack = [stack[0], stack[1], task]; // Update stack: [grandparent, parent, this new grandchild].
    } else if (depth !== null) {
      // If depth is valid but stack context is wrong (e.g., depth 1 with no parent on stack),
      // it indicates malformed hierarchy in the input file.
      warnings.push(`Malformed hierarchy or unexpected depth (${depth}) at line ${i+1}: ${line}`);
      stack = []; // Reset stack to avoid incorrect linking for subsequent tasks.
    }
    // --- End Relationship Logic ---

    tasks.push(task); // Add the parsed task object to the main list.
    lineToTask[i] = task; // Map the line index to the task object.
  }

  // --- Parent Task Collection & Sorting ---
  // Goal: Group all identified top-level (depth 0) tasks by their prefix
  //       and then sort each group by their original line number to ensure
  //       sequential processing based on file order.
  const prefixToParents = {}; // Object to hold arrays of parent tasks, keyed by prefix.

  // Collect parent tasks
  for (const task of tasks) {
    // Re-evaluate depth/prefix directly from the original line for safety, though task object should be correct.
    const currentDepth = getDepth(task.originalLine);
    const currentPrefix = extractPrefix(task.originalLine, null);
    const isAllowed = currentPrefix ? ALLOWED_PREFIXES.has(currentPrefix) : false;

    // Check if it meets the criteria: is depth 0 and has an allowed prefix.
    if (currentDepth === 0 && isAllowed) {
      // Ensure the array for this prefix exists.
      if (!prefixToParents[currentPrefix]) {
        prefixToParents[currentPrefix] = [];
      }
      // Add the task object to the correct prefix array.
      prefixToParents[currentPrefix].push(task);
    }
  }

  // After grouping parents by prefix, ensure they are sorted by original file order.
  // This is CRUCIAL for the sequential numbering requirement.
  for (const prefix in prefixToParents) {
    // Sort parent tasks within each prefix group based on their original line index (`idx`).
    prefixToParents[prefix].sort((a, b) => a.idx - b.idx);
  }

  // --- PASS 2: Assign New IDs to a Temporary Map ---
  // Goal: Calculate the intended new sequential/hierarchical ID for every task and store it.
  const newIdAssignments = new Map(); // Map: task.idx -> newIdString
  
  // Iterate through each prefix group.
  for (const prefix in prefixToParents) {
    const parents = prefixToParents[prefix]; // Get all parent tasks for this prefix.
    // Assign IDs 1 to N based *strictly* on their order of appearance in the file (due to sorting earlier).
    for (let i = 0; i < parents.length; i++) { 
      const parentNum = i + 1; // Calculate sequential number (1-based index).
      const parentTask = parents[i]; // Get the parent task object.
      
      // Construct the new parent ID (e.g., UI-1, UI-2).
      const newParentId = `${prefix}-${parentNum}`; 
      // Store the assignment: map original line index to the new ID string.
      newIdAssignments.set(parentTask.idx, newParentId); 
    }
  }

  // --- Assign Child/Grandchild IDs ---
  // Iterate through all *parsed* tasks again.
  // If a task is a child/grandchild, find its parent's *newly assigned* ID
  // and construct the hierarchical ID accordingly.
  for (const task of tasks) {
    if (task.depth === 1 && task.parent) { // Process children
      // Get the NEW ID assigned to the parent task in the step above.
      const parentNewId = newIdAssignments.get(task.parent.idx);
      if (parentNewId) { // Ensure the parent was actually assigned an ID (it should have been)
        // Find the index of this child within its parent's children array (0-based).
        const childIndex = task.parent.children.indexOf(task);
        if (childIndex !== -1) { // Should always be found
          const childNum = childIndex + 1; // Convert 0-based index to 1-based number.
          const newChildId = `${parentNewId}.${childNum}`; // Construct hierarchical ID (e.g., UI-1.1)
          // Store the assignment for this child task.
          newIdAssignments.set(task.idx, newChildId);
        } else {
          // This warning indicates an internal inconsistency if a child isn't in its parent's list.
          warnings.push(`Could not find child task at line ${task.idx+1} in parent's children during Pass 2.`);
        }
      }
    } else if (task.depth === 2 && task.parent) { // Process grandchildren
      // Get the NEW ID assigned to the immediate parent (which is a child task itself).
      const childNewId = newIdAssignments.get(task.parent.idx);
      if (childNewId) { // Ensure the child/parent was assigned an ID
        // Find the index of this grandchild within its parent's children array.
        const grandIndex = task.parent.children.indexOf(task);
        if (grandIndex !== -1) {
          const grandNum = grandIndex + 1; // Convert 0-based index to 1-based number.
          const newGrandChildId = `${childNewId}.${grandNum}`; // Construct hierarchical ID (e.g., UI-1.1.1)
          // Store the assignment for this grandchild task.
          newIdAssignments.set(task.idx, newGrandChildId);
        } else {
          // This warning indicates an internal inconsistency.
          warnings.push(`Could not find grandchild task at line ${task.idx+1} in parent's children during Pass 2.`);
        }
      }
    }
  }

  const assignmentsToLog = {};
  for (const [key, value] of newIdAssignments.entries()) {
      assignmentsToLog[key] = value;
  }

  // --- PASS 3: Pre-Write Validation --- 
  // Goal: Validate the calculated newIdAssignments map *before* writing to the file.
  // Checks for: 
  //   1. Duplicate ID assignments.
  //   2. Gaps or non-sequential numbering within parent/child/grandchild levels for each prefix.
  //   3. Presence of `• [ done:MM.DD.YY ]` tag on completed (✅) tasks.
  errors.length = 0; // Reset errors array for this pass.
  const idCounts = new Map(); // Map: newIdString -> count (for duplicate check).
  // const finalIdSet = new Set(); // Not strictly needed if duplicate check works.

  // Populate counts from the assignments map.
  for (const [taskIndex, newId] of newIdAssignments.entries()) {
      if (!newId) continue; // Skip tasks that didn't get an ID.
      idCounts.set(newId, (idCounts.get(newId) || 0) + 1);
  }

  // 1. Check for Duplicate ID Assignments.
  for (const [id, count] of idCounts.entries()) {
      if (count > 1) {
          // Find all lines assigned this duplicate ID for clearer error reporting.
          const conflictingLines = [];
          for (const [idx, assignedId] of newIdAssignments.entries()) {
              if (assignedId === id) {
                  // Include original line number and original ID (if available) in the error.
                  conflictingLines.push(`${idx + 1} (orig: ${lineToTask[idx]?.origId || 'N/A'})`);
              }
          }
          errors.push(`Duplicate ID Assignment: ID ${id} is assigned to multiple lines: ${conflictingLines.join(', ')}.`);
      }
  }

  // Initialize counters for sequence validation.
  // These track the *next expected* number for each level within a scope (prefix or parent ID).
  const parentCounters = {}; // Key: prefix, Value: expected next parent number (e.g., { UI: 1, A11Y: 1 }).
  const childCounters = {}; // Key: parentNewId, Value: expected next child number (e.g., { 'UI-1': 1, 'UI-2': 1 }).
  const grandChildCounters = {}; // Key: childNewId, Value: expected next grandchild number (e.g., { 'UI-1.1': 1 }).
  for(const prefix of ALLOWED_PREFIXES) { parentCounters[prefix] = 1; } // Initialize all allowed prefixes to expect #1 first.

  // 2. Validate Sequences (Parents, Children, Grandchildren).
  // Iterate through the *original task order* to check sequences correctly.
  for (const task of tasks) {
      const newId = newIdAssignments.get(task.idx);
      if (!newId) continue; // Skip tasks that weren't assigned a new ID (e.g., wrong prefix).

      // --- Parent Sequence Validation ---
      if (task.depth === 0 && ALLOWED_PREFIXES.has(task.prefix)) {
          const expectedNum = parentCounters[task.prefix]; // Get the expected number for this prefix.
          const actualNumMatch = newId.match(/-(\d+)$/); // Extract the number from the assigned ID.
           if (!actualNumMatch) {
             // Should not happen if Pass 2 worked, but validates format.
             errors.push(`Parent ID format error: Cannot parse parent number from ${newId} at line ${task.idx+1}.`);
           } else {
             const actualNum = parseInt(actualNumMatch[1], 10);
             if(actualNum !== expectedNum) {
               // This is the core sequence gap check for parents.
               errors.push(`Parent Sequence Gap/Error for prefix ${task.prefix}: Expected ${task.prefix}-${expectedNum}, but got ${newId} at line ${task.idx+1}.`);
             }
             // Initialize child counter for this validated parent ID, ready for its children.
             childCounters[newId] = 1;
             // Increment the counter for the *next* parent task with this prefix.
             parentCounters[task.prefix]++; 
           }
      }

      // --- Child Sequence Validation ---
      if (task.depth === 1 && task.parent) {
        const parentNewId = newIdAssignments.get(task.parent.idx); // Get the parent's *new* ID.
        if (parentNewId) {
          if (!childCounters[parentNewId]) {
              // This indicates a logic error - the parent wasn't validated or counter wasn't initialized.
              errors.push(`Validation Logic Error: Child counter missing for parent ${parentNewId} (child at line ${task.idx+1})`);
              continue; // Skip further validation for this child if parent context is broken.
          }
          const expectedChildNum = childCounters[parentNewId]; // Get the expected number for this child.
          const actualChildNumMatch = newId.match(/\.(\d+)$/); // Extract the last number segment.
          if (!actualChildNumMatch) {
             errors.push(`Child ID format error: Cannot parse child number from ${newId} at line ${task.idx+1}.`);
          } else {
             const actualChildNum = parseInt(actualChildNumMatch[1], 10);
             if (actualChildNum !== expectedChildNum) {
               // Core sequence gap check for children of the same parent.
               errors.push(`Child task sequence error for parent ${parentNewId}: Expected ${parentNewId}.${expectedChildNum}, got ${newId} at line ${task.idx+1}.`);
             }
             // Initialize grandchild counter for this child ID, ready for its children.
             grandChildCounters[newId] = 1;
             // Increment the counter for the *next* child of this *same parent*.
             childCounters[parentNewId]++; 
          }
        } else {
           // This means the parent task object didn't get an ID assigned in Pass 2.
           errors.push(`Validation Error: Parent ID missing in assignments map for child task at line ${task.idx+1}.`);
        }
      }

      // --- Grandchild Sequence Validation ---
      if (task.depth === 2 && task.parent) {
        const childNewId = newIdAssignments.get(task.parent.idx); // Get the immediate parent's (a child) *new* ID.
        if (childNewId) {
           if (!grandChildCounters[childNewId]) {
               // Logic error - child wasn't validated or counter wasn't initialized.
               errors.push(`Validation Logic Error: Grandchild counter missing for child ${childNewId} (grandchild at line ${task.idx+1})`);
               continue; 
           }
           const expectedGrandChildNum = grandChildCounters[childNewId]; // Get expected number.
           const actualGrandChildNumMatch = newId.match(/\.(\d+)$/); // Extract last number segment.
           if (!actualGrandChildNumMatch) {
             errors.push(`Grandchild ID format error: Cannot parse grandchild number from ${newId} at line ${task.idx+1}.`);
           } else {
             const actualGrandChildNum = parseInt(actualGrandChildNumMatch[1], 10);
             if (actualGrandChildNum !== expectedGrandChildNum) {
               // Core sequence gap check for grandchildren of the same child.
               errors.push(`Grandchild task sequence error for child ${childNewId}: Expected ${childNewId}.${expectedGrandChildNum}, got ${newId} at line ${task.idx+1}.`);
             }
             // Increment counter for the *next* grandchild under this *same child*.
             grandChildCounters[childNewId]++; 
          }
        } else {
            // This means the parent (child) task object didn't get an ID assigned in Pass 2.
            errors.push(`Validation Error: Child ID missing in assignments map for grandchild task at line ${task.idx+1}.`);
        }
      } // End of Grandchild Sequence Validation
    
      // --- `done:` Tag Validation --- 
      // Completed tasks (✅) MUST have a `• [ done:MM.DD.YY ]` tag.
      if (task.statusEmoji === '✅') {
        /*
         * Regex Breakdown (Done Tag Check):
         * \s*•\s*  : Matches optional space, bullet, optional space.
         * \[\s*    : Matches opening bracket, optional space.
         * done:    : Matches the literal key "done:".
         * \d{2}    : Matches exactly two digits (Month).
         * \.       : Matches a literal dot.
         * \d{2}    : Matches exactly two digits (Day).
         * \.       : Matches a literal dot.
         * \d{2}    : Matches exactly two digits (Year).
         * \s*\]    : Matches optional space, closing bracket.
         * /          : End regex pattern.
         */
        const doneTagRegex = /\s*•\s*\[\s*done:\d{2}\.\d{2}\.\d{2}\s*\]/; // Regex to find the done tag.
        if (!doneTagRegex.test(task.originalLine)) {
          // Report error if a completed task is missing the required tag.
          errors.push(`Missing required '• [ done:MM.DD.YY ]' tag for completed task at line ${task.idx+1}: ${task.originalLine.trim()}`);
        }
      }
  }

  // --- Error Reporting and Halt --- 
  if (errors.length > 0) {
    console.error(`RSVP migration failed due to ${errors.length} validation error(s):`);
    const errorsToShow = errors.slice(0, MAX_ERRORS_TO_DISPLAY);
    errorsToShow.forEach(e => console.error('  -', e));
    if (errors.length > MAX_ERRORS_TO_DISPLAY) {
      console.error(`  ... (${errors.length - MAX_ERRORS_TO_DISPLAY} more errors not shown)`);
    }
    return; 
  }

  // --- PASS 4: Rewrite File --- 
  const newLines = [];

  for (let i = 0; i < lines.length; i++) {
    let line = lines[i];
    const assignedNewId = newIdAssignments.get(i);
    
    if (assignedNewId) {
      // Find the existing ID block (e.g., "[ UI-OLD ]" or "[A11Y-3]") using a simple regex.
      // This regex finds the first occurrence of content within square brackets.
      /*
       * Regex Breakdown (Find Old ID Block for Replacement):
       * \[      : Matches the literal opening bracket.
       * [^\]]+ : Matches one or more characters that are NOT a closing bracket (`]`). This captures the content inside.
       * \]      : Matches the literal closing bracket.
       * /        : End regex pattern.
       * Note: This is intentionally broad to find *any* existing bracketed content where the ID should be.
       *       It assumes the first bracketed item on the line is the ID, which should hold true for valid task lines.
       */
      const oldIdMatch = line.match(/\[[^\]]+\]/); 

      if (oldIdMatch) {
        const newIdBlock = `[ ${assignedNewId} ]`; 
        line = line.replace(oldIdMatch[0], newIdBlock); 
      } else {
        warnings.push(`No ID block found to replace at line ${i+1} despite having new ID ${assignedNewId}: ${line}`);
      }
    } 
    
    newLines.push(line);
  }

  // --- Write the final output --- 
  console.log(`Writing updated roadmap to ${ROADMAP_PATH}...`);
  await fs.writeFile(ROADMAP_PATH, newLines.join('\n'), 'utf8');

  // --- Final Report & Validation --- 
  console.log(`RSVP migration complete. Updated file: ${ROADMAP_PATH}`);
  if (warnings.length) {
    console.warn('Warnings:');
    warnings.forEach(w => console.warn('  -', w));
  }

  let invalids = [];
  for (let i = 0; i < newLines.length; i++) {
    const l = newLines[i];
    if (isTaskLine(l)) {
      // Use the precise regex that defines a valid task line structure, including optional tags.
      /*
       * Regex Breakdown (Final Validation Check):
       * ^         : Start of the line.
       * \s*-\s+   : List marker (`-`) with surrounding whitespace.
       * [⏳...]   : One status emoji.
       * \s+       : One or more spaces before the ID.
       * \[\s*     : Opening bracket `[` with optional internal space.
       * (...)     : Capturing group 1: The ID itself (PREFIX-Num.Sub.SubSub).
       *   [A-Z0-9]{3,6} : Prefix.
       *   -         : Hyphen.
       *   \d+       : Parent number.
       *   (?:\.\d+){0,2} : Zero to two sub-level groups (non-capturing).
       * \s*\]     : Optional internal space and closing bracket `]`.
       * \s+       : One or more spaces after the ID.
       * .*?       : The task description (non-greedy match).
       * (         : Start capturing group 2 (for optional tags).
       * \s+•\s+  : Space, bullet, space.
       * \[\s*     : Opening bracket `[` with optional internal space.
       * [^:]+     : Tag key (anything not a colon).
       * :         : Literal colon.
       * [^\]]+    : Tag value (anything not a closing bracket).
       * \s*\]     : Optional internal space and closing bracket `]`.
       * )*        : End group 2, allowing zero or more tag groups.
       * $         : End of the line.
       */
      const finalCheckRegex = /^\s*-\s+[⏳🔄⏸️🔬🧪✅❌🔧]\s+\[\s*([A-Z0-9]{3,6}-\d+(?:\.\d+){0,2})\s*\]\s+.*?(\s+•\s+\[\s*[^:]+:[^\\\]]+\s*\])*$/;
      const m = l.match(finalCheckRegex);
      if (!m) {
        invalids.push(`Line ${i+1}: ${l.trim()}`);
      }
    }
  }
  if (invalids.length) {
    console.error('POST-WRITE VALIDATION FAILED: Invalid RSVP IDs detected in final output:');
    invalids.forEach(l => console.error('  -', l));
  } else {
    console.log('All task IDs in the updated file appear RSVP-compliant.');
  }
}

// --- Main Execution Block ---
retrofitRoadmap().catch(err => {
  console.error('Migration script failed with error:', err);
  process.exit(1);
});