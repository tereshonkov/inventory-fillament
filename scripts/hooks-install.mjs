#!/usr/bin/env node
// Підключає git-хуки з .githooks (core.hooksPath) і робить pre-commit виконуваним.
import { execFileSync } from 'node:child_process';
import { chmodSync } from 'node:fs';

execFileSync('git', ['config', 'core.hooksPath', '.githooks'], { stdio: 'inherit' });

try {
    chmodSync('.githooks/pre-commit', 0o755);
    execFileSync('git', ['update-index', '--chmod=+x', '.githooks/pre-commit'], { stdio: 'ignore' });
} catch {
    // на Windows chmod/update-index можуть бути недоступні до першого git add — не критично
}

console.log('Git-хуки підключено: core.hooksPath = .githooks');
