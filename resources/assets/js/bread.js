let bread_components = [
    'Bread/Browse',
    'Bread/EditAdd',
    'Bread/Read',
];

bread_components.forEach(function (component) {
    var name = component.substring(component.lastIndexOf('/') + 1);
    var name = name.match(/[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/g)
                    .map(x => x.toLowerCase())
                    .join('-');
    Voyager.component('bread-'+name, require('../components/'+component+'.vue').default);
});

Voyager.component('bread-builder-browse', require('../components/Builder/Browse.vue').default);
Voyager.component('bread-builder-edit-add', require('../components/Builder/EditAdd.vue').default);
Voyager.component('bread-builder-view', require('../components/Builder/View.vue').default);
Voyager.component('bread-builder-list', require('../components/Builder/List.vue').default);
Voyager.component('bread-builder-validation', require('../components/Builder/ValidationForm.vue').default);