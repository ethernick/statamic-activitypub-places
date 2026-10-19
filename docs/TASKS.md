# ActivityPubPlaces Task List & Roadmap

> **Legend**
> - [ ] To Do
> - [/] In Progress
> - [x] Done

---

## 1. Core Architecture, Data Modeling & Federation

### 1.1 Core Architecture & Data Models
- [x] Define `place.yaml` blueprint with coordinates, `osm_type`, `osm_id`, address, and `osm_name`.
- [x] Configure nested routing (`/places/{osm_type}/{osm_id}`).
- [x] Register `Place` type in `ActivityPubTypes` with `PlaceController`, `PlaceOutboxHandler`, and hooks.
- [x] Expanded backend `PlaceSearchController::activities` query to include linked activities, notes, and events.
- [x] Refactor `PlaceSearchController::activities` to use Core's standardized `ActivityPubUtils::transformEntryForFeed()`.

### 1.2 Cross-Collection Antlers Tag Resolution
- [x] Support resolving venue data across 5 fallback tiers (explicit tag parameters, context `location`/`place` variables, current entry, context ID, and OSM identifiers).
- [x] Support unwrapping related entries (`events`, `notes`, etc.) that reference places via `location` field.
- [x] Support Antlers tag pair iteration (`parseLoop`) for `{{ activitypub_place:links }}...{{ /activitypub_place:links }}`.
- [x] Add unit tests in `ActivitypubPlaceTagTest.php` covering non-place entries with location fields and tag pairs.

### 1.3 Automated Testing & Sandbox Isolation
- [x] Ensure `ActivityPubPlacesInstall` uses `ActivityPubUtils::blueprintPath()` to respect test sandboxes.
- [x] Ensure automated creation of `places` collection and configuration in `settings/activitypub.yaml`.
- [x] Add dedicated sandbox installation test in `FreshInstallationTest.php` (All 24 PHPUnit tests passing).
- [x] Resolve Leaflet layerGroup mocking and verify Vitest passing (5/5 tests).

---

## 2. Control Panel & Frontend Presentation

### 2.1 CP UX & OpenStreetMap Integration
- [x] Build `LocationSearch.vue` with Nominatim API and Overpass discovery.
- [x] Implement interactive click-to-pin placement and reverse geocoding.
- [x] Build `PlacesDashboard.vue` with full-bleed Leaflet map view and venue list.
- [x] Build `CheckInStack.vue` with actor selection, commentary, and Mastodon preview.
- [x] Build `InboxPlaceMap.vue` and `InboxActivityArrive.vue` for interactive inbox stream maps.

### 2.2 Reusable Feed Integration & Dual-Build Pipeline
- [x] Configure Vite and `./bin/build-v6.sh` for dual Vue 2 (Statamic 5) and Vue 3 (Statamic 6) compilation.
- [x] Redesign Related Activity Stream in `PlacesDashboard.vue` / `PlacesDashboard.v6.vue`: removed internal pill, removed redundant object type pills (`Event`, `Note`, `Check-in`), suppressed note titles to mirror inbox stream conventions, parsed Markdown on the backend via `Markdown::parse()`, constrained avatar dimensions, and unified feed presentation (major cards for Notes/Events/Check-ins, compact rows for Like/Announce/Follow).
- [x] Migrate `PlacesDashboard.vue` / `PlacesDashboard.v6.vue` activity stream to Core's reusable `<activity-pub-post-card>` component (`readonly: true`).

---

## 3. Packaging, Developer Standards & Documentation

### 3.1 Package Metadata & Compliance
- [x] Packagist & Marketplace Schema Compliance (`composer.json`):
  - [x] Add explicit commercial source-available licensing and `type`: `statamic-addon`.
  - [x] Add targeted fediverse and geolocation keywords (`places`, `openstreetmap`, `nominatim`, `checkin`).
  - [x] Set accurate author profile, issues, source, and documentation support links.
  - [x] Enforce clean version constraints: PHP `^8.2`, Statamic `^5.0 | ^6.0`, Core `^1.0 || dev-main`.
  - [x] Validate schema via `composer validate --no-check-publish`.

