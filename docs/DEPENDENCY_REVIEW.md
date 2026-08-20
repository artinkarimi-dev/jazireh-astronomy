# Dependency Review

The frontend uses a locked npm dependency tree through `frontend/package-lock.json`.

## Required checks before production

```bash
cd frontend
npm ci
npm audit
npm run check
```

Do not run `npm audit fix --force` blindly. A forced upgrade may change major versions of Vite, React Router, Framer Motion, or related packages and can break the application.

Recommended workflow:

1. Read the complete `npm audit` report.
2. Separate production dependencies from development-tool findings.
3. Apply non-breaking patch updates first.
4. Review Dependabot pull requests one at a time.
5. Run lint, production build, and manual route testing after every dependency change.
6. Commit `package.json` and `package-lock.json` together.
