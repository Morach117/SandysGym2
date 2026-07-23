    $(document).ready(function() {

        $('#userDropdown').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#userMenu').toggleClass('show');
        });

        $('#userDropdownMobile').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#userMenuMobile').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$('.dropdown').is(e.target) && $('.dropdown').has(e.target).length === 0) {
                $('.dropdown-menu').removeClass('show');
            }
        });

    });