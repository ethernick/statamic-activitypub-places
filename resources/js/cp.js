import '../css/cp.css';
import SettingsPlaces from './components/settings/SettingsPlaces.vue';
import LocationSearch from './components/location/LocationSearch.vue';
import LocationFieldtype from './components/location/LocationFieldtype.vue';
import PlacesDashboard from './components/place/PlacesDashboard.vue';
import InboxNewPlace from './components/inbox/InboxNewPlace.vue';
import InboxNewCheckIn from './components/inbox/InboxNewCheckIn.vue';
import PlaceInboxModals from './components/place/PlaceInboxModals.vue';
import PlaceSummary from './components/place/PlaceSummary.vue';
import MapServiceSelectorStack from './components/map/MapServiceSelectorStack.vue';
import InboxPlaceMap from './components/inbox/InboxPlaceMap.vue';
import InboxActivityArrive from './components/inbox/InboxActivityArrive.vue';

const bootHooks = () => {
    if (typeof Statamic !== 'undefined' && Statamic.$activitypub) {
        // Register Inbox Hooks
        Statamic.$activitypub.hooks.register('inbox-new-dropdown', {
            component: InboxNewCheckIn,
            priority: 12 // Priority: Notes (10), Checkin (12), Polls (15), Place (18)? 
        });

        Statamic.$activitypub.hooks.register('inbox-new-dropdown', {
            component: InboxNewPlace,
            priority: 15
        });

        Statamic.$activitypub.hooks.register('inbox-modals', {
            component: PlaceInboxModals,
            priority: 15
        });

        Statamic.$activitypub.hooks.register('inbox-note-content', {
            component: PlaceSummary,
            priority: 15
        });

        // Render an inline Leaflet map for any note that has location data
        Statamic.$activitypub.hooks.register('inbox-note-content', {
            component: InboxPlaceMap,
            priority: 20
        });

        // Register the Arrive Activity Specialized Presentation
        Statamic.$activitypub.hooks.register('inbox-activity-Arrive', {
            component: InboxActivityArrive,
            priority: 10
        });
    } else if (typeof Statamic !== 'undefined') {
        setTimeout(bootHooks, 10);
    }
};

const bootComponents = () => {
    if (typeof Statamic !== 'undefined') {
        Statamic.booting(() => {
            Statamic.$components.register('settings-places-tab', SettingsPlaces);
            Statamic.$components.register('location-search', LocationSearch);
            Statamic.$components.register('location-fieldtype', LocationFieldtype);
            Statamic.$components.register('places-dashboard', PlacesDashboard);
            Statamic.$components.register('map-service-selector-stack', MapServiceSelectorStack);
        });
    } else {
        setTimeout(bootComponents, 10);
    }
};

bootComponents();
bootHooks();
