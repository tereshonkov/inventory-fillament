---
name: to-plan
description: Breaks a finished PRD into implementation phases and saves the result as docs/plan-{feature-name}.md. Use it ONLY when a ready PRD file (docs/prd-*.md) exists and an implementation plan has to be created from it - "make a plan from the PRD", "/to-plan <feature>". It reads the unfinished PRD, splits the work into phases (the first one is a Tracer Bullet), covers every acceptance criterion of the PRD and plans mandatory tests per phase (Playwright for the frontend, PHPUnit for logic). Do not use it to describe a feature (use the prd skill) and do not use it without a PRD.
argument-hint: "[feature name or prd file]"
---

# To plan

Turn a finished PRD into a phased implementation plan. The PRD says **what and why**; the plan says **in which order it is built**. The plan must cover the PRD completely and must not contain anything the PRD does not ask for.

## When to use

- Use: a `docs/prd-{feature}.md` exists and the user wants the implementation plan for it.
- Do not use: there is no PRD (tell the user to run the `prd` skill first and stop), or the user only wants to discuss or describe a feature.

## Input

The argument is the feature name or the PRD file name (`/to-plan bulk-write-off` → `docs/prd-bulk-write-off.md`).

Find the PRD:

1. With an argument: take `docs/prd-{argument}.md` (match the slug loosely). Not found → list the existing PRDs and ask which one is meant.
2. Without an argument: look through `docs/prd-*.md` and pick the **unfinished** ones. A PRD is unfinished when it has at least one unchecked acceptance criterion (`- [ ]`). One candidate → use it. Several → ask which one (`AskUserQuestion`). None → say that there is no unfinished PRD and stop.
3. If the chosen PRD has all criteria checked, tell the user it looks finished and ask whether to continue anyway.
4. If `docs/plan-{slug}.md` already exists, do not overwrite it silently: ask whether to replace it, update it or use another name.

## Output

One file: `docs/plan-{feature-name}.md`, where `{feature-name}` is the slug of the PRD file (`prd-bulk-write-off.md` → `plan-bulk-write-off.md`), in the same folder as the PRD.

## Steps

1. **Read the PRD carefully and completely** (goal, scenarios, scope, out of scope, constraints, acceptance criteria, open questions).
2. **Number the acceptance criteria** in the order they appear in the PRD: C1, C2, C3... (the PRD does not number them; the numbering lives in the plan and is used for traceability).
3. **Collect context** the same way as the `prd` skill: `CLAUDE.md` (stack, roles, conventions, tests, commit rules) and, where the PRD touches existing behaviour, the relevant code. Do not ask what the repository already answers.
4. **Ask clarifying questions if needed** (see below). Ask first, write after.
5. **Split the work into phases** following the rules below.
6. **Check coverage:** every criterion C1..Cn is closed by exactly one phase; every task traces to the PRD; nothing is added on top; every phase has the tests its layers require (Playwright for the frontend, PHPUnit for logic).
7. **Write the file** with the structure below.
8. **Reply** with the path, the list of phases (one line each) and the coverage result. Offer to start Phase 1, but do not start implementing: plans are executed only on the user's command.
9. **Project documentation:** add a one-line entry to the "Change log" of `CLAUDE.md` (English, newest first) and add the plan to the list of plans in its "Plans" section.

## Structure

Use these headings and this order. The headings are fixed in Russian (the project standard); the content is written in the language the user writes in.

```markdown
# Plan-{feature name}

**Дата:** {YYYY-MM-DD}
**PRD:** docs/prd-{slug}.md

## Прогресс

- [ ] Фаза 1: {name} (Tracer Bullet)
- [ ] Фаза 2: {name}
- ...

## Фаза 1: {name} (Tracer Bullet)

**Статус:** [ ] не выполнена

### Цель
{what this phase gives to the user / the product - one or two sentences}

### Затрагивает
{only the layers that are really touched: Бекенд, Фронтенд, База данных}

### Задачи
- **Задача 1.** {concrete deliverable}
- **Задача 2.** {concrete deliverable}
- ... (at most 5)

### Тесты
- PHPUnit: {what is checked} (backend logic, data rules, access by role, database changes)
- Playwright: {what is checked} (everything the user sees or clicks: pages, widgets, forms, layout)

### Готово когда
{one concrete, verifiable criterion for the whole phase; it always includes "the new tests and the whole pre-commit check pass"}

**Закрывает критерии PRD:** C1, C3

## Фаза 2: {name}
...

## Покрытие критериев PRD

| Критерий PRD | Фаза |
|---|---|
| C1: {text} | Фаза 1 |
| C2: {text} | Фаза 2 |
```

