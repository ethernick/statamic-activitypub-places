# ActivityPub Places Troubleshooting & Operations Runbook

A diagnostic matrix, resolution guide, and operations runbook for **ActivityPub Places** on Statamic 5.x and 6.x.

---

## 🧭 Quick Diagnostic Steps

1. **Verify Addon Registration:**
   In your terminal, test that the `Place` type is active in the ActivityPub registry:
   ```bash
   php artisan tinker --execute="dump(app(\Ethernick\ActivityPubCore\Services\ActivityPubTypes::class)->all()['Place'] ?? 'NOT_REGISTERED');"
   ```

2. **Check Nominatim User-Agent:**
   Verify that your User-Agent is saved in settings:
   ```bash
   php artisan tinker --execute="dump(\Statamic\Facades\YAML::file(resource_path('settings/activitypub.yaml'))->parse()['nominatim_user_agent'] ?? 'MISSING');"
   ```

3. **Verify Places Collection Routing:**
   Ensure the `places` collection uses the nested OSM format:
   ```bash
   php artisan tinker --execute="dump(\Statamic\Facades\Collection::findByHandle('places')->route('{osm_type}/{osm_id}'));"
   ```

---

## 🧩 Troubleshooting Matrix

### 1. OpenStreetMap & Nominatim Search Issues

| Symptom | Probable Cause | Resolution |
| :--- | :--- | :--- |
| **`403 Forbidden` from Nominatim** | Missing or generic `User-Agent` string. OpenStreetMap blocks empty, default cURL, or generic agent strings. | Navigate to **ActivityPub > Settings > Places** and provide an explicit User-Agent containing your domain or email (e.g. `MySite/1.0 (admin@mydomain.com)`). |
| **Search returns zero results for known venue** | Nominatim rate limits or temporary timeout. | 1. Check your server's outbound HTTP latency.<br>2. Try searching by address or city rather than venue name.<br>3. Drop a pin on the map instead to reverse-geocode coordinates. |
| **Reverse geocode doesn't identify exact building** | No discrete OpenStreetMap building node exists at those exact coordinates. | The pin drop modal automatically creates an "Exact Coordinate Point" entry with latitude and longitude even if no OSM business entity is tagged at that point. |

---

### 2. Control Panel Map & Leaflet Issues

| Symptom | Probable Cause | Resolution |
| :--- | :--- | :--- |
| **Map is grey or tiles do not load** | Missing OpenStreetMap tile connectivity or Content Security Policy (CSP) blocking tile domains. | Ensure your CSP permits `https://*.tile.openstreetmap.org` in `img-src` and `connect-src`. |
| **Map renders distorted or collapsed (0px height)** | Leaflet map initialized while container was hidden inside a tab or drawer. | The component triggers `map.invalidateSize()` after animation frames. If overriding CSS, ensure the container has an explicit CSS `height` (e.g. `height: 380px`). |
| **Tiles load slowly in CP** | Heavy usage of standard OSM tile server. | Configure a custom tile layer URL in settings or proxy tiles through a local CDN if serving high-frequency traffic. |

---

### 3. Check-In & Federation Inquiries

| Symptom | Probable Cause | Resolution |
| :--- | :--- | :--- |
| **Mastodon shows check-in without map link** | Compatibility Mode is disabled. | Mastodon's native frontend does not parse ActivityStreams `Arrive` activities. Turn on **Compatibility Mode** in **ActivityPub > Settings > Places** so a companion `Note` with an OpenStreetMap markdown link is generated. |
| **Arrive activity stuck in outbox queue** | Queue worker not processing `activitypub-outbox`. | Run `php artisan queue:work --queue=activitypub-outbox,default` or check the Queue Dashboard in **ActivityPub > Queue**. |
| **Inbox note does not display interactive map** | Incoming ActivityPub post is missing `location` property with `latitude` and `longitude`. | ActivityPub Places automatically inspects payloads for `location.latitude` and `location.longitude`. If remote servers omit coordinates, a map cannot be generated. |

---

## 🛠️ Maintenance & Resetting

### Rebuilding Stache Cache
If place entries aren't appearing in searches or collection queries:
```bash
php artisan statamic:stache:clear
php artisan statamic:stache:warm
```

### Re-publishing Control Panel Assets
If updating between Statamic 5 and 6:
```bash
php artisan vendor:publish --tag=activitypub-places --force
```
