import { createApp } from '../../../node_modules/vue/dist/vue.esm-bundler';
import Store from './store';

const Voyager = createApp({
    
});

Voyager.use(Store);

window.Voyager = Voyager;

require('./vendor');
require('./helper');
require('./notify');
require('./bread');
require('./multilanguage');
require('./formfields');
require('./layout');
require('./ui');

Voyager.component('settings-manager', require('../components/Settings/Manager.vue').default);
Voyager.component('plugins-manager', require('../components/Plugins/Manager.vue').default);
Voyager.component('login', require('../components/Auth/Login.vue').default);

Voyager.mount('#voyager');