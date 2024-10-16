$(document).ready(function() {

    /**
     * сортирует изображения
     * */
    $('.image-preview-container-o').sortable({
        stop(ev, ui) {
            let sort = [];
            $('.image-preview-container-o .image-preview-o').each(function(index, element){
                sort.push($(element).attr('data-id'));
            });
            $.ajax({
                url: '/admin/images/save-sort',
                method: 'post',
                data: {ids: JSON.stringify(sort)},
                success(response) {
                    if(response.result) {
                        displaySuccessMessage(response.message)
                    }
                },
                error(e) {
                    console.log('error', e)
                }
            });
        }
    });



    /**
     * сворачивает/разворачивает карточку
     * */
    $('body').on('click', '.card-header-o', function(e) {
        //e.preventDefault();
        let target = e.target;
        if($(target).is('.card-header-o')) {
            let parent = $(this).closest('.card-img-o');
            let body = parent.find('.card-body-o');
            let icon = $(this).find('.bi')
            if(body.is(':visible')) {
                body.slideUp();
                icon.removeClass('bi-chevron-up').addClass('bi-chevron-down')
            }
            else {
                body.slideDown();
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-up')
            }
        }

    });

    /**
     * в форме товара показывает форму атрибутов по их типу
     * */
    $('body').on('click', '.show-attributes-for-type-o', function(e) {
        e.preventDefault();
        let self = $(this);
    });

    /**
     * Открывает нужный таб по ссылке
     * */
    if($('#myTab').length) {
        let location = window.location;
        let hash = location.hash;
        if(hash.length) {
            let tab = $('.nav-item a[href="' + hash + '"]');
            if(tab.length) {
                tab.trigger('click');
            }
        }
    }


    initPlugins()
})
