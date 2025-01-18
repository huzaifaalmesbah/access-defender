jQuery(document).ready(function($) {
    // Handle switch toggle animation
    $('.switch input[type="checkbox"]').on('change', function() {
        $(this).next('.slider').toggleClass('checked', this.checked);
    });
});
