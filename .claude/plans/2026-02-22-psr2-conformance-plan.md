# PSR-2 Conformance & Reference Plugin Alignment Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bring `docker-plugin` into full PSR-2 compliance and structural alignment with the SamplePlugin, Tailscale, and Wireshark reference plugins, with phpcs enforced as a permanent project tool.

**Architecture:** Add `composer.json` + `phpcs.xml` as committed project artifacts. Install phpcs locally. Run phpcbf to auto-fix formatting. Manually fix docblocks, structural patterns, and one constructor bug. Verify with a final clean phpcs run before committing.

**Tech Stack:** PHP 7.4+, Composer, squizlabs/php_codesniffer ^3.x, PSR-2 standard

**Design doc:** `.claude/plans/2026-02-22-psr2-conformance-design.md`

---

## Prerequisites

PHP and Composer must be available. If not:

```bash
sudo apt-get install -y php-cli php-xml php-mbstring
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Verify:
```bash
php --version   # expect PHP 7.4+
composer --version
```

All commands run from `/home/user/ai/raspap/docker-plugin/`.

---

## Task 1: Add Toolchain Files

**Files:**
- Create: `composer.json`
- Create: `phpcs.xml`
- Modify: `.gitignore`

**Step 1: Create `composer.json`**

```json
{
    "name": "raspap/docker-plugin",
    "description": "A Docker container management plugin for RaspAP",
    "type": "project",
    "license": "GPL-3.0",
    "require": {},
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.9"
    },
    "scripts": {
        "cs":  "phpcs",
        "cbf": "phpcbf"
    }
}
```

**Step 2: Create `phpcs.xml`**

```xml
<?xml version="1.0"?>
<ruleset name="docker-plugin">
    <description>PSR-2 coding standard for docker-plugin</description>

    <!-- Files and directories to check -->
    <file>Docker.php</file>
    <file>DockerService.php</file>
    <file>DockerJobManager.php</file>
    <file>DockerHubClient.php</file>
    <file>ajax</file>

    <!-- Exclude mixed HTML/PHP templates -->
    <exclude-pattern>templates/*</exclude-pattern>
    <exclude-pattern>vendor/*</exclude-pattern>

    <rule ref="PSR2"/>

    <!-- Output formatting -->
    <arg name="colors"/>
    <arg value="sp"/>
    <arg name="parallel" value="4"/>
</ruleset>
```

**Step 3: Add `vendor/` to `.gitignore`**

Append to the existing `.gitignore`:
```
vendor/
```

**Step 4: Commit**

```bash
git add composer.json phpcs.xml .gitignore
git commit -m "chore: add phpcs toolchain with PSR2 ruleset"
```

---

## Task 2: Install phpcs and Capture Initial Violation Report

**Step 1: Install**

```bash
composer install
```

Expected: `vendor/` created, `vendor/bin/phpcs` present.

**Step 2: Run initial check**

```bash
./vendor/bin/phpcs 2>&1 | tee /tmp/phpcs-initial.txt
```

Expected: Many violations listed across `Docker.php`, `DockerService.php`, `DockerJobManager.php`, `DockerHubClient.php`, and `ajax/*.php`. Exit code non-zero.

**Step 3: Count violations**

```bash
./vendor/bin/phpcs --report=summary
```

Note the total. This is the baseline to beat.

> No commit for this task — read-only.

---

## Task 3: Auto-fix Formatting with phpcbf

**Step 1: Run phpcbf**

```bash
./vendor/bin/phpcbf
```

Expected output: lines like `Fixed X violations in N files`.

**Step 2: Run phpcs again**

```bash
./vendor/bin/phpcs 2>&1 | tee /tmp/phpcs-after-cbf.txt
```

Expected: Fewer violations. Remaining ones are things phpcbf cannot fix (missing docblocks, structural issues, the constructor bug).

**Step 3: Commit the auto-fixes**

```bash
git add Docker.php DockerService.php DockerJobManager.php DockerHubClient.php ajax/
git commit -m "style: apply phpcbf PSR-2 auto-fixes"
```

---

## Task 4: Fix `Docker.php` — Docblock + Structural Alignment

**File:** `Docker.php`

Read the current file before editing.

**Step 1: Add file-level PHPDoc block**

After the opening `<?php` line, before the `namespace` declaration, add:

```php
/**
 * Docker
 *
 * @description A Docker container management plugin for RaspAP
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 * @see         src/RaspAP/Plugins/PluginInterface.php
 * @see         src/RaspAP/UI/Sidebar.php
 */
