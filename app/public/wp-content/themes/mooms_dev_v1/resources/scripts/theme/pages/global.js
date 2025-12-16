import "jquery-pjax/jquery.pjax";

window.globalFunctions = {
    init: function () {
        hidePageLoader();

        let jobSeekerRegisterForm = $('#job_seeker_register_form');
        if (jobSeekerRegisterForm) {
            registerFunction.initJobSeekerForm(jobSeekerRegisterForm);
        }

        $(document).on("click", '.icon_hamberger', function () {
            $("#drop_down").mmenu({
                offCanvas: {
                    position: "right",
                }
            });
            $("#drop_down").mmenu();
            var API = $("#drop_down").data("mmenu");
            API.open();
        });

        $(document).on("click", '#mm-blocker', function () {
        })

        // menu-mobile
        $('#js-job-slider').slick({
            infinite: true,
            slidesToShow: 1,
            dots: true,
            arrows: false,
            slidesToScroll: 1
        });

        $('#js-mv-slider').slick({
            infinite: true,
            autoplay: true,
            slidesToShow: 1,
            dots: true,
            arrows: false,
            slidesToScroll: 1
        });

        $('.p-video-item__link').click(function (e) {
            e.preventDefault();
            $('#video-js').attr("src", $(this).attr("href") + '?autoplay=1');
        })
        $('[data-dismiss="modal"]').click(function () {
            $('#video-js').attr('src', '');
        });

        $('.c-filter-select').niceSelect();

        $('.c-inputFile').change(function (e) {
            var fileName = e.target.files[0].name;
            $('#js-url-file').html(fileName);
        });
        $(window).scroll(function () {
            if ($(this).scrollTop() > $('.p-header-info').innerHeight()) {
                $('.p-header-menu').addClass('is-fixed');
            } else {
                $('.p-header-menu').removeClass('is-fixed');
            }
        });
        $(".js-goApply").click(function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: ($("#jobApply").offset().top - 100)
            }, 500);
        });


        $('#js-profile-slider').slick({
            dots: true,
            infinite: true,
            speed: 300,
            slidesToShow: 5,
            arrows: false,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1,
                        infinite: true,
                        dots: true
                    }
                },
                {
                    breakpoint: 940,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 730,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 490,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
                // You can unslick at a given breakpoint now by adding:
                // settings: "unslick"
                // instead of a settings object
            ]
        });

    }
};

// Enable pjax
window.suggestTimeout = null;

$(function () {
    if ($.support.pjax) {
        $.pjax.defaults.timeout = 1000; // time in milliseconds
    }
});

$(document).pjax('a', '#main_content');

// $(document).on('submit', 'form', function (event) {
//     $.pjax.submit(event, '#main_content');
// });

$(document).on('pjax:send', function () {
    showPageLoader();
});

$(document).on('pjax:complete', function () {
    globalFunctions.init();
});
