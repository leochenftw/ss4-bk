(function($)
{
    $.entwine('ss', function($)
    {
        $('.btn-vue-prerenderer').entwine(
        {
            // onmatch: function(e)
            // {

            // }
            onclick: function()
            {
                var error_handler   =   function(event, jqxhr, settings, thrownError)
                                        {
                                            $(document).unbind('ajaxError', error_handler);
                                            window.location.reload();
                                        },
                    success_handler =   function( event, request, settings )
                                        {
                                            $(document).unbind('ajaxSuccess', success_handler);
                                            window.location.reload();
                                        };

                $(document).ajaxError(error_handler).ajaxSuccess(success_handler);
                this._super();
            }
        });
    });
}(jQuery));
