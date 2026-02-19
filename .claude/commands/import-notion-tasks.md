# Import Findings to Notion

Parse a document containing findings, tasks, feedback, or review items and create entries in the Meal Planner Tasks database in Notion.

## Arguments

`$ARGUMENTS` — Path to the document to import (e.g., `RECOMMENDATIONS.md`, `review-notes.md`). Required.

## Notion Context

- **Data source ID:** `30c76197-fafa-8190-a650-000bf63982aa`
- **Database properties:**
  - `Project name` (title)
  - `Status` — default "Not started"
  - `Priority` — "Urgent", "High", "Medium", "Low"
  - `Module` — "Recipes", "Ingredients", "Meal Plans", "Shopping Lists", "Grocery Stores", "UI/UX", "Other"
  - `Type` — "Bugfix", "Improvement", "Feature"
  - `Effort Level` — "High", "Medium", "Low"
  - `Blocked by` / `Blocking` — relations to other tasks

## Instructions

### Phase 1: Parse the Document

1. If `$ARGUMENTS` is empty, ask the user for the file path.
2. Read the file at the given path.
3. Extract every discrete finding, task, feedback item, or action item from the document. Look for:
   - Severity-tagged findings (`[CRITICAL]`, `[HIGH]`, `[MEDIUM]`, `[LOW]`)
   - Numbered or bulleted action items
   - Sections with "Issue" / "Fix" / "Recommendation" patterns
   - Table rows representing individual items
   - Any other structure that represents a distinct unit of work
4. For each item, extract:
   - **Title** — concise name for the task
   - **Severity/Priority** — map from document severity to Notion priority:
     - CRITICAL → Urgent
     - HIGH → High
     - MEDIUM → Medium
     - LOW → Low
     - If no severity, infer from context or default to Medium
   - **Module** — infer from file paths or domain keywords:
     - Recipe routes/controllers/components/views → Recipes
     - Ingredient routes/controllers/components → Ingredients
     - Meal plan/planning routes/controllers → Meal Plans
     - Shopping list routes/controllers/components → Shopping Lists
     - Grocery store/store section routes → Grocery Stores
     - Layout, theming, sidebar, general UI → UI/UX
     - Everything else → Other
   - **Type** — infer from the nature of the item:
     - Security issues, bugs, errors, crashes → Bugfix
     - Performance, refactoring, code quality, docs → Improvement
     - New functionality → Feature
   - **Effort Level** — estimate from complexity:
     - Single-line or config-only changes → Low
     - Multi-file changes, moderate logic → Medium
     - Architectural changes, new systems, complex logic → High
   - **File path(s)** — any files mentioned
   - **Description** — the full issue details
   - **Fix recommendation** — any suggested fix from the document

### Phase 2: Preview and Confirm

5. Present a summary table to the user showing all extracted items:
   ```
   | # | Title | Priority | Module | Type | Effort |
   |---|-------|----------|--------|------|--------|
   ```
6. Show the total count and ask for confirmation: **"Found N items to import. Proceed, or would you like to adjust any before creating?"**
7. The user may:
   - Approve all → proceed to Phase 3
   - Remove specific items by number
   - Change properties on specific items
   - Ask to re-categorize

### Phase 3: Create Notion Entries

8. For each item, create a Notion page using `mcp__plugin_Notion_notion__notion-create-pages` with parent `{"data_source_id": "30c76197-fafa-8190-a650-000bf63982aa"}`.
9. Batch pages into groups of 7-10 per API call for efficiency (max 100 per call).
10. Each page should have this content structure (Notion-flavored markdown):

```
## Finding Details

**Severity:** [severity from document]
**File:** `[file path(s)]`
**Issue:** [description of the problem]
**Impact:** [what could go wrong]
**Fix:** [recommendation]

---

## AI Agent Prompt

[Generate a detailed, self-contained prompt that gives an AI coding agent everything needed to fix this issue. Include:
- Exact file paths to read and modify
- What to look for in the code
- Step-by-step instructions for the fix
- What verification commands to run after (e.g., `php artisan test --compact --filter=X`, `vendor/bin/pint --dirty`, `npm run lint`)
- Any related files or patterns to be aware of]
```

11. Set all properties: `Project name`, `Priority`, `Module`, `Type`, `Effort Level`.

### Phase 4: Report

12. After all entries are created, report:
    - Total entries created
    - Breakdown by priority
    - Breakdown by module
    - Link to the Notion database
13. If any entries failed to create, report the failures and offer to retry.
