import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import flushPromises from 'flush-promises';
import LocationSearch from '../components/location/LocationSearch.vue';

// Mock Leaflet - factory is hoisted, so we define all mocks internally
vi.mock('leaflet', () => {
    const mockMap = {
        setView: vi.fn(),
        remove: vi.fn(),
        invalidateSize: vi.fn(),
        addLayer: vi.fn(),
        removeLayer: vi.fn(),
        fitBounds: vi.fn(),
        tileLayer: vi.fn().mockReturnValue({
            addTo: vi.fn()
        })
    };

    return {
        default: {
            map: vi.fn().mockReturnValue(mockMap),
            tileLayer: vi.fn().mockReturnValue({
                addTo: vi.fn()
            }),
            marker: vi.fn().mockReturnValue({
                addTo: vi.fn(),
                setLatLng: vi.fn()
            }),
            circleMarker: vi.fn().mockReturnValue({
                addTo: vi.fn()
            }),
            geoJSON: vi.fn().mockReturnValue({
                addTo: vi.fn(),
                getBounds: vi.fn().mockReturnValue({
                    toBBoxString: vi.fn(),
                    getNorthEast: vi.fn(),
                    getSouthWest: vi.fn()
                })
            }),
            Icon: {
                Default: {
                    prototype: {},
                    mergeOptions: vi.fn()
                }
            }
        }
    };
});

// Mock Statamic globals
global.cp_url = vi.fn((path) => `/cp/${path}`);
global.Statamic = {
    $toast: {
        success: vi.fn(),
        error: vi.fn()
    }
};

describe('LocationSearch.vue', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mount(LocationSearch, {
            mocks: {
                $axios: {
                    get: vi.fn()
                }
            },
            attachTo: document.body 
        });
        
        // Setup a mock map for the instance directly to avoid ref issues in unit tests
        wrapper.vm.map = {
            setView: vi.fn(),
            remove: vi.fn(),
            invalidateSize: vi.fn(),
            addLayer: vi.fn(),
            removeLayer: vi.fn(),
            fitBounds: vi.fn(),
            tileLayer: vi.fn().mockReturnValue({
                addTo: vi.fn()
            })
        };
    });

    it('displays the "type a place name" message initially', () => {
        expect(wrapper.text()).toContain('Type a place name to begin.');
    });

    it('shows a loading spinner and searching message during search', async () => {
        await wrapper.setData({ query: 'Paris' });
        wrapper.vm.$axios.get.mockReturnValue(new Promise(() => {}));
        wrapper.vm.search();
        await wrapper.vm.$nextTick();
        expect(wrapper.vm.loading).toBe(true);
        expect(wrapper.find('.loading').exists()).toBe(true);
    });

    it('displays "No results found" only after search finishes with no results', async () => {
        wrapper.setData({ query: 'NonExistentPlace' });
        wrapper.vm.$axios.get.mockResolvedValue({ data: { results: [] } });
        await wrapper.vm.search();
        await flushPromises();
        expect(wrapper.text()).toContain('No results found for "NonExistentPlace"');
    });

    it('emits "selected" event when a result is confirmed', async () => {
        const result = { place_id: 1, lat: '48.8', lon: '2.3', display_name: 'Paris', name: 'Paris' };
        await wrapper.setData({ 
            results: [result],
            selectedResult: result
        });
        await wrapper.vm.$nextTick();

        const button = wrapper.find('button.btn-primary');
        expect(button.exists()).toBe(true);
        await button.trigger('click');

        expect(wrapper.emitted().selected).toBeTruthy();
        expect(wrapper.emitted().selected[0][0]).toEqual(result);
    });

    it('renders GeoJSON polygons when available', async () => {
        const L = (await import('leaflet')).default;
        const result = { 
            place_id: 1, 
            lat: '48.8', 
            lon: '2.3', 
            display_name: 'Paris', 
            geojson: { type: 'Polygon', coordinates: [] } 
        };

        const mockBounds = { getBounds: vi.fn().mockReturnValue({}) };
        L.geoJSON.mockReturnValue({
            addTo: vi.fn().mockReturnValue(mockBounds),
            getBounds: vi.fn().mockReturnValue({})
        });

        await wrapper.vm.selectResult(result);
        
        expect(L.geoJSON).toHaveBeenCalled();
        expect(wrapper.vm.map.fitBounds).toHaveBeenCalled();
    });
});
