import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const commit = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();

mkdirSync('public/build', { recursive: true });
writeFileSync(
    'public/build/ministry-build.json',
    `${JSON.stringify({ commit, generatedAt: new Date().toISOString() }, null, 2)}\n`,
);
