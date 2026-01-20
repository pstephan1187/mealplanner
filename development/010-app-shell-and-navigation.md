# Feature 1 - App Shell and Navigation

## Goal
Provide a consistent, mobile-first shell that matches the current app look and supports Recipes, Meal Plans, and Shopping Lists.

## Scope
- Primary navigation entries: Dashboard, Recipes, Meal Plans, Shopping Lists, Settings.
- Use existing layouts and components: `AppLayout`, `AppHeader`, `AppSidebar`, `AppContent`.
- Add empty-state call-to-actions on the dashboard for first-run guidance.

## UI/UX Notes
- Mobile and tablet are primary; ensure headers and actions remain reachable with one hand.
- Collapse sidebar into a drawer or bottom navigation on smaller breakpoints, matching existing behavior.
- Keep primary actions visible: use sticky footer actions on mobile when needed.
- Provide loading feedback at page level for longer data fetches.

## Deliverables
- Navigation entries wired to routes for each feature.
- Dashboard landing page with summary cards and empty-state CTA buttons.
- Consistent breadcrumb usage where it exists in the app.
