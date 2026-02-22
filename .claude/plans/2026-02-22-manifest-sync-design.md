# Manifest Sync Design

**Date:** 2026-02-22
**Scope:** `docker-plugin/manifest.json` and `plugins/manifest.json` (Docker entry only)

---

## Goal

Bring both manifest files into alignment with reference plugin conventions and correct authorship.

---

## Changes

### `docker-plugin/manifest.json`

| Field | Before | After |
|---|---|---|
| `author` | `"RaspAP"` | `"cyrus104"` |
| `author_uri` | `"https://github.com/RaspAP"` | `"https://github.com/cyrus104"` |
| `javascript` | `["app/js/Docker.js"]` (array) | `{"source": "app/js/Docker.js"}` (object) |

### `plugins/manifest.json` Docker entry (ID 9)

| Field | Change |
|---|---|
| `author` | `"RaspAP"` → `"cyrus104"` |
| `author_uri` | `"https://github.com/RaspAP"` → `"https://github.com/cyrus104"` |
| `keys` | Added — Docker GPG key + apt source |
| `dependencies` | Added — docker-ce, docker-ce-cli, containerd.io, docker-buildx-plugin, docker-compose-plugin |
| `configuration` | Added — config/.gitkeep → /etc/raspap/docker/compose/ |
| `sudoers` | Added — 5 www-data NOPASSWD rules |
| `javascript` | Added — `{"source": "app/js/Docker.js"}` |

---

## Rationale

- The registry entry was previously a stub (metadata only). All other registry entries that install packages include `keys`, `dependencies`, `configuration`, and `sudoers`. The Docker plugin installs Docker Engine and related packages, so all installation fields are required.
- The `javascript` field format follows the object convention `{"source": "..."}` used by DynDNS, Firewall, and NTPServer. `PluginInstaller::copyJavaScriptFiles()` iterates with `foreach ($javascript as $js)`, which works with both formats, but the object form is the project convention.
- The JS path `"app/js/Docker.js"` matches the actual file location within the plugin directory (not `templates/app/js/` as used by some other plugins that organize their assets differently).
- Author corrected to `cyrus104` in both files.
