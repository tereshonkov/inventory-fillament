---
name: prd
description: Creates a short PRD (product requirements document) for a feature in docs/prd-{feature}.md. Use it when a feature has to be described before it is implemented - "write a PRD", "describe the feature first", "/prd <feature>". It captures what and why (goal, user scenarios, scope, out of scope, technical constraints, acceptance criteria), never how to implement. Asks clarifying questions first if the description is short or vague.
argument-hint: "<feature name or short description>"
---

# PRD

Write a PRD for one feature: **what** has to exist and **why**. Never **how** to build it.

## Input

The arguments are the feature name or a short description (for example `/prd bulk write-off of assets`).

- No arguments → ask for the feature name and a one-sentence description. Do not invent a feature.
- The PRD is **not** an implementation plan. Do not start implementing anything after writing it.

## Output

One file: `docs/prd-{slug}.md` (create `docs/` if it does not exist).

- `{slug}` — the feature name in lowercase latin kebab-case, short (2-5 words): `bulk write-off of assets` → `bulk-write-off`. Transliterate non-latin names.
- If the file already exists: do not overwrite it silently. Ask whether to update it or to use another slug.

## Steps

1. **Collect context** before asking anything. Read `CLAUDE.md` (stack, roles, conventions, known limits, test/commit rules), look through `docs/` (existing PRDs and plans) and, if the feature touches existing behaviour, the relevant code. Do not ask the user what the repository already answers.
2. **Decide whether to ask questions** (see "Clarifying questions"). Ask first, write after.
3. **Write the file** with exactly the structure below.
4. **Reply** with the path and a 3-5 line summary, then list anything still open. Offer the next step (a plan in `docs/plan-{feature}.md`), but do not start it.
5. **Project documentation:** add a one-line entry to the "Change log" of `CLAUDE.md` (in English, newest first) saying that the PRD was added.

## Structure

Use these headings and this order. Do not add other sections (one exception: "Открытые вопросы", see Rules).

```markdown
# PRD-{feature name}

**Дата:** {YYYY-MM-DD}

## Цель
{1-2 sentences: why the user needs this. Value for the user, not a description of the solution.}

## Пользовательские сценарии
1. {role} -> {action} -> {result}
2. ...

## Скоуп
- {concrete item that is part of the feature}
- ...

## Не в скоупе
- {what is explicitly NOT done in this iteration}
- ...

## Технические ограничения
- {known constraint that has to be respected}
- ...

## Критерии готовности
- [ ] {verifiable criterion}
- [ ] ...
```

The headings are fixed in Russian (the project standard). The content is written in the language the user writes in.

### Section rules

- **Дата** — today's date, `YYYY-MM-DD`. Take it from the current date given in the conversation; if it is not there, run `date +%F`. Never guess.
- **Цель** — at most two sentences. Answers "why does the user need this".
- **Пользовательские сценарии** — one line each, always `role -> action -> result`. Use the real roles of the product (see `CLAUDE.md`), not "the user". One scenario = one observable outcome. Number them (S1, S2...) so criteria and tests can reference them.
- **Скоуп** — a concrete list of what is built. Each item is something that can be shown or checked. No class, table, file or library names.
- **Не в скоупе** — only things a reader could reasonably expect but that are deliberately left out. Not a dumping ground for far-fetched ideas.
- **Технические ограничения** — real, known constraints that affect the feature: roles and access rules, supported databases, environments, language of the UI, existing behaviour that must not break, performance or data-volume limits. Take them from `CLAUDE.md` and the code, not from imagination. State the constraint, not the solution.
- **Критерии готовности** — a checklist (`- [ ]`). Every criterion must be verifiable by a test or a manual check: an observable behaviour with concrete values, roles and conditions. Reference scenarios where useful (`S2: ...`). Forbidden: "works fast", "convenient", "correct", "nice". At least one criterion per scenario, plus one for each important edge case or access rule.

## Rules

- Be concrete and brief. No filler, no marketing language, no repetition between sections.
- Describe **what and why**, never how: no architecture, no code, no file or class names, no step-by-step solution.
- Do not invent facts, numbers, thresholds or requirements the user did not give. If something matters and is unknown, ask; if the user cannot answer, put it into a short "Открытые вопросы" section at the end (the only optional section) instead of guessing.
- Every item in "Скоуп" must be covered by at least one scenario or criterion, and every criterion must trace back to the scope. Remove items that do not.
- Do not duplicate what already exists. If part of the feature is already implemented, say so in the constraints and keep it out of the scope.

## Clarifying questions

Ask before writing when the description is short or leaves any of these unclear:

- who uses it (which roles) and in which situation;
- what the user wants to achieve and what the visible result is;
- what is explicitly out of scope for this iteration;
- constraints that cannot be found in the repository (deadlines, data volumes, external systems, legal or business rules);
- how "done" is checked (what exactly must be true).

How to ask:

- Use the `AskUserQuestion` tool: at most 4 questions per round, 2-4 options each, the recommended option first, concrete options (not "yes/no" when a choice exists).
- Ask only what changes the document. Do not ask what `CLAUDE.md`, `docs/` or the code already answer.
- Maximum two rounds. After that write the PRD and list the rest under "Открытые вопросы".
- If the description is already complete (who, what, why, boundaries are clear), do not ask anything and write the file.

## Quality check before saving

- The title is `PRD-{feature name}`, the file is `docs/prd-{slug}.md`.
- All seven blocks are present in the right order, nothing else (except possibly "Открытые вопросы").
- Every scenario has the form `role -> action -> result`.
- Every criterion can be checked without interpretation.
- No implementation details anywhere.
