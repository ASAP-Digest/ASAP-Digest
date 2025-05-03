/**
 * generate-todotxt.js
 * 
 * @description Parses the project's Markdown roadmap (`md-docs/ROADMAP_TASKS.md`) 
 *              and generates a prioritized `todo.txt` formatted file (`md-docs/todotasks.txt`).
 *              This script is a core component of the VeriDX Task System.
 * 
 * @protocol @task-system-documentation-protocol.mdc - Defines the overall system this script supports.
 * @protocol @roadmap-syntax-validation-protocol.mdc - Defines the expected input format.
 * @protocol @task-system-ranking-protocol.mdc - Defines the logic for assigning `rnk:X` tags used by RWS sort.
 * @protocol @work-session-management-protocol.mdc - Defines the RWS fallback logic that uses the output of this script.
 * @protocol @task-tracking-protocol.mdc - Defines the status emojis and lifecycle.
 * 
 * @input `md-docs/ROADMAP_TASKS.md` - The canonical source Markdown file.
 * @output `md-docs/todotasks.txt` - The generated, prioritized todo.txt file (overwritten on each run).
 * 
 * @features
 * - Extracts tasks, statuses (emojis), explicit ranks (`rnk:X`), due dates (`due:MM.DD.YY`), and timestamps.
 * - Assigns source tags (`src:+SourceName`) based on Markdown heading hierarchy.
 * - Applies context tags (`@status`) based on task status emojis.
 * - Sorts tasks based on a configurable strategy (`CURRENT_SORT_MODE`).
 * - Assigns `todo.txt` priorities (`(A)`, `(B)`, etc.) based on the selected sort mode (RWS respects explicit ranks).
 * - Formats output according to `todo.txt` rules + project conventions (tag order, separators).
 * - Includes basic validation for rank/due date tag format parsing.
 * - Configurable trigger control (`LIST_GENERATION`) for manual or automated execution.
 * 
 * @usage Run `node ./generate-todotxt.js`. Configure constants `CURRENT_SORT_MODE` and `LIST_GENERATION` as needed.
 * 
 * @example_output_line `(A) @inprogress Implement feature X - src:+TaskGroup_SubTask - due:04.22.25`
 * @example_output_line `x @completed Fix bug Y - src:+AnotherGroup - ts:04.10.25_|_10:00_AM_PDT`
 * 
 * @notes
 * - This script DOES NOT trigger itself. See trigger mechanisms below.
 * - Manual edits to `todotasks.txt` WILL BE LOST on the next run.
 * - Ensure `ROADMAP_TASKS.md` syntax adheres to @roadmap-syntax-validation-protocol.mdc.
 * 
 * --- Triggering Mechanisms & Control ---
 * See inline comments below the `LIST_GENERATION` constant for details on setting up
 * Git hooks or file watchers to run this script automatically.
 */

// --- Node.js Built-in Modules --- 
const fs = require('fs').promises; // Asynchronous file system operations
const path = require('path'); // Utilities for working with file and directory paths
const os = require('os'); // Provides operating system-related utility methods (used for EOL)

// --- Configuration ---
/**
 * @const {string} ROADMAP_PATH
 * @description Absolute path to the input Markdown roadmap file.
 *              Ref: @task-system-documentation-protocol.mdc
 */
const ROADMAP_PATH = path.join(__dirname, 'md-docs/ROADMAP_TASKS.md');

/**
 * @const {string} TODOTXT_PATH
 * @description Absolute path to the output todo.txt file.
 *              This file will be overwritten.
 *              Ref: @task-system-documentation-protocol.mdc
 */
const TODOTXT_PATH = path.join(__dirname, 'md-docs/todotasks.txt');

// --- Generation Control Constant ---
/**
 * @const {object} GENERATE_LIST
 * @description Enum-like object defining modes for controlling script execution trigger.
 *              Allows selective generation based on whether the script was run manually,
 *              via a Git hook, or via a file save watcher.
 */
const GENERATE_LIST = {
    GIT: 'GIT',           // Generate when triggered by Git hook (requires lint-staged setup)
    FILE_SAVE: 'FILE_SAVE', // Generate when triggered by file watcher (requires separate watcher script)
    MANUAL: 'MANUAL'        // Generate only when script is run directly from terminal
};

