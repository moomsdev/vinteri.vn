import Swal from 'sweetalert2/dist/sweetalert2';
import 'jquery-validation';

window.registerFunction = {
    form             : null,
    initJobSeekerForm: function (formElement) {
        let _this = this;
        _this.form = formElement;

        _this.form.on('submit', function (e) {
            e.preventDefault();
            _this.form.validate({
                rules: {
                    username                  : {
                        required : true,
                        maxlength: 15,
                    },
                    email                     : {
                        required : true,
                        email    : true,
                        maxlength: 50,
                    },
                    password                  : {
                        required : true,
                        maxlength: 50,
                    },
                    password_confirm          : {
                        required : true,
                        maxlength: 50,
                        equalTo  : '[name="password"]',
                    },
                    'extras[last_name]'       : {
                        required: true,
                    },
                    'extras[first_name]'      : {
                        required: true,
                    },
                    'extras[_phone_number]'   : {
                        required : true,
                        maxlength: 13,
                    },
                    'extras[_identify_number]': {
                        required : true,
                        maxlength: 15,
                    },
                }
            });

            if (_this.form.valid()) {
                _this.submitForm();
            }
        });
    },
    initEmployerForm : function (formElement) {
        this.form = formElement;
        this.form.on('submit', function (e) {
            e.preventDefault();

            _this.form.on('submit', function (e) {
                e.preventDefault();
                _this.form.validate({
                    rules: {
                        username                   : {
                            required : true,
                            maxlength: 15,
                        },
                        description                : {
                            required : true,
                            maxlength: 500,
                        },
                        email                      : {
                            required : true,
                            email    : true,
                            maxlength: 50,
                        },
                        password                   : {
                            required : true,
                            maxlength: 50,
                        },
                        password_confirm           : {
                            required : true,
                            maxlength: 50,
                            equalTo  : '[name="password"]',
                        },
                        'extras[last_name]'        : {
                            required: true,
                        },
                        'extras[first_name]'       : {
                            required: true,
                        },
                        'extras[_phone_number]'    : {
                            required : true,
                            maxlength: 13,
                        },
                        'extras[_company_name]'    : {
                            required : true,
                            maxlength: 100,
                        },
                        'extras[_tax_code]'        : {
                            required : true,
                            maxlength: 15,
                        },
                        'extras[_address]'         : {
                            required : true,
                            maxlength: 255,
                        },
                        'extras[_province]': {
                            required: true,
                        },
                        'extras[_career]'          : {
                            required: true,
                        },
                        'extras[_company_size]'    : {
                            required: true,
                        },
                    }
                });

                if (_this.form.valid()) {
                    _this.submitForm();
                }
            });
        });
    },
    submitForm       : function () {
        let _this = this;
        showPageLoader();
        $.ajax({
            url    : _this.form.attr('action'),
            method : _this.form.attr('method'),
            data   : _this.form.serialize(),
            async  : false,
            success: (response) => {
                hidePageLoader();
                if (response.success !== true) {
                    if (typeof response.data === 'string') {
                        Swal.fire('Đăng ký tài khoản không thành công!', data, 'error');
                    }
                }

                Swal.fire('Đăng ký tài khoản thành công!', '', 'success').then(() => {
                    $.pjax({url: response.data.redirect_to, container: '#main_content'});
                });
            },
            error  : (error) => {
                console.log(error);
                Swal.fire('Đăng ký tài khoản không thành công!', '', 'error');
                hidePageLoader();
            },
        });
    }
}
