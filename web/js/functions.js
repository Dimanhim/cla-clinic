




    function addPreloader() {
        setTimeout(function() {
            $('.loader-block').addClass('loader');
        }, 500);
    }
    function removePreloader() {
        setTimeout(function() {
            $('.loader-block').removeClass('loader');
        }, 500)
    }

    function displaySuccessMessage(message) {
        $('.info-message').text(message).fadeIn();
        setTimeout(function() {
            $('.info-message').text('').fadeOut();
        }, 5000)
    }
    function displayErrorMessage(message) {
        $('.info-message').addClass('error').text(message).fadeIn();
        setTimeout(function() {
            $('.info-message').text('').fadeOut();
        }, 5000)
    }



    function initPlugins() {
        $('.chosen').chosen()
        $(".select-time").inputmask({"mask": "99:99"});
        $(".phone-mask").inputmask({"mask": "+7 (999) 999-99-99"});
    }










