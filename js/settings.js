$.wa_blog = $.extend(true, $.wa_blog,
        {
            plugins_recommendproducts: {
                options: {
                    loader: '<i class="b-ajax-status-loading icon16 loading"></i>'
                },
                ajaxInit: function () {
                    var self = this;
                    $('#plugin-recommendproducts-form').live('submit.plugins_recommendproducts', function (eventObject) {
                        return self.submitHandler.apply(self, [this, eventObject]);
                    });

                },
                submitHandler: function (element) {
                    var form = $(element);
                    var data = form.serialize();
                    var self = this;
                    form.find(':input').attr('disabled', true);
                    form.find(':submit').after(this.options.loader);

                    $.wa.errorHandler = function (xhr) {
                        if ((xhr.status >= 500) || (xhr.status == 0)) {
                            return false;
                        }
                        return true;
                    };

                    var url = $(element).attr('action');
                    $.ajax({
                        url: url,
                        data: data,
                        type: 'post',
                        dataType: 'json',
                        success: function (response) {
                            $('.b-ajax-status-loading').remove();
                            form.find(':submit').after(' <span class="green response-message"><i class="icon16 yes" style="vertical-align:middle"></i>' + response.data.message + '</span>');
                            form.find(':submit').removeAttr('disabled');
                            setTimeout(function () {
                                form.find('span.response-message').remove();
                            }, 3000);

                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            //TODO
                        }
                    });
                    return false;
                }
            }
        });