### 3.2 Operator Manuals & Architecture Documentation
- [x] Visual Marketplace README Overhaul (`README.md`):
  - [x] Add badges (Statamic 5 & 6, PHP 8.2+, OpenStreetMap, Fediverse, MIT/Proprietary).
  - [x] Add Feature Grid (Check-Ins, Nominatim Search, Pin Drop, Compatibility Mode, Antlers Tags).
  - [x] Add clean 3-step Quick Start guide and architecture Mermaid diagram.
  - [x] Add Control Panel screenshot layout and Antlers frontend documentation.
- [x] Operator Manuals (`INSTALLATION.md` & `TROUBLESHOOTING.md`):
  - [x] Document system requirements and automated installation steps.
  - [x] Document mandatory Nominatim User-Agent setup and Mastodon Compatibility Mode.
  - [x] Provide diagnostic matrix for Nominatim HTTP 403s, Leaflet map container sizing, and queue workers.
- [x] Deep Architecture Guide (`PLACES_GUIDE.md`):
  - [x] Document dual-name architecture (`osm_name` vs editorial `title`).
  - [x] Document OSM identifier mapping (`/places/{osm_type}/{osm_id}`).
  - [x] Document `Arrive` vs `Create` ActivityStreams topology and caching policies.
- [x] Developer Extension Guide (`HOOKS.md`):
  - [x] Document `ActivityPubTypes::registerOutboxHook` and `registerTransformHook` for venue enrichment.
  - [x] Document `{{ activitypub_place:map }}` and `{{ activitypub_place:links }}` customization and wrapping.
- [x] Marketplace Launch Blueprint (`docs/MARKETPLACE_LAUNCH.md`):
  - [x] Define hero banner specifications (1200x630, dark cartography obsidian theme, emerald + indigo glow).
  - [x] Outline 4 high-converting Control Panel screenshot briefs (Dashboard, Pin Placement, Check-In, Inbox Map).
  - [x] Create 15-second demo video storyboard (CP Pin Drop -> Check-in -> Mastodon Live Post).
  - [x] Author Marketplace pitch deck and copy pack.

---

## 4. Active Release Punchlist (Pre-Launch Verification)

- [ ] **Task 4.1: Git Repository & Tagging**
  - [ ] Push clean commits to `origin/dev` and merge into `origin/main`.
  - [ ] Tag release **`v0.3.0`** (Labs milestone) on GitHub.
- [ ] **Task 4.2: Packagist Registration**
  - [ ] Submit `ethernick/statamic-activitypub-places` to [Packagist.org](https://packagist.org).
  - [ ] Configure GitHub webhook for automatic package sync.
- [ ] **Task 4.3: Statamic Marketplace Listing Setup**
  - [ ] Create listing for "ActivityPub Places" in Statamic Partner Portal.
  - [ ] Upload 1200x630 hero banner and 4 CP screenshots.
  - [ ] Set pricing tier: **$10** (Early Adopter "Labs" price, scaling to $29–$39 at v1.0).
  - [ ] Set version to **0.3.x**.
- [ ] **Task 4.4: Live Verification**
  - [ ] Test installation on a clean Statamic site via `composer require ethernick/statamic-activitypub-places`.
  - [ ] Verify live Nominatim search and Mastodon federation end-to-end.

---

## 5. Future Roadmap (Post-v1.0)

- [ ] **Offline Map Tile Caching**: Local caching of OSM map tiles for air-gapped or privacy-focused intranet environments.
- [ ] **Multi-Point Route Serialization**: GeoJSON LineString and Path support for federated trails, routes, and excursions.
- [ ] **Automatic Timezone Derivation**: Auto-populate timezone metadata based on latitude/longitude coordinates on venue creation.