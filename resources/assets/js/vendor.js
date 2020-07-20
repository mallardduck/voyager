// https://github.com/component/debounce
window.debounce = require('debounce');
Voyager.config.globalProperties.debounce = debounce;

// https://github.com/axios/axios
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.head.querySelector('meta[name="csrf-token"]').content;

// https://github.com/simov/slugify
window.slugify = require('slugify');
Voyager.config.globalProperties.slugify = window.slugify;

// https://github.com/BinarCode/vue2-transitions
import Transitions from 'vue2-transitions';
Voyager.use(Transitions);

// https://github.com/Jexordexan/vue-slicksort
import { HandleDirective } from 'vue-slicksort';
Voyager.directive('sort-handle', HandleDirective);

// https://github.com/katlasik/mime-matcher
import MimeMatcher from 'mime-matcher';
Voyager.config.globalProperties.MimeMatcher = MimeMatcher;

// https://github.com/Akryum/v-tooltip
import { VTooltip } from 'v-tooltip';
VTooltip.options.defaultPlacement = 'bottom';
Voyager.directive('tooltip', VTooltip);

// https://github.com/ndelvalle/v-click-outside
import vClickOutside from 'v-click-outside';
Voyager.use(vClickOutside);