import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


$('#reload').click(function () {
    $.ajax({
        type: 'GET',
        url: 'reload-captcha',
        success: function (data) {
            $(".captcha span").html(data.captcha);
        }
    });
});