At the end of the file add one line: *When a phase is done: tick it in "Прогресс", update its "Статус" line (`[x]`, date, commit subject) and tick the criteria it closes in the PRD.*

## Rules

- **Every phase gives a concrete, visible result.** No "preparation" phases whose result nobody can see or check.
- **Phases can be stopped after:** after any phase the product works and can be released. A phase may build on earlier phases but never depends on a later one. Order the phases so that the most valuable and the most basic come first.
- **At most 5 tasks per phase.** More than 5 means the phase is too big: split it.
- **Phase 1 is a Tracer Bullet:** the thinnest working path through every layer the feature needs (data → backend → UI → a test) for the main scenario of the PRD. Ugly is fine, broken is not. It proves the whole chain works before anything is polished.
- **Cover all acceptance criteria of the PRD.** Every criterion belongs to exactly one phase ("Закрывает критерии PRD") and appears in the coverage table. A criterion that cannot be placed means the PRD or the split is wrong: ask.
- **Add nothing that is not in the PRD.** No extra features, refactorings or "nice to have" tasks. A task is allowed only if it is needed for a scope item, a scenario or a criterion of the PRD (for example a migration for a scope item). Respect "Не в скоупе" and the technical constraints of the PRD.
- **Tasks are deliverables, not code.** Name what is built (a screen, a rule, a data change, a check), in the terms of the project. No code snippets and no step-by-step instructions.
- **"Готово когда" is verifiable** with concrete values, roles and conditions, and is proven by the phase's tests (PHPUnit / Playwright). Forbidden: "works", "is done", "is convenient".
- **Затрагивает lists only the layers that are really touched.** Do not list a layer "just in case".
- **Testing after every phase is mandatory** (the way this project always worked). Every phase has a "Тесты" block:
  - the phase touches the **frontend** (page, widget, form, layout, anything the user sees or clicks) → a **Playwright** test is required;
  - the phase touches **logic** (backend rules, calculations, access by role, queries, database changes) → a **PHPUnit** test is required;
  - both are touched → both are required. A phase with nothing to test has no reason to exist: merge it into another one.
  - The tests belong to the phase and are **not** counted in the 5-task limit. The Tracer Bullet phase has at least one PHPUnit and one Playwright test for the main scenario of the PRD (when the feature has both logic and UI).
  - New tests must prove the new behaviour: they have to fail without the phase's change. A smoke test that passes anyway is not enough.
  - A phase is done only when its new tests **and the whole pre-commit check pass** (Pint on the changed files, ESLint, PHPUnit, frontend build, Playwright), and the phase is committed as one increment. Never run tests against a real database (see the safety notes in `CLAUDE.md`).
- **One phase = one committable, tested increment** (the project convention: one step = one commit that passes the pre-commit checks).
- **Do not invent facts** (numbers, thresholds, behaviour). If the PRD does not say it and the split depends on it, ask.

## Clarifying questions

Ask before writing when:

- the PRD has an unresolved "Открытые вопросы" section;
- a criterion is not verifiable or contradicts another one, or the scope and the criteria do not match;
- the split into phases depends on a decision that is not in the PRD (which scenario is the main one, what may be released first);
- a task seems necessary but has no basis in the PRD (ask whether to add it to the PRD instead of silently adding it to the plan).

How to ask:

- Use `AskUserQuestion`: at most 4 questions per round, 2-4 concrete options each, the recommended option first.
- Ask only what changes the plan. At most two rounds; after that write the plan and note what is still open under the affected phase.
- If the PRD is clear and complete, ask nothing.

## Quality check before saving

- The title is `Plan-{feature name}`, the file is `docs/plan-{slug}.md`, the date is today's (from the conversation context, or `date +%F`; never guessed).
- Phase 1 is a Tracer Bullet and gives a working path through all needed layers.
- Every phase has Цель, Затрагивает, Задачи (1-5), Тесты, Готово когда, Статус and the closed PRD criteria.
- Frontend touched → a Playwright test is planned; logic touched → a PHPUnit test is planned; no phase is without tests.
- Every PRD criterion appears in the coverage table exactly once.
- No task without a basis in the PRD; nothing from "Не в скоупе".
- Every "Готово когда" is verifiable without interpretation.
