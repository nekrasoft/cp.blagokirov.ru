import { createHash } from 'node:crypto';
import { existsSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const outputPath = join(root, 'public/build/build-fingerprint.json');

const sourcePaths = [
    'app/Filament',
    'resources/css',
    'resources/js',
    'resources/views',
    'package.json',
    'package-lock.json',
    'vite.config.js',
];

const ignoredPathParts = [
    ['storage', 'framework', 'views'],
    ['public', 'build'],
];

function toRelativePath(path) {
    return relative(root, path).split(sep).join('/');
}

function shouldIgnore(path) {
    const relativePathParts = toRelativePath(path).split('/');

    return ignoredPathParts.some((ignoredParts) => {
        return ignoredParts.every((part, index) => relativePathParts[index] === part);
    });
}

function collectFiles(path) {
    if (!existsSync(path) || shouldIgnore(path)) {
        return [];
    }

    const stats = statSync(path);

    if (stats.isFile()) {
        return [toRelativePath(path)];
    }

    if (!stats.isDirectory()) {
        return [];
    }

    return readdirSync(path, { withFileTypes: true }).flatMap((entry) => {
        return collectFiles(join(path, entry.name));
    });
}

const files = sourcePaths
    .flatMap((sourcePath) => collectFiles(join(root, sourcePath)))
    .sort((a, b) => a.localeCompare(b));

const entries = files.map((file) => {
    const hash = createHash('sha256')
        .update(readFileSync(join(root, file)))
        .digest('hex');

    return `${hash}  ${file}`;
});

const fingerprint = createHash('sha256')
    .update(`${entries.join('\n')}\n`)
    .digest('hex');

const payload = `${JSON.stringify({
    algorithm: 'sha256:build-source-files:v1',
    fingerprint,
    files,
}, null, 2)}\n`;

mkdirSync(dirname(outputPath), { recursive: true });
writeFileSync(outputPath, payload);
