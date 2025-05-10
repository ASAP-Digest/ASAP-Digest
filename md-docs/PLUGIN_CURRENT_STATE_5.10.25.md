# ASAP Digest Core Plugin – Current State Documentation

_Last updated: 2025-05-10_

---

## 1. Overview

The ASAP Digest Core plugin provides the foundational backend for the ASAP Digest application, integrating a modern ingestion pipeline, centralized admin UI, REST API endpoints, and protocol-driven architecture. All code and configuration strictly adhere to project protocols for class organization, menu registration, dependency loading, and error prevention.

---

## 2. Directory & File Structure (Key Areas)

```
wp-content/plugins/asapdigest-core/
├── asapdigest-core.php                # Main plugin bootstrap (centralized menu registration)
├── includes/
│   ├── class-core.php                 # Main plugin singleton, dependency loader
│   ├── class-database.php             # Database management, settings, stats
│   ├── class-better-auth.php          # Better Auth integration
│   ├── class-usage-tracker.php        # Usage tracking
│   ├── traits/
│   │   ├── user-sync.php              # User sync trait
│   │   └── session-mgmt.php           # Session management trait
│   └── api/
│       ├── class-rest-base.php        # Base REST controller
│       ├── class-rest-auth.php        # Auth endpoints
│       ├── class-rest-digest.php      # Digest endpoints
│       ├── class-rest-ingested-content.php # Ingested content endpoints
│       ├── class-session-check-controller.php
│       ├── class-sk-token-controller.php
│       ├── class-active-sessions-controller.php
│       ├── class-check-sync-token-controller.php
│       ├── class-user-controller.php
│       ├── class-sk-user-sync.php
│       └── class-sync-token-controller.php
├── admin/
│   ├── class-central-command.php      # Central Command dashboard, analytics, moderation
│   ├── class-admin-ui.php             # UI helpers, static utilities
│   └── views/
│       ├── dashboard.php
│       ├── ingested-content.php
│       ├── analytics-dashboard.php
│       ├── ai-settings.php
│       ├── stats-page.php
│       ├── settings-page.php
│       ├── main-page.php
│       ├── usage-analytics.php
│       ├── service-costs.php
│       └── test-session-sync.php
├── templates/
│   └── digest-email.php
├── tests/
│   └── test-session-sync.php
```

---

## 3. Class & Component Catalogue

| File/Component                                 | Namespace/Class                          | Purpose/Role                                                      |
|------------------------------------------------|------------------------------------------|-------------------------------------------------------------------|
| `asapdigest-core.php`                          | (none)                                   | Plugin bootstrap, centralized menu registration, hooks             |
| `includes/class-core.php`                      | `ASAPDigest\Core\ASAP_Digest_Core`      | Singleton, dependency loader, main plugin logic                    |
| `includes/class-database.php`                  | `ASAPDigest\Core\ASAP_Digest_Database`  | DB management, settings, stats, table creation                     |
| `includes/class-better-auth.php`               | `ASAPDigest\Core\ASAP_Digest_Better_Auth`| Better Auth integration                                           |
| `includes/class-usage-tracker.php`             | `ASAPDigest\Core\ASAP_Digest_Usage_Tracker`| Usage/cost tracking                                         |
| `admin/class-central-command.php`              | `ASAPDigest\Core\ASAP_Digest_Central_Command`| Central Command dashboard, analytics, moderation           |
| `admin/class-admin-ui.php`                     | `ASAPDigest\Core\ASAP_Digest_Admin_UI`  | UI helpers, static utilities (no menu registration)                |
| `includes/api/class-rest-base.php`             | `ASAPDigest\Core\API\ASAP_Digest_REST_Base`| Base REST controller                                         |
| `includes/api/class-rest-auth.php`             | `ASAPDigest\Core\API\ASAP_Digest_REST_Auth`| Auth REST endpoints                                         |
| `includes/api/class-rest-digest.php`           | `ASAPDigest\Core\API\ASAP_Digest_REST_Digest`| Digest REST endpoints                                     |
| `includes/api/class-rest-ingested-content.php` | `ASAPDigest\Core\API\ASAP_Digest_REST_Ingested_Content`| Ingested content REST endpoints           |
| ...                                            | ...                                      | ...                                                               |

---

## 4. Menu Registration (Centralized, Protocol-Compliant)

All admin menu and submenu registration is performed in `asapdigest-core.php` via the `asap_add_central_command_menu()` function, registered with `admin_menu`.

**Main Menus:**
- ⚡️ Central Command (slug: `asap-central-command`)
- ASAP Digest (legacy/feature, slug: `asap-digest`)

**Submenus (Central Command):**
- Digests
- User Stats
- Auth Settings
- Settings
- Ingested Content

**Submenus (ASAP Digest):**
- Dashboard
- Crawler Sources
- Moderation Queue
- Analytics
- AI Settings
- Usage Analytics
- Service Costs

**All menu callbacks are defined as standalone functions or as methods in `ASAP_Digest_Central_Command` and are required before registration.**

---

## 5. REST API Endpoints

- All REST controllers are in `includes/api/` and extend `ASAP_Digest_REST_Base`.
- Key endpoints:
  - `/ingested-content` (GET, POST): List/add ingested content (deduplicated, quality scored)
  - `/crawler/sources`, `/crawler/queue`, `/crawler/metrics`, etc.: Crawler management
  - `/auth`, `/digest`, `/session-check`, etc.: Auth/session/digest management

---

## 6. Admin UI & Views

- All admin UI views are in `admin/views/`.
- Main dashboard, analytics, moderation, ingested content, settings, and stats pages are present.
- All UI helpers (cards, forms, status indicators) are static methods in `ASAP_Digest_Admin_UI`.
- No menu registration or instantiation occurs in admin UI classes (protocol-compliant).

---

## 7. Ingestion Pipeline & Data Flow

- All new content is ingested into `wp_asap_ingested_content` (not `wp_posts`).
- Deduplication and quality scoring are handled via `wp_asap_content_index`.
- REST API and admin UI allow listing and manual addition of ingested content.
- Documentation: See `DATABASE_SCHEMA.md`, `DATA_FLOW.md` for schema and flow details.

---

## 8. Protocol Compliance & Error Prevention

- **Menu registration**: Centralized, single location, no duplication (see `wordpress-menu-registration-protocol`).
- **Class organization**: One class per file, correct namespaces, dependency loading order enforced (see `wordpress-class-organization`).
- **No circular dependencies**: All require/include paths are plugin-root relative and ordered per protocol.
- **No duplicate class definitions**: Verified via audit and file search.
- **All admin UI classes**: Only used for static helpers/utilities, not for menu registration or instantiation.
- **All REST controllers**: Registered via hooks in the main plugin file or via `ASAP_Digest_Core`.

---

## 9. Testing & Verification

- Tests are present in `tests/` (e.g., `test-session-sync.php`).
- All major flows (ingestion, REST, admin UI) are covered by manual and automated tests.
- All changes are verified against protocol checklists before deployment.

---

## 10. Outstanding Issues / Next Steps

- Continue to expand test coverage for new ingestion and moderation flows.
- Review and update documentation as new features are added.
- Ensure all new code and refactors are protocol-compliant.
- Regularly audit for menu/class/namespace/file alignment.

---

## 11. References

- [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md)
- [DATA_FLOW.md](./DATA_FLOW.md)
- [ASAP_Digest_Core_Plugin-Implementation_Plan.md](./ASAP_Digest_Core_Plugin-Implementation_Plan.md)
- [ROADMAP_TASKS.md](./ROADMAP_TASKS.md)

---

_End of current state documentation._ 