# Dependency Review

The frontend uses a locked npm dependency tree through `frontend/package-lock.json`.

## Recommended Review Flow

```bash
cd frontend
npm ci
npm audit
npm run lint
npm run build
npm run build:wordpress
```

Do not run `npm audit fix --force` blindly. Forced upgrades can change major versions of Vite, React Router, ESLint, or related tooling and create misleading green builds.

## Current Dependency Notes

- the public frontend no longer depends on Framer Motion or the removed Three.js-based explorer stack
- route-level code splitting remains part of the current React architecture
- deployment depends on the generated theme `dist/` output, so dependency changes should always be validated with both frontend build commands

## Update Discipline

1. Read the full audit report
2. Separate production risk from tooling-only findings
3. Prefer non-breaking patch or minor upgrades first
4. Review Dependabot pull requests individually
5. Re-run lint, `build`, and `build:wordpress` after each dependency change
6. Commit `package.json` and `package-lock.json` together