```

**Step 2: Add blank line after opening class brace**

The class declaration should read:
```php
class Docker implements PluginInterface
{

    private string $pluginPath;
```
(blank line between `{` and first property — matches Tailscale/Wireshark pattern)

**Step 3: Fix constructor — add `loadData()` restoration**

Current constructor calls `$this->loadData()` but ignores the return value — a bug. Replace:

```php
// BEFORE (broken):
$this->loadData();

// AFTER (correct — matches SamplePlugin/Tailscale/Wireshark pattern):
if ($loaded = self::loadData()) {
    $this->serviceStatus = $loaded->serviceStatus;
}
```

**Step 4: Add docblock to `__construct()`**

```php
/**
 * Initializes the Docker plugin
 *
 * @param string $pluginPath path to the plugins directory
 * @param string $pluginName name of this plugin
 */
public function __construct(string $pluginPath, string $pluginName)
```

**Step 5: Rewrite `initialize()` — extract local variables**

Reference plugins (Tailscale, Wireshark, SamplePlugin) all extract to local vars. Replace:

```php
// BEFORE:
public function initialize(Sidebar $sidebar): void
{
    $sidebar->addItem('Docker', $this->icon, 'plugin__Docker', 77);
}

// AFTER:
/**
 * Initializes Docker and creates a sidebar item
 *
 * @param Sidebar $sidebar an instance of the Sidebar
 * @see src/RaspAP/UI/Sidebar.php
 * @see https://fontawesome.com/icons
 */
public function initialize(Sidebar $sidebar): void
{
    $label    = $this->label;
    $icon     = $this->icon;
    $action   = 'plugin__' . $this->getName();
    $priority = 77;

    $sidebar->addItem($label, $icon, $action, $priority);
}
```

**Step 6: Rewrite `handlePageAction()` — outer-if pattern**

Replace the inverted early-return with the outer-if pattern used by all three reference plugins:

```php
// BEFORE:
if (strpos($page, '/plugin__' . $this->getName()) !== 0) {
    return false;
}
// ... rest of logic ...
return true;

// AFTER:
if (strpos($page, '/plugin__' . $this->getName()) === 0) {
    // ... all existing logic unchanged, indented one level ...
    return true;
}
return false;
```

Also add docblock:
```php
/**
 * Handles a page action by processing inputs and rendering a plugin template
 *
 * @param string $page the current page route
 * @return bool true if this plugin handled the page, false otherwise
 */
public function handlePageAction(string $page): bool
```

**Step 7: Fix `renderTemplate()` — return error string on missing file**

```php
// BEFORE:
if (!file_exists($templateFile)) {
    return '';
}

// AFTER (matches SamplePlugin, Tailscale, Wireshark exactly):
if (!file_exists($templateFile)) {
    return "Template file {$templateFile} not found.";
}
```

Also add docblock:
```php
/**
 * Renders a template from inside the plugin directory
 *
 * @param string $templateName name of the template (without .php extension)
 * @param array  $__data       data to extract into the template scope
 * @return string rendered template output
 */
public function renderTemplate(string $templateName, array $__data = []): string
```

**Step 8: Add docblocks to `persistData()`, `loadData()`, `getName()`**

```php
/**
 * Persists plugin data to a temporary file
 *
 * @note Data is written to /tmp and cleared on reboot. Not for long-term storage.
 * @note Avoid $_SESSION vars — they may conflict with other plugins.
 */
public function persistData(): void

/**
 * Loads previously persisted plugin data
 *
 * @return self|null the deserialized instance, or null if none exists
 */
public static function loadData(): ?self

/**
 * Returns the abbreviated class name used as the plugin identifier
 *
 * @return string plugin name
 */
public static function getName(): string
```

**Step 9: Run phpcs on this file only**

```bash
./vendor/bin/phpcs Docker.php
```

Expected: Zero violations.

**Step 10: Commit**

```bash
git add Docker.php
git commit -m "fix: align Docker.php with reference plugin structure and fix loadData() constructor bug"
```

---

## Task 5: Fix `DockerService.php` — Docblocks

**File:** `DockerService.php`

Read the current file before editing.

**Step 1: Add file-level PHPDoc block**

After `<?php`, before `namespace`:
```php
/**
 * DockerService
 *
 * @description Service class for Docker daemon interactions
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 */
```

**Step 2: Add class-level PHPDoc block**

Before `class DockerService`:
```php
/**
 * Provides methods for managing Docker containers, images, volumes,
 * Compose projects, and daemon lifecycle via the Docker CLI.
 */
class DockerService
```

**Step 3: Add method docblocks**

Add the following docblocks above each method:

```php
/**
 * Returns all containers (running and stopped)
 *
 * @return array array of container objects decoded from docker ps JSON output
 */
public function getContainers(): array

/**
 * Returns all locally available images
 *
 * @return array array of image objects decoded from docker images JSON output
 */
public function getImages(): array

/**
 * Returns all volumes with inspect details merged in
 *
 * @return array array of volume info arrays including Mountpoint, Labels, CreatedAt, Driver
 */
public function getVolumes(): array

/**
 * Returns disk usage for images, containers, and volumes
 *
 * @return array parsed system df output, or ['raw' => string] fallback
 */
public function getSystemDf(): array

/**
 * Performs a lifecycle action on a container
 *
 * @param string $id     container ID or name
 * @param string $action one of: start, stop, rm
 * @return array{success: bool, output: string}
 */
public function containerAction(string $id, string $action): array

/**
 * Creates and starts a new container
 *
 * @param array $params container parameters:
 *   image        string (required) image name
 *   name         string optional container name
 *   ports        array  optional port mappings (e.g. ['8080:80'])
 *   volumes      array  optional volume mounts (e.g. ['/host:/container'])
 *   env          array  optional env vars (e.g. ['FOO=bar'])
 *   network      string optional network name
 *   restart      string optional restart policy
 *   entrypoint   string optional entrypoint override
 *   labels       array  optional labels (e.g. ['key=value'])
 *   cpu_limit    float  optional CPU limit
 *   memory_limit int    optional memory limit in MB
 *   cmd          string optional command override
 * @return array{success: bool, container_id: string, error: string}
 */
public function createContainer(array $params): array

/**
 * Deletes a Docker image by ID or name
 *
 * @param string $id image ID or name:tag
 * @return array{success: bool, output: string}
 */
public function deleteImage(string $id): array

/**
 * Creates a named volume
 *
 * @param string $name   volume name
 * @param string $driver volume driver (default: local)
 * @param array  $labels optional labels as ['key=value', ...]
 * @return array{success: bool, name: string, error: string}
 */
public function createVolume(string $name, string $driver = 'local', array $labels = []): array

/**
 * Deletes a volume by name
 *
 * @param string $name volume name
 * @return array{success: bool, output: string}
 */
public function deleteVolume(string $name): array

/**
 * Returns raw JSON inspect output for a container
 *
 * @param string $id container ID or name
 * @return string JSON string from docker inspect
 */
public function inspectContainer(string $id): string

/**
 * Returns the Docker daemon systemd status
 *
 * @return string one of: active, inactive, failed, unknown
 */
public function getDaemonStatus(): string

/**
 * Starts the Docker daemon via systemctl
 *
 * @return array{success: bool}
 */
public function startDockerDaemon(): array

/**
 * Returns the installed Docker version string
 *
 * @return string e.g. "Docker version 24.0.5, build ..." or empty string if not found
 */
public function getDockerVersion(): string

/**
 * Returns all Compose projects found under the compose config directory
 *
 * Each project is a directory containing a docker-compose.yml file.
 *
 * @return array array of ['name', 'path', 'yaml', 'modified'] per project
 */
public function getComposeProjects(): array

/**
 * Saves or overwrites a docker-compose.yml for the given project name
 *
 * @param string $project project name (alphanumeric, hyphens, underscores only)
 * @param string $yaml    YAML content to write
 * @return bool true on success
 */
public function saveComposeFile(string $project, string $yaml): bool

/**
 * Deletes a Compose project directory and all its contents
 *
 * @param string $project project name (alphanumeric, hyphens, underscores only)
 * @return bool true on success, false if not found or name invalid
 */
public function deleteComposeProject(string $project): bool

/**
 * Lists the contents of a volume mountpoint path
 *
 * @param string $mountpoint absolute path of the volume mountpoint
 * @param string $subpath    optional subdirectory relative to mountpoint
 * @return array{entries: array, current_path: string, error: string}
 * @throws \RuntimeException on invalid path or traversal attempt
 */
public function browseVolumePath(string $mountpoint, string $subpath = ''): array
```

**Step 4: Run phpcs on this file**

```bash
./vendor/bin/phpcs DockerService.php
```

Expected: Zero violations.

**Step 5: Commit**

```bash
git add DockerService.php
git commit -m "docs: add PHPDoc blocks to DockerService"
```

---

## Task 6: Fix `DockerJobManager.php` — Docblocks

**File:** `DockerJobManager.php`

Read the current file before editing.

**Step 1: Add file-level PHPDoc block**

```php
/**
 * DockerJobManager
 *
 * @description Manages long-running Docker background jobs (e.g. image pulls)
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 */
```

**Step 2: Add class-level PHPDoc block**

```php
/**
 * Runs shell commands as background processes, tracking them by job ID.
 * Job output is written to /tmp log files and queried via polling.
 */
class DockerJobManager
```

**Step 3: Add method docblocks**

```php
/**
 * Starts a background job and returns a unique job ID
 *
 * @param string $cmd the shell command to run in the background
 * @return string job ID used to query status and clean up
 */
public function startJob(string $cmd): string

/**
 * Returns the current status and output of a background job
 *
 * @param string $jobId job ID returned by startJob()
 * @return array{running: bool, output: string, done: bool}
 */
public function getJobStatus(string $jobId): array

/**
 * Removes the log and PID files for a completed job
 *
 * @param string $jobId job ID returned by startJob()
 * @return void
 */
public function cleanupJob(string $jobId): void
```

**Step 4: Run phpcs on this file**

```bash
./vendor/bin/phpcs DockerJobManager.php
```

Expected: Zero violations.

**Step 5: Commit**

```bash
git add DockerJobManager.php
git commit -m "docs: add PHPDoc blocks to DockerJobManager"
```

---

## Task 7: Fix `DockerHubClient.php` — Docblocks

**File:** `DockerHubClient.php`

Read the current file before editing.

**Step 1: Add file-level PHPDoc block**

```php
/**
 * DockerHubClient
 *
 * @description Docker Hub API client for searching public repositories
 * @author      RaspAP <hello@raspap.com>
 * @license     https://github.com/RaspAP/raspap-webgui/blob/master/LICENSE
 */
```

**Step 2: Add class-level PHPDoc block**

```php
/**
 * Wraps the Docker Hub v2 search API with response normalization
 * and HTTP error handling.
 */
class DockerHubClient
```

**Step 3: Add docblock to `search()`**

```php
/**
 * Searches Docker Hub for public repositories matching a query
 *
 * @param string $query search string (must not be empty)
 * @param int    $page  page number for pagination (1-based)
 * @return array{results: array, total_count: int, page: int, error: bool, error_message?: string}
 */
public function search(string $query, int $page = 1): array
```

**Step 4: Run phpcs on this file**

```bash
./vendor/bin/phpcs DockerHubClient.php
```

Expected: Zero violations.

**Step 5: Commit**

```bash
git add DockerHubClient.php
git commit -m "docs: add PHPDoc blocks to DockerHubClient"
```

---

## Task 8: Fix `ajax/*.php` — Any Remaining Violations

**Step 1: Run phpcs on ajax directory**

```bash
./vendor/bin/phpcs ajax/
```

Review the output. If phpcbf (Task 3) already handled everything, this may show zero violations.

**Step 2: Fix any remaining violations manually**

Common remaining issues in procedural AJAX scripts:
- Missing blank line after `<?php`
- Missing newline at end of file
- Long lines exceeding 120 chars

Fix each flagged line directly per the phpcs output.

**Step 3: Run phpcs again to verify**

```bash
./vendor/bin/phpcs ajax/
```

Expected: Zero violations.

**Step 4: Commit if any changes were made**

```bash
git add ajax/
git commit -m "style: fix remaining PSR-2 violations in ajax handlers"
```

---

## Task 9: Final Verification and Summary Commit

**Step 1: Run full phpcs check**

```bash
./vendor/bin/phpcs
```

Expected: No output, exit code 0 (zero violations).

If any violations remain, fix them now and re-run until clean.

**Step 2: Run phpcs summary report**

```bash
./vendor/bin/phpcs --report=summary
```

Expected: All files listed with 0 errors, 0 warnings.

**Step 3: Verify `composer cs` script works**

```bash
composer cs
```

Expected: Clean exit.

**Step 4: Final commit**

```bash
git log --oneline -8
```

Review that commits are clean and logical, then push if desired.
