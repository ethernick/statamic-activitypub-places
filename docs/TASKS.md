# Active Tasks

> **Legend**
> - [ ] To Do
> - [/] In Progress
> - [x] Done

## Current Priority

### Core Architecture & Data Models
- [ ] Create `Place` Statamic Blueprint base (`places/place.yaml`).
  - Fields needed: Name, Latitude, Longitude, Radius, OSM ID, OSM Type (node/way/relation).
- [ ] Implement Configuration System to map Statamic Collections to "Places" object types (Custom Naming, Enabled, Federate).
- [ ] Create `Location` statamic Fieldtype (Vue component for Control Panel).
  - Feature: Search via OSM Nominatim API.
  - Feature: "Near Me" HTML5 geolocation lookup.
  - Feature: Fallback creation (custom location/zoom out).
  - Feature: "Save to Collection" dropdown to route the new Place entry to the correct collection.

### ActivityPub Federation & Injection
- [ ] Extend Activity dispatcher to support the `Arrive` activity for "Check-ins" (generate only on initial creation alongside `Create`, skip on edits/deletes).
- [ ] Listen to Event updates on posts/articles and append `location` property for outbound AP JSON (with `places.pub` reference).
- [ ] Extend `Actor` profile JSON to include a `places` property linking to a paginated `OrderedCollection`.
- [ ] Create HTTP Endpoint to serve the Actor's paginated `Places` collection based on recency and opt-in/visibility rules.

### Testing & Validation (ProvidesSandbox)
- [ ] Write Unit tests for configurable Places collections and routing.
- [ ] Write Unit tests verifying `Arrive` activity JSON payload serialization.
- [ ] Write Unit tests for paginated `Places` Actor profile collection parsing.
