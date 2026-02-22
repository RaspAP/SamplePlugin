# PSR-2 Conformance & Reference Plugin Alignment Design

**Date:** 2026-02-22
**Scope:** `docker-plugin` only — no changes to `sampleplugin`, `raspap-insiders`, or any other repo

---

## Goal

Bring `docker-plugin` into full conformance with:

1. The structural and docblock conventions established by the reference plugins (SamplePlugin, Tailscale, Wireshark)
2. PSR-2 coding style, verified by PHP_CodeSniffer

---

## Approach

Add `composer.json` and `phpcs.xml` as permanent project artifacts so the style check is reproducible for all future contributors. Fix all violations — automated where possible via `phpcbf`, manually for docblocks and structural issues.

---

## Section 1: Toolchain

### Files added

| File | Purpose |
|---|---|
| `composer.json` | Declares `squizlabs/php_codesniffer` as a `require-dev` dependency; exposes `composer cs` (check) and `composer cbf` (auto-fix) scripts |
| `phpcs.xml` | Ruleset: PSR2 standard, scoped to class files and `ajax/`, excluding `templates/` (mixed HTML/PHP) |

### Scripts

```bash
composer install          # install phpcs locally into vendor/
composer cs               # run phpcs — exits 1 on violations
composer cbf              # run phpcbf — auto-fixes formatting violations
```

### Scope

- **Checked:** `Docker.php`, `DockerService.php`, `DockerJobManager.php`, `DockerHubClient.php`, `ajax/*.php`
- **Excluded:** `templates/` — mixed HTML/PHP; PSR-2 does not apply cleanly

---

## Section 2: Structural & Docblock Fixes

Changes to align with reference plugin conventions (SamplePlugin, Tailscale, Wireshark).

### `Docker.php`

- Add file-level PHPDoc block (`@description`, `@author`, `@license`, `@see`)
- Add blank line after opening class brace
- Add docblocks to all public methods: `initialize()`, `handlePageAction()`, `renderTemplate()`, `persistData()`, `loadData()`, `getName()`
- `initialize()`: extract inline literals to local variables (`$label`, `$icon`, `$action`, `$priority`) before calling `$sidebar->addItem()` — exact pattern used by all three reference plugins
- `handlePageAction()`: rewrite to outer-if pattern (`if (strpos() === 0) { ... return true; } return false;`) replacing the current inverted early-return
- `renderTemplate()`: return `"Template file {$templateFile} not found."` on missing file instead of `''` — matches all reference plugins
- **Fix constructor bug:** `loadData()` return value is currently discarded silently; rewrite constructor to use `if ($loaded = self::loadData()) { ... }` and restore relevant state

### `DockerService.php`, `DockerJobManager.php`, `DockerHubClient.php`

- Add file-level PHPDoc block to each
- Add class-level PHPDoc block to each
- Add `@param` / `@return` docblocks to all public methods

### `ajax/*.php`

- PSR-2 formatting checked and fixed (spacing, braces, blank lines)
- No structural changes — these are procedural scripts, not classes

---

## Section 3: PSR-2 Violations

Known violations to be fixed (full list confirmed by `phpcs` output after install):

| File | Violation |
|---|---|
| All class files | Missing blank line after opening class brace |
| `Docker.php` | Inconsistent concatenation spacing (`'a'.$b` vs `'a' . $b`) |
| `DockerService.php` | Spacing inconsistencies in `escapeshellarg` calls |
| `DockerJobManager.php` | Spacing inside function call arguments |
| `DockerHubClient.php` | Aligned assignment padding (not permitted by PSR-2) |
| `ajax/*.php` | Blank lines, brace placement, spacing — confirmed by phpcs |

**Auto-fixed by `phpcbf`:** indentation, operator spacing, blank lines, brace placement, trailing whitespace.

**Requires manual fix:** docblocks, constructor bug, `renderTemplate()` return value, `handlePageAction()` pattern rewrite.

---

## Execution Order

1. Add `composer.json` and `phpcs.xml` → commit
2. `composer install` to get phpcs locally
3. `composer cs` → capture full violation list
4. `composer cbf` → auto-fix formatting violations
5. Manual fixes: docblocks, constructor bug, structural alignment
6. `composer cs` → confirm zero violations
7. Commit all fixes

---

## Out of Scope

- No changes to `templates/` PHP files
- No changes to `sampleplugin`, `raspap-insiders`, or any other repository
- No new features or refactoring beyond what is needed for conformance
