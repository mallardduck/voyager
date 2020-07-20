let formfields = [
    'Checkboxes',
    'DynamicSelect',
    'MediaPicker',
    'Number',
    'Password',
    'Radios',
    'Relationship',
    'Select',
    'SimpleArray',
    'Slug',
    'Tags',
    'Text',
];

formfields.forEach(function (formfield) {
    var name = formfield.match(/[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/g)
                        .map(x => x.toLowerCase())
                        .join('-');
    Voyager.component('formfield-'+name+'-browse', require('../components/Formfields/'+formfield+'/Browse.vue').default);
    Voyager.component('formfield-'+name+'-read', require('../components/Formfields/'+formfield+'/Read.vue').default);
    Voyager.component('formfield-'+name+'-edit-add', require('../components/Formfields/'+formfield+'/EditAdd.vue').default);
    Voyager.component('formfield-'+name+'-builder', require('../components/Formfields/'+formfield+'/Builder.vue').default);
});

Voyager.component('key-value-form', require('../components/Formfields/KeyValueForm.vue').default);
Voyager.component('array-form', require('../components/Formfields/ArrayForm.vue').default);