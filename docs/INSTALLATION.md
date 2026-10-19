# Production Operator & Installation Guide

Complete deployment, configuration, and operations manual for running **ActivityPub Places** on Statamic 5.x and 6.x (Laravel 11.x & 12.x).

---

## 📋 System Requirements

| Requirement | Supported Version | Notes |
| :--- | :--- | :--- |
| **ActivityPub Core** | `^1.0` | [`ethernick/statamic-activitypub-core`](https://github.com/ethernick/statamic-activitypub-core) must be installed and configured. |
| **PHP** | `^8.2` \| `^8.3` \| `^8.4` | Extensions required: `curl`, `json`, `mbstring`, `openssl`. |
| **Statamic** | `^5.0` \| `^6.0` | Flat-file Stache content or database-backed. |
| **Laravel** | `^11.0` \| `^12.0` | Queue worker runtime for outbox federation. |
| **External APIs** | OpenStreetMap (Nominatim & Overpass) | Outbound HTTP requests from your server to `nominatim.openstreetmap.org` and `overpass-api.de` must be permitted. |

---

## 🚀 Step-by-Step Installation

### Step 1: Require via Composer

Install the package into your Statamic project:

```bash
composer require ethernick/statamic-activitypub-places
```

### Step 2: Run the Guided Installer

Run the automated installer command:

```bash
php artisan activitypub:places:install
```

The installer performs the following:
1. **Creates `places` Content Collection**: Proposes and provisions the `places` collection in Statamic with nested routing (`/places/{osm_type}/{osm_id}`).
2. **Deploys Blueprint**: Copies `resources/blueprints/places/place.yaml` with OSM identity, coordinates (`latitude`, `longitude`), `osm_name`, and address fields.
3. **Configures Federation Settings**: Injects the `places` configuration into `settings/activitypub.yaml` (`enabled: true`, `federated: true`, `type: Place`).
4. **Publishes CP Assets**: Automatically selects and publishes matching Vue 2 (Statamic 5) or Vue 3 (Statamic 6) Control Panel assets to `public/vendor/ethernick/activitypub-places/`.

---

## ⚙️ Post-Installation Configuration

### OpenStreetMap Nominatim User-Agent (Mandatory)

OpenStreetMap's [Nominatim Usage Policy](https://operations.osmfoundation.org/policies/nominatim/) requires that all automated requests send a valid, identifying `User-Agent` or `Referer` header.

1. Navigate to **Control Panel > ActivityPub > Settings > Places**.
2. Fill in the **Nominatim User-Agent** field with your application name and contact info (e.g. `MyStatamicBlog/1.0 (contact@example.com)`).
3. (Optional) If you self-host Nominatim or use a commercial provider like LocationIQ / Geocode.earth, specify a custom **Nominatim URL** endpoint.

### Mastodon Compatibility Mode

Not all fediverse platforms render ActivityStreams `Arrive` activities. 

To ensure maximum visibility across Mastodon, Pixelfed, and Threads:
- Toggle **Compatibility Mode** to **ON** in the Places settings tab.
- When enabled, posting a check-in creates both an `Arrive` activity for modern clients and a companion `Note` post containing an interactive Markdown link to the venue on OpenStreetMap (`📍 [Venue Name](https://www.openstreetmap.org/way/123456)`).

---

## 🎨 Frontend Template Setup

To display place entries on your public web layout, create or customize your Antlers template at `resources/views/places/show.antlers.html`:

```antlers
<article class="place-entry">
    <header>
        <h1>{{ title }}</h1>
        {{ if osm_name && osm_name != title }}
            <p class="osm-name"><em>Original: {{ osm_name }}</em></p>
        {{ /if }}
        {{ if address }}
            <p class="place-address">📍 {{ address }}</p>
        {{ /if }}
    </header>

    <!-- Embedded Leaflet Map with interactive pin -->
    <div class="place-map">
        {{ activitypub_place:map }}
    </div>

    <!-- External Navigation Links -->
    <nav class="place-navigation">
        <p><strong>Open in Maps:</strong></p>
        <ul>
            {{ activitypub_place:links }}
                <li>
                    <a href="{{ url }}" target="_blank" rel="noopener noreferrer">
                        {{ type }}
                    </a>
                </li>
            {{ /activitypub_place:links }}
        </ul>
    </nav>
</article>
```


---

## 🔄 Re-Publishing Assets & Stubs

If you update Statamic versions or upgrade the addon, you can re-publish assets and blueprints anytime:

```bash
# Re-publish Control Panel assets only
php artisan vendor:publish --tag=activitypub-places --force
```