// --- SELECT GENERATION MODE HERE ---
/**
 * @config {string} LIST_GENERATION
 * @description Controls WHEN the script actually generates the todotasks.txt file.
 *              Set to MANUAL to prevent automatic generation from hooks/watchers.
 *              Set to GIT to enable generation via lint-staged pre-commit hook.
 *              Set to FILE_SAVE to enable generation via `watch-roadmap.js` (requires setup).
 *              See top-level script comments for setup details.
 */
const LIST_GENERATION = GENERATE_LIST.GIT; // Defaulting to Git hook trigger
// const LIST_GENERATION = GENERATE_LIST.FILE_SAVE;
// const LIST_GENERATION = GENERATE_LIST.MANUAL;

// --- Constants for Sorting ---
/**
 * @const {object} SORT_MODES
 * @description Enum-like object defining available sorting strategies for the output tasks.
 */
const SORT_MODES = {
    /** Sorts according to @work-session-management-protocol.mdc fallback logic:
     *  1. Explicit Rank (`rnk:X` from @task-system-ranking-protocol.mdc)
     *  2. Status Group (Paused > Testing > InProgress > Pending > Blocked > PendingTesting - per @task-tracking-protocol.mdc)
     *  3. Depth (for InProgress only, deeper first)
     *  4. Original Roadmap Order (stability)
     */
    RWS: 'RWS',
    /** Sorts alphabetically by task description. */
    ALPHA: 'ALPHA',
    /** Sorts strictly by status priority group (see RWS point 2), then original order. */
    STATUS: 'STATUS',
    /** Sorts primarily by source tag (`src:+` derived from roadmap hierarchy), then by RWS within each source group. */
    SOURCE: 'SOURCE'
};

// --- SELECT SORT MODE HERE ---
/**
 * @config {string} CURRENT_SORT_MODE
 * @description Sets the active sorting mode used when generating `todotasks.txt`.
 *              Change this value to alter the task order in the output file.
 *              Options: `SORT_MODES.RWS`, `SORT_MODES.ALPHA`, `SORT_MODES.STATUS`, `SORT_MODES.SOURCE`.
 */
const CURRENT_SORT_MODE = SORT_MODES.RWS; 
// const CURRENT_SORT_MODE = SORT_MODES.ALPHA;
// const CURRENT_SORT_MODE = SORT_MODES.STATUS;
// const CURRENT_SORT_MODE = SORT_MODES.SOURCE;


// --- Parsing Logic ---
/**
 * Parses the Markdown roadmap content into structured task objects.
 * 
 * @function parseRoadmap
 * @param {string} content - The full string content of the roadmap file.
 * @returns {Array<object>} An array of task objects, each containing properties like 
 *          `id`, `description`, `status`, `priorityGroup`, `rank`, `dueDate`, `timestamp`, 
 *          `depth`, `sourceTag`, `originalLine`.
 * @protocol @roadmap-syntax-validation-protocol.mdc - Defines the syntax this function expects.
 * @protocol @task-tracking-protocol.mdc - Defines the status emojis and their mapping.
 */
