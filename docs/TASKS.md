### Core Architecture & Data Models
- [ ] Create `Place` Statamic Blueprint base (`places/place.yaml`).
  - Fields needed: Name, Latitude, Longitude, Radius, OSM ID, OSM Type (node/way/relation).
- [ ] Implement Configuration System to map Statamic Collections to "Places" object types (Custom Naming, Enabled, Federate).
- [ ] Create `Location` statamic Fieldtype (Vue component for Control Panel).
  - Feature: Search via OSM Nominatim API.
  - Feature: "Near Me" HTML5 geolocation lookup.
  - Feature: Fallback creation (custom location/zoom out).
  - Feature: "Save to Collection" dropdown to route the new Place entry to the correct collection.

# Tasks for ActivityPubPlaces

## 1. Core Dependencies (ActivityPubCore)
- [ ] Refactor Settings page architecture to allow Addons/Plugins to register top-level Tabs (Settings Tab Extensibility).
- [x] **Core Decoupling**
    - [x] Migrate `handleEntrySaving` logic from `ActivityPubCore` to a dedicated `SavingHook`.
    - [x] Status: **✅ COMPLETE**

- [x] **Manual Pin Placement**
    - [x] Implement Map Click + Reverse Geocoding in LocationSearch.
    - [x] Status: **✅ COMPLETE**

## 2. Infrastructure & Data
- [ ] **Data Payload Augmentation**
- [ ] Create `Place` ActivityPubType registration logic (similar to Note/Article).
- [ ] Define Place Collection blueprint and schema (must handle OSM references, coords, bounding boxes).
- [ ] Configure `places.pub/(node|way|relation)/ID` translation mapping.

## 3. Control Panel Experience
- [ ] Add "Places" child nav item under ActivityPub in CP sidebar.
- [ ] Build Places Listing dashboard.
- [ ] Add "Places" settings tab to ActivityPub Settings page.
- [ ] Implement "New Check-in" vs "New Place" UI flows/buttons.
- [ ] Build custom OSM Location Search Fieldtype.

## 4. Activities & Routing
- [ ] Generate `Arrive` activity for check-ins if specifically requested/enabled.
- [ ] Implement "Check-in" logic: Auto-generate the corresponding Mastodon-compatible `Note` alongside the place when triggered.
- [ ] Implement "New Place" logic: Silent save.
- [ ] Extend Activity dispatcher to support the `Arrive` activity for "Check-ins" (generate only on initial creation alongside `Create`, skip on edits/deletes).
- [x] Listen to Event updates on posts/articles and append `location` property for outbound AP JSON (with `places.pub` reference).
- [x] **Interactive Inbox Maps**
    - [x] Render Leaflet maps in inbox stream for location-aware notes.
    - [x] Implement full map interactivity (pan/zoom).
- [ ] Extend `Actor` profile JSON to include a `places` property linking to a paginated `OrderedCollection`.
- [ ] Create HTTP Endpoint to serve the Actor's paginated `Places` collection based on recency and opt-in/visibility rules.

## 5. Web Views & Frontend
- [ ] Create default Antlers templates for Places.
- [ ] Map internal notes to Places for display.
- [ ] Ensure overriding capability in Statamic theme structure.

### 6. Activity/Content Refactor (Current Session)
- [x] **Component Renaming (Naming Standard)**
    - [x] Rename `CheckInDrawer.vue` -> `CheckInStack.vue`
    - [x] Rename `PlaceDrawer.vue` -> `PlaceStack.vue`
    - [x] Rename `MapServiceSelector.vue` -> `MapServiceSelectorStack.vue`
- [x] **Arrive Activity Specialization**
    - [x] Create `InboxActivityArrive.vue` to unify Arrive presentation.
    - [x] Register `InboxActivityArrive` to the new `inbox-activity-Arrive` hook in `cp.js`.

### Testing & Validation (ProvidesSandbox)
- [ ] Write Unit tests for configurable Places collections and routing.
- [ ] Write Unit tests verifying `Arrive` activity JSON payload serialization.
- [ ] Write Unit tests for paginated `Places` Actor profile collection parsing.