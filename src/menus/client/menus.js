jQuery(
    function ($)
    {
        $(document).on('mouseenter', '.menu__item',
            function ()
            {
                var dropdown = $(this).children('.menu_is_dropdown');
                dropdown.show();
            }
        );

        $(document).on('mouseleave', '.menu__item',
            function ()
            {
                var dropdown = $(this).children('.menu_is_dropdown');
                dropdown.hide();
            }
        );
    }
);