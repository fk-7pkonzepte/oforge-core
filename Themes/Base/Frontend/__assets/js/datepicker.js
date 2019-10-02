if (typeof Oforge !== 'undefined') {
    Oforge.register({
        name: 'datepicker',
        selector: '#datepicker',
        otherNotRequiredContent: 'some other content, that we can define and that is not required',
        init: function () {
            var picker = new Pikaday({
                field: document.getElementById('datepicker'),
                maxDate: new Date(),
                minDate: new Date() - 50,
            });
            console.log(picker);
        }
    });
} else {
    console.warn("Oforge is not defined. Module cannot be registered.");
}