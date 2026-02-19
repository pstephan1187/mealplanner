# Work on a Notion Task

Pick a task from the Meal Planner Tasks database in Notion, plan it, execute it, and mark it complete after review.

## Notion Context

- **Tasks database URL:** `https://www.notion.so/30c76197fafa80139be8cb7af9fa7812`
- **Data source ID:** `30c76197-fafa-8190-a650-000bf63982aa`
- **Database properties:**
  - `Project name` (title)
  - `Status` — "Not started", "In progress", "Done", "Archived"
  - `Priority` — "Urgent", "High", "Medium", "Low"
  - `Module` — "Recipes", "Ingredients", "Meal Plans", "Shopping Lists", "Grocery Stores", "UI/UX", "Other"
  - `Type` — "Bugfix", "Improvement", "Feature"
  - `Effort Level` — "High", "Medium", "Low"
  - `Blocked by` / `Blocking` — relations to other tasks

## Instructions

### Phase 1: Task Selection

1. Use `mcp__plugin_Notion_notion__notion-search` to search the Tasks database for tasks with status "Not started". Query: `"Meal Planner"` and then fetch the database to see available tasks. **Do NOT use web search or WebFetch for Notion content — always use the Notion MCP tools.**
2. Alternatively, use `mcp__plugin_Notion_notion__notion-fetch` on the database URL to get the full list of tasks.
3. Present the user with a curated list of available tasks, organized by priority:
   - Show **Urgent** and **High** priority tasks first as recommendations
   - Show the task name, priority, module, type, and effort level
   - Note any blocked tasks (they cannot be started until blockers are resolved)
4. Ask the user which task they want to work on. They may pick from the list or specify any task by name.
5. If the argument `$ARGUMENTS` is provided, search for a task matching that text and skip the selection prompt.

### Phase 2: Task Setup

6. Fetch the full task page using `mcp__plugin_Notion_notion__notion-fetch` with the selected task's page ID. Read all content including the **Finding Details** and **AI Agent Prompt** sections.
7. Update the task status to "In progress" using `mcp__plugin_Notion_notion__notion-update-page`:
   ```json
   {
     "page_id": "<task-page-id>",
     "command": "update_properties",
     "properties": { "Status": "In progress" }
   }
   ```
8. Summarize the task to the user: what the issue is, which files are involved, and what the fix entails.

### Phase 3: Planning

9. Use the **AI Agent Prompt** from the Notion page as the primary context for understanding the fix.
10. Activate any relevant skills based on the task domain:
    - PHP/Laravel work → `superpowers:test-driven-development` as appropriate
    - Vue/frontend work → `frontend-design:frontend-design` as appropriate
    - General changes → relevant skills from available skill list
11. Enter plan mode (`EnterPlanMode`) to design the implementation approach:
    - Read all relevant files mentioned in the task
    - Understand the current code before proposing changes
    - Consider test requirements — every change needs a test
    - Present the plan for user approval
12. Ask clarifying questions if the task is ambiguous or has multiple valid approaches. Make recommendations with trade-offs.

### Phase 4: Execution

13. After the plan is approved, execute it using appropriate tools:
    - For tasks with independent sub-steps, use `superpowers:dispatching-parallel-agents` or `superpowers:subagent-driven-development`
    - Follow `superpowers:test-driven-development` for implementation
    - Use `superpowers:systematic-debugging` if issues arise
14. Follow the project checklist:
    - Tests pass (`php artisan test --compact`)
    - PHP formatting clean (`vendor/bin/pint --dirty`)
    - Frontend formatting clean (`npm run format` and `npm run lint`)
15. Use `superpowers:verification-before-completion` before claiming the work is done.

### Phase 5: Review

16. Present completed work to the user:
    - Summarize what was changed and why
    - Show test results
    - Highlight any decisions or trade-offs made
17. Ask: **"Ready for your review. Any feedback or changes needed?"**
18. If the user provides feedback:
    - Apply the requested changes
    - Re-run verification (tests, linters)
    - Present the updates
    - Ask again for review
    - Repeat this loop until the user is satisfied
19. If the user says it's done/approved/looks good:
    - Move to Phase 6

### Phase 6: Completion

20. Update the task status to "Done" using `mcp__plugin_Notion_notion__notion-update-page`:
    ```json
    {
      "page_id": "<task-page-id>",
      "command": "update_properties",
      "properties": { "Status": "Done" }
    }
    ```
21. Confirm to the user that the task is marked complete in Notion.
22. Optionally suggest the next highest-priority task available.
