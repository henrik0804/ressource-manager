---
name: laravel-actions
description: Document how Actions are structured and invoked in this codebase
license: MIT
compatibility: opencode
metadata:
    audience: contributors
    workflow: laravel
---

## What I do

- Explain the role of `app/Actions` classes in request flows
- Show how Actions are called from controllers and other Actions
- Outline conventions for naming, signatures, return types, and error handling

## When to use me

Use this when you need to add or refactor an Action, or when reviewing controller logic for extraction into Actions. Ask clarifying questions if the caller context (controller, webhook, job, or CLI) is unclear.

## How Actions work here

- Location: Actions live in `app/Actions` with one class per file.
- Naming: Use verb-first names like `StoreCustomerAction`, `StartReleaseRevisionAction`, `SendReleaseNotificationAction`.
- Signatures: `handle(...)` is the standard entry point.
- Scope: Actions encapsulate a single business operation and are called by controllers, jobs, or other Actions.
- Dependencies: Simple Actions are often `final readonly`; service dependencies can be injected via `__construct` (see `app/Actions/PullReleaseChangelogAction.php`, `app/Actions/StoreMonthPlanAction.php`).
- Return values: Prefer returning a model or a simple boolean; throw exceptions for invalid state when needed.

## Example patterns

- Controller calling Action via dependency injection:

    ```php
    public function store(StoreReleaseRequest $request, StoreReleaseAction $action): RedirectResponse
    {
        $release = $action->handle($request->validated());
        // controller handles flash messaging and redirects
    }
    ```

- Action with validation and state changes:
    ```php
    final readonly class SendReleaseNotificationAction
    {
        public function handle(Release $release, ?User $user, bool $isReminder = false): void
        {
            // validate state, throw InvalidArgumentException for errors
            // perform side effects (notify, create history row)
            // update ClickUp status on initial notification
        }
    }
    ```

## Conventions to follow

- Keep controllers thin: validation, orchestration, and flash messages remain in controllers.
- Keep Actions focused: one business concern per Action.
- Use `DB::transaction(...)` for interdependent updates that must succeed together.
- Actions can leverage other Actions to compose workflows.
- Prefer explicit parameters over reading global state, unless conventionally accepted (e.g., `auth()->user()`).
- Use exceptions for invalid state, and let the controller map them to user-facing errors.