function parseRoadmap(content) {
    const lines = content.split('\n');
    const tasks = [];
    let taskIdCounter = 1; // Simple counter for stable sort if no explicit IDs parsed later
    
    // Maps roadmap status emojis to internal status strings and RWS sorting priority groups.
    // Ref: @task-tracking-protocol.mdc Status Lifecycle
    const statusMap = {
        '⏸️': { status: 'paused', priorityGroup: 1 },          // Highest priority for action (Resume)
        '🧪': { status: 'testing', priorityGroup: 2 },         // Needs active testing/review
        '🔄': { status: 'inprogress', priorityGroup: 3 },      // Currently being worked on
        '⏳': { status: 'pending', priorityGroup: 4 },         // Ready to be started
        '❌': { status: 'blocked', priorityGroup: 5 },         // Blocked (by error/dependency)
        '🔬': { status: 'pendingtesting', priorityGroup: 99 }, // Needs user to initiate testing (ST command)
        '✅': { status: 'completed', priorityGroup: 100 },     // Done - lowest sort priority
        '🔧': { status: 'rework', priorityGroup: 3 },         // Treat rework similar to inprogress for sorting
    };
    
    // Tracks the current hierarchy based on Markdown headings (## Task, ### Subtask, etc.)
    // Used to generate `src:+` tags.
    let currentParentHierarchy = [];

    // Regex for parsing the Task ID, e.g., "[ UI-3.2.1 ]" (with required spaces, up to 3 levels)
    // Ref: @roadmap-syntax-validation-protocol.mdc v1.2
    const taskIdRegex = /\[\s+([A-Z0-9]{3,6}-\d+(?:\.\d+){0,2})\s+\]/;
    
    // Regex for parsing explicit rank tags, e.g., " • [ rnk:A ]"
    // Allows optional whitespace. Captures the single uppercase letter rank.
    // Ref: @roadmap-syntax-validation-protocol.mdc
    const rankTagRegex = /\s*•\s*\[\s*rnk:([A-Z])\s*\]/g;
    
    // Regex for parsing optional due date tags, e.g., " • [ due:04.22.25 ]"
    // Allows optional whitespace. Captures the MM.DD.YY date.
    // Ref: @roadmap-syntax-validation-protocol.mdc
    const dueDateRegex = /\s*•\s*\[\s*due:(\d{2}\.\d{2}\.\d{2})\s*\]/g;

    // Regex for parsing optional done date tags, e.g., " • [ done:07.27.24 ]"
    // Allows optional whitespace. Captures the MM.DD.YY date.
    // Ref: @roadmap-syntax-validation-protocol.mdc
    const doneDateRegex = /\s*•\s*\[\s*done:(\d{2}\.\d{2}\.\d{2})\s*\]/g;
    
    // Flag to prevent parsing list items within the introductory "Status Definitions" section.
    let skippingDefinitionSection = false;
    let currentLineNumber = 0;

    // Process each line of the roadmap file
    for (const line of lines) {
        currentLineNumber++;
        const trimmedLine = line.trim();
        const indentLevel = line.search(/\S|$/); // Simple indentation check

        if (!trimmedLine) continue; // Skip empty lines

        // --- Heading Parsing & Hierarchy Tracking ---
        const headingMatch = trimmedLine.match(/^(#+)\s*(.*)/);
        if (headingMatch) {
            const level = headingMatch[1].length; 
            const headingText = headingMatch[2].trim();

            // Check if this heading marks the start of the status definitions block
            // Updated to check for the specific standard section title
            if (level === 3 && headingText.startsWith('Status Emojis (MUST)')) { // Assuming section title is specific
                skippingDefinitionSection = true; 
                // console.log(`Line ${currentLineNumber}: Detected Status Definitions, skipping tasks.`);
            } else if (level <= 2 && skippingDefinitionSection) { // Main task heading resets skip
                // console.log(`Line ${currentLineNumber}: Detected heading "${headingText}", resuming task parsing.`);
                skippingDefinitionSection = false; 
            }

            // Update hierarchy stack if not skipping and it's a relevant heading level (## or deeper)
            if (!skippingDefinitionSection && level >= 2) {
                currentParentHierarchy = currentParentHierarchy.slice(0, level - 2);
                // Sanitize heading: remove prefixes, replace spaces for src:+ tag
                const sanitizedHeading = headingText.replace(/^(Phase|Task|Subtask)\s*\d+(\.\d+)*:?\s*/, '').replace(/[\s&]+/g, '');
                if (sanitizedHeading && !headingText.startsWith('Status Emojis (MUST)')) { 
                    currentParentHierarchy.push(sanitizedHeading);
                }
            }
            continue; // Done processing heading line
        }

        // --- Skip non-task lines within the definition section --- 
        if (skippingDefinitionSection) {
            continue;
        }

        // --- Task Line Parsing Logic ---
        // Check if the line looks like a task item (starts with "- ")
        if (trimmedLine.startsWith('- ')) {
            const potentialTaskPart = trimmedLine.substring(2).trim();
            const firstSpaceIndex = potentialTaskPart.indexOf(' ');
            if (firstSpaceIndex === -1) continue; // Malformed task line, skip

            // Extract potential status emoji
            const emoji = potentialTaskPart.substring(0, firstSpaceIndex);
            let remainingLine = potentialTaskPart.substring(firstSpaceIndex).trim();

            // If it's a valid status emoji we recognize...
            if (statusMap[emoji]) {
                const statusInfo = statusMap[emoji];
                let taskId = null;
                let timestampStr = null;
                let explicitRank = null;
                let dueDate = null;
                let doneDate = null;
                const internalSortId = taskIdCounter++; // Use counter for stable sort

                // --- Parse Task ID [PREFIX-Num[.SubNum[.SubSubNum]]] --- 
                const idMatch = taskIdRegex.exec(remainingLine);
                if (idMatch) {
                    taskId = idMatch[1]; // Captured ID
                    // Remove the matched ID (including brackets and spaces) from the line
                    remainingLine = remainingLine.replace(taskIdRegex, '').trim();
                } else {
                    // If ID is missing or malformed, it's a violation of the new standard
                    console.warn(`[WARN] Line ${currentLineNumber}: Missing or malformed Task ID (must match '[ PREFIX-Num[.SubNum[.SubSubNum]] ]' with spaces) for line: "${trimmedLine}"`);
                    continue; // Skip this task entirely
                }

                // --- Validate ID (max depth 3, integer segments, dot notation) ---
                const idParts = taskId.split('-');
                if (idParts.length !== 2) {
                    console.warn(`[WARN] Line ${currentLineNumber}: Task ID prefix/number split invalid: ${taskId}. Line: "${trimmedLine}"`);
                    continue;
                }
                const prefix = idParts[0];
                const numSegments = idParts[1].split('.');
                if (numSegments.length > 3) {
                    console.warn(`[WARN] Line ${currentLineNumber}: Task ID too deep (max 3 levels): ${taskId}. Line: "${trimmedLine}"`);
                    continue;
                }
                if (!numSegments.every(seg => /^\d+$/.test(seg))) {
                    console.warn(`[WARN] Line ${currentLineNumber}: Task ID contains non-integer segment: ${taskId}. Line: "${trimmedLine}"`);
                    continue;
                }

                // --- Debug log for successful parse ---
                console.log(`[INFO] Parsed task: ${taskId} - ${remainingLine}`);

                let descriptionCleaned = remainingLine; // Start with line after emoji and ID

                // --- Parse Optional Tags --- 
                // Rank Tag
                const rankMatches = [...descriptionCleaned.matchAll(rankTagRegex)];
                if (rankMatches.length > 0) {
                    explicitRank = rankMatches[0][1]; 
                    if (rankMatches.length > 1) {
                        console.warn(`[WARN] Line ${currentLineNumber}: Multiple rank tags found. Using first: ${explicitRank}. Line: "${trimmedLine}"`);
                    }
                    descriptionCleaned = descriptionCleaned.replace(rankTagRegex, '').trim();
                }

                // Due Date Tag
                const dueDateMatches = [...descriptionCleaned.matchAll(dueDateRegex)];
                if (dueDateMatches.length > 0) {
                    dueDate = dueDateMatches[0][1]; // MM.DD.YY format
                    if (dueDateMatches.length > 1) {
                        console.warn(`[WARN] Line ${currentLineNumber}: Multiple due date tags found. Using first: ${dueDate}. Line: "${trimmedLine}"`);
                    }
                     // Basic date format validation (MM.DD.YY)
                    if (!/^\d{2}\.\d{2}\.\d{2}$/.test(dueDate)) {
                        console.warn(`[WARN] Line ${currentLineNumber}: Malformed due date format: ${dueDate}. Expected MM.DD.YY. Line: "${trimmedLine}"`);
                        dueDate = null; // Discard malformed date
                    }
                    descriptionCleaned = descriptionCleaned.replace(dueDateRegex, '').trim();
                }

                // Done Date Tag
                const doneDateMatches = [...descriptionCleaned.matchAll(doneDateRegex)];
                if (doneDateMatches.length > 0) {
                    doneDate = doneDateMatches[0][1]; // MM.DD.YY format
                    if (doneDateMatches.length > 1) {
                        console.warn(`[WARN] Line ${currentLineNumber}: Multiple done date tags found. Using first: ${doneDate}. Line: "${trimmedLine}"`);
                    }
                    // Basic date format validation (MM.DD.YY)
                    if (!/^\d{2}\.\d{2}\.\d{2}$/.test(doneDate)) {
                        console.warn(`[WARN] Line ${currentLineNumber}: Malformed done date format: ${doneDate}. Expected MM.DD.YY. Line: "${trimmedLine}"`);
                        doneDate = null; // Discard malformed date
                    } else if (statusInfo.status !== 'completed') {
                        // Validation: Done date should only exist for completed tasks
                        console.warn(`[WARN] Line ${currentLineNumber}: Found done date tag [done:${doneDate}] on a non-completed task (Status: ${emoji}). Line: "${trimmedLine}"`);
                        // Keep the date extracted but warn user
                    }
                    descriptionCleaned = descriptionCleaned.replace(doneDateRegex, '').trim();
                } else if (statusInfo.status === 'completed') {
                     // Validation: Completed tasks MUST have a done date tag
                     console.warn(`[WARN] Line ${currentLineNumber}: Completed task (Status: ${emoji}) is missing the required '• [done:MM.DD.YY]' tag. Line: "${trimmedLine}"`);
                }

                // --- Parse Timestamp (Optional) --- 
                // Attempt to parse timestamp from the end of the *remaining* description
                // Handles formats like "✅ MM.DD.YY | HH:MM AM/PM TimeZone" or "⏸️ [Paused: SWS - Timestamp]"
                let descriptionFinal = descriptionCleaned;
                const completionTimestampMatch = descriptionCleaned.match(/(\d{2}\.\d{2}\.\d{2}\s*\|\s*\d{1,2}:\d{2}\s*(?:AM|PM)\s*[A-Z]{3,}\s*$)/);
                const pausedTimestampMatch = descriptionCleaned.match(/(\[Paused:\s*SWS\s*-\s*\d{2}\.\d{2}\.\d{2}\s*\|\s*\d{1,2}:\d{2}\s*(?:AM|PM)\s*[A-Z]{3,}\]\s*$)/);
                
                if (completionTimestampMatch && statusInfo.status === 'completed') {
                    timestampStr = completionTimestampMatch[1].trim();
                    descriptionFinal = descriptionCleaned.substring(0, completionTimestampMatch.index).trim();
                } else if (pausedTimestampMatch && statusInfo.status === 'paused') {
                    timestampStr = pausedTimestampMatch[1].trim(); // Keep the full bracketed string
                    descriptionFinal = descriptionCleaned.substring(0, pausedTimestampMatch.index).trim();
                }

                // Fallback for older timestamp formats if needed (less likely with new standard)
                // const fallbackTimestampMatch = descriptionFinal.match(/((?:\d{2}\.\d{2}\.\d{2}|\d{8})_\|_\d{1,2}:\d{2}_(?:AM|PM)_[A-Z]{3,})\s*$/);
                // if (!timestampStr && fallbackTimestampMatch) {
                //     timestampStr = fallbackTimestampMatch[1].trim();
                //     descriptionFinal = descriptionFinal.substring(0, fallbackTimestampMatch.index).trim();
                // }

                // Create task object
                tasks.push({
                    id: taskId, // The parsed [PREFIX-Num[.SubNum[.SubSubNum]]]
                    internalSortId: internalSortId, // For stable sort
                    description: descriptionFinal.trim(), // Cleaned description
                    status: statusInfo.status,
                    priorityGroup: statusInfo.priorityGroup,
                    rank: explicitRank, // Explicit rank (A, B, C...) or null
                    dueDate: dueDate,     // Due date (MM.DD.YY) or null
                    doneDate: doneDate,   // Done date (MM.DD.YY) or null
                    timestamp: timestampStr, // Original timestamp string or null
                    depth: currentParentHierarchy.length, // For RWS depth sorting
                    sourceTag: currentParentHierarchy.join('_'), // Build src:+ tag
                    originalLine: trimmedLine, // Keep for reference
                });
            }
        } else if (trimmedLine.startsWith('-')) {
            // If the line starts with a dash but does not match the strict pattern, warn for protocol non-compliance
            console.warn(`[WARN] Line does not match RSVP v1.2 task syntax: ${trimmedLine}`);
        }
    }
    // After parsing all lines, protocol-driven debug log for total tasks
    console.log(`[INFO] Total tasks parsed: ${tasks.length}`);
    return tasks;
}


// --- Sorting Logic ---
/**
 * Sorts an array of task objects based on the selected mode (CURRENT_SORT_MODE).
 * 
 * @function sortTasks
 * @param {Array<object>} tasks - The array of parsed task objects.
 * @param {string} mode - The sorting mode (e.g., SORT_MODES.RWS).
 * @returns {Array<object>} The sorted array of task objects.
 */
function sortTasks(tasks, mode) {
    console.log(`Sorting tasks using mode: ${mode}`);

    // Separate completed tasks to append them at the very end.
    const incompleteTasks = tasks.filter(t => t.status !== 'completed');
    const completedTasks = tasks.filter(t => t.status === 'completed');

    /**
     * Helper function for stable ID sorting (used as the final tie-breaker).
     * Ensures tasks with identical sort criteria maintain their relative order from the roadmap.
     * @param {object} a - First task object.
     * @param {object} b - Second task object.
     * @returns {number} Sort order (-1, 0, 1).
     */
    const sortByOriginalId = (a, b) => {
        const numA = parseInt(a.id.match(/\d+$/)?.[0] || '0'); // Extract trailing number from generated ID
        const numB = parseInt(b.id.match(/\d+$/)?.[0] || '0');
        if (numA !== numB) {
            return numA - numB; // Sort by number
        }
        return a.id.localeCompare(b.id); // Fallback to full string compare
    };

    /**
     * Helper function implementing the core RWS (Resume Work Session) sorting logic.
     * Used directly for RWS mode and as a sub-sort for SOURCE mode.
     * Priority: Explicit Rank > Status Group > Depth (inprogress only) > Original ID.
     * @param {object} a - First task object.
     * @param {object} b - Second task object.
     * @returns {number} Sort order (-1, 0, 1).
     */
    const sortByRWS = (a, b) => {
        const hasRankA = !!a.rank;
        const hasRankB = !!b.rank;

        // 1. Ranked tasks come before unranked tasks.
        if (hasRankA && !hasRankB) return -1;
        if (!hasRankA && hasRankB) return 1;

        // 2. If both are ranked, sort alphabetically by rank (A > B).
        if (hasRankA && hasRankB) {
            if (a.rank !== b.rank) {
                return a.rank.localeCompare(b.rank);
            }
        }

        // 3. If ranks are same or both unranked, sort by status priority group.
        //    (Lower group number = higher priority, e.g., Paused(1) > Testing(2) > InProgress(3) ...)
        if (a.priorityGroup !== b.priorityGroup) {
            return a.priorityGroup - b.priorityGroup;
        }

        // 4. Within the In Progress group (3), sort by depth (deeper = higher priority).
        if (a.priorityGroup === 3 && b.priorityGroup === 3) {
            return b.depth - a.depth; 
        }

        // 5. Final tie-breaker: maintain original relative order using generated ID.
        return sortByOriginalId(a, b);
    };


    // Apply the selected sorting algorithm
    switch (mode) {
        case SORT_MODES.ALPHA:
            // Sort alphabetically by description, using ID as tie-breaker.
            incompleteTasks.sort((a, b) => {
                const descCompare = a.description.localeCompare(b.description);
                return (descCompare !== 0) ? descCompare : sortByOriginalId(a, b);
            });
            break;

        case SORT_MODES.STATUS:
            // Sort strictly by status priority group, using ID as tie-breaker.
            incompleteTasks.sort((a, b) => {
                return (a.priorityGroup !== b.priorityGroup) ? a.priorityGroup - b.priorityGroup : sortByOriginalId(a, b);
            });
            break;

        case SORT_MODES.SOURCE:
            // Sort primarily by source tag (alphabetical), then by RWS logic within each source group.
            incompleteTasks.sort((a, b) => {
                const sourceA = a.sourceTag || ''; // Treat missing tags as empty string
                const sourceB = b.sourceTag || '';
                const sourceCompare = sourceA.localeCompare(sourceB);
                return (sourceCompare !== 0) ? sourceCompare : sortByRWS(a, b); // Use RWS for secondary sort
            });
            break;

        case SORT_MODES.RWS:
        default: // Default to RWS mode
            // Sort using the primary RWS logic.
            incompleteTasks.sort(sortByRWS);
            break;
    }

    // Append completed tasks (which are not sorted further among themselves) to the end.
    return [...incompleteTasks, ...completedTasks];
}

// --- Formatting Logic ---
/**
 * Converts MM.DD.YY to YYYY-MM-DD format.
 * Assumes 21st century for YY.
 * Returns null if input is invalid.
 * @param {string | null} mmddyy - Date string in MM.DD.YY format.
 * @returns {string | null} Date string in YYYY-MM-DD format or null.
 */
function convertMMDDYYtoYYYYMMDD(mmddyy) {
    if (!mmddyy) return null;
    const parts = mmddyy.match(/^(\d{2})\.(\d{2})\.(\d{2})$/);
    if (!parts) return null; 
    const year = parseInt(parts[3], 10);
    // Basic assumption for 2-digit year (adjust range if needed)
    const fullYear = year >= 0 && year <= 50 ? 2000 + year : 1900 + year; 
    return `${fullYear}-${parts[1]}-${parts[2]}`;
}

/**
 * Formats a single task object into a todo.txt compatible line.
 * 
 * @function formatTaskLine
 * @param {object} task - The parsed task object.
 * @param {string | null} priorityChar - The assigned priority character (A-Z) or null.
 * @returns {string} The formatted todo.txt line.
 * @protocol @task-system-documentation-protocol.mdc Section 7 (Output Format)
 */
function formatTaskLine(task, priorityChar) {
    let line = '';
    
    // 1. Completion Marker or Priority
    if (task.status === 'completed') {
        line += 'x '; // Completion marker
    } else if (priorityChar) {
        line += `(${priorityChar}) `; // Priority (A), (B), etc.
    }

    // 2. Status Context Tag
        line += `@${task.status} `;

    // 3. Task Description
    line += task.description;

    // 4. Metadata Tags (separated by " - ")
    const metadata = [];

    // 4a. Source Tag (src:+)
    if (task.sourceTag) {
        metadata.push(`src:+${task.sourceTag}`);
    }

    // 4b. Due Date Tag (due:YYYY-MM-DD)
    const dueDateYYYYMMDD = convertMMDDYYtoYYYYMMDD(task.dueDate);
    if (dueDateYYYYMMDD) {
        metadata.push(`due:${dueDateYYYYMMDD}`);
    }

    // 4c. Done Date Tag (done:YYYY-MM-DD) - Only for completed tasks
    // Use the protocol-specified MM.DD.YY format directly for the tag value
    if (task.status === 'completed' && task.doneDate) {
        // Convert MM.DD.YY to YYYY-MM-DD for the tag value
        const doneDateYYYYMMDD = convertMMDDYYtoYYYYMMDD(task.doneDate);
        if (doneDateYYYYMMDD) { // Only add if conversion is successful
             metadata.push(`done:${doneDateYYYYMMDD}`);
        } else {
            console.warn(`[WARN] Could not convert done date ${task.doneDate} to YYYY-MM-DD for task: ${task.description}`);
        }
    }

    // 4d. Timestamp Tag (ts:)
    if (task.timestamp) {
        // Replace problematic characters for todo.txt compatibility if needed
        // Keep original format from roadmap for this tag as per protocol
        metadata.push(`ts:${task.timestamp.replace(/\s*\|\s*/g, '_')}`); 
    }
    
    // Join metadata with the required separator
    if (metadata.length > 0) {
        line += ` - ${metadata.join(' - ')}`;
    }

    return line;
}

// --- Main Execution ---
/**
 * Main asynchronous function orchestrating the generation of the todo.txt file.
 * Reads the roadmap, parses tasks, sorts them, formats them, and writes the output file.
 * Includes generation control based on `LIST_GENERATION`.
 * 
 * @async
 * @function generateTodoTxt
 */
async function generateTodoTxt() {
    // --- Check Generation Control --- 
    // Prevents accidental overwrites if the script is run automatically (e.g., via hooks)
    // when the user intends only manual generation.
    if (LIST_GENERATION === GENERATE_LIST.MANUAL) {
        // Simple check: Assumes automated runners don't add extra command-line arguments.
        // If run via `node generate-todotxt.js` directly, process.argv.length will be > 2.
        // Allows manual runs while blocking most automated triggers if MANUAL mode is set.
        if (process.argv.length <= 2) { 
             console.log(`[INFO] LIST_GENERATION mode is MANUAL. Skipping automatic generation.`);
             return; // Exit silently for unintended automatic runs
        }
        console.log("[INFO] LIST_GENERATION mode is MANUAL, but script appears to be run directly. Proceeding...");
    }
    
    console.log(`[INFO] Starting todotxt generation (Trigger Mode: ${LIST_GENERATION}, Sort Mode: ${CURRENT_SORT_MODE})...`);

    try {
        // Step 1: Read the source roadmap file.
        console.log(`[INFO] Reading roadmap: ${ROADMAP_PATH}`);
        const roadmapContent = await fs.readFile(ROADMAP_PATH, 'utf-8');

        // Step 2: Parse the Markdown content into task objects.
        console.log('[INFO] Parsing roadmap...');
        const parsedTasks = parseRoadmap(roadmapContent);

        // Step 3: Sort the tasks based on the selected mode.
        console.log('[INFO] Sorting tasks...');
        const sortedTasks = sortTasks(parsedTasks, CURRENT_SORT_MODE);

        // Step 4: Format tasks into todo.txt lines, assigning priorities for RWS mode.
        console.log('[INFO] Formatting todo.txt lines...');
        const todoLines = [];
        
        // --- Determine Starting Point for Sequential Priorities (RWS Mode) ---
        // This ensures sequential priorities (D, E, F...) start *after* any explicit ranks (A, B, C...).
        let maxExplicitRankCode = 0; // ASCII code of highest explicit rank found (0 = none)
        if (CURRENT_SORT_MODE === SORT_MODES.RWS) {
            sortedTasks.forEach(task => {
                if (task.rank) {
                    const rankCode = task.rank.charCodeAt(0);
                    // Basic validation: ensure rank is A-Z
                    if (rankCode >= 'A'.charCodeAt(0) && rankCode <= 'Z'.charCodeAt(0)) {
                         if (rankCode > maxExplicitRankCode) {
                            maxExplicitRankCode = rankCode;
                        }
                    }
                }
            });
        }
        // Calculate the starting character code for the *next* available priority slot.
        let nextSequentialCharCode = (maxExplicitRankCode >= 'A'.charCodeAt(0)) ? maxExplicitRankCode + 1 : 'A'.charCodeAt(0);
        // --- End Determination ---


        // --- Main Formatting Loop ---
        for (const task of sortedTasks) {
            let priorityChar = null; // Will hold (A), (B), etc. if assigned

            // Assign priorities only if sorting in RWS mode.
            if (CURRENT_SORT_MODE === SORT_MODES.RWS) {
                // Use explicit rank if present.
                if (task.rank) {
                    priorityChar = task.rank;
                // Otherwise, assign next sequential priority if task is unranked and not completed.
                } else if (!task.rank && task.status !== 'completed') {
                    // Assign if we haven't exhausted A-Z.
                    if (nextSequentialCharCode <= 'Z'.charCodeAt(0)) {
                        priorityChar = String.fromCharCode(nextSequentialCharCode);
                        nextSequentialCharCode++; // Increment for the next unranked task
                    }
                }
            }
            
            // Format the task object into a todo.txt line.
            const formattedLine = formatTaskLine(task, priorityChar);
            if (formattedLine) {
                todoLines.push(formattedLine);
            }
        }
        // --- End Formatting Loop ---

        // Protocol-driven debug log for total todo.txt lines
        console.log(`[INFO] Total todo.txt lines to write: ${todoLines.length}`);

        // Step 5: Write the formatted lines to the output file.
        console.log(`[INFO] Writing ${todoLines.length} lines to ${TODOTXT_PATH}`);
        // Use OS-specific end-of-line character for compatibility.
        await fs.writeFile(TODOTXT_PATH, todoLines.join(os.EOL));

        console.log('[SUCCESS] md-docs/todotasks.txt generated successfully.');

    } catch (error) {
        // Log critical errors encountered during the process.
        console.error('[ERROR] Error generating todo.txt:', error);
        process.exit(1); // Exit with a non-zero code to indicate failure
    }
}

// --- Script Entry Point --- 
// Execute the main generation function when the script is run.
generateTodoTxt(); 