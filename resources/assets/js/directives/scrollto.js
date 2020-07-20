Voyager.directive('scroll-to', {
    inserted: function (el, binding) {
        el.addEventListener('click', function (e) {
            var element = document.getElementById('top');
            if (binding.value !== '') {
                element = document.getElementById(binding.value);
            }
            if (element) {
                element.scrollIntoView({
                    left: 0,
                    block: 'start',
                    behavior: 'smooth'
                });
            } else {
                console.warn('Element with ID "'+binding.value+'" was not found!');
            }
            if (binding.modifiers.prevent === true) {
                e.preventDefault();
            }
            if (binding.modifiers.stop === true) {
                e.stopPropagation();
            }
        });
    }
});