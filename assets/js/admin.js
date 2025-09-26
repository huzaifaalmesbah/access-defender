jQuery(document).ready(function($) {
    // Handle switch toggle animation
    $('.switch input[type="checkbox"]').on('change', function() {
        $(this).next('.slider').toggleClass('checked', this.checked);
    });

    // Provider mode switching - v1.1.0 Smart Interface
    $('.provider-mode-radio').on('change', function() {
        var mode = $(this).val();
        
        if (mode === 'free') {
            $('.free-providers-section').slideDown(300);
            $('.paid-providers-section').slideUp(300);
            $('#no-api-key-needed').slideDown(300);
            $('.api-key-field').stop(true, true).slideUp(300);
            $('.free-providers-header').fadeIn(200);
            $('.paid-providers-header').fadeOut(200);
        } else if (mode === 'paid') {
            $('.free-providers-section').slideUp(300);
            $('.paid-providers-section').slideDown(300, function() {
                // Only update API key fields after paid section is fully shown
                updateApiKeyFields();
            });
            $('#no-api-key-needed').slideUp(300);
            $('.free-providers-header').fadeOut(200);
            $('.paid-providers-header').fadeIn(200);
        }
    });

    // Provider card selection
    $('.provider-card').on('click', function() {
        var $card = $(this);
        var $input = $card.find('input[type="checkbox"], input[type="radio"]');
        
        if ($input.attr('type') === 'checkbox') {
            // Free provider - toggle selection
            $input.prop('checked', !$input.prop('checked'));
            $card.toggleClass('selected', $input.prop('checked'));
        } else {
            // Paid provider - single selection
            $('.paid-providers-section .provider-card').removeClass('selected');
            $card.addClass('selected');
            $input.prop('checked', true);
            updateApiKeyFields();
        }
    });

    // Prevent card click when clicking directly on input
    $('.provider-card input').on('click', function(e) {
        e.stopPropagation();
        var $card = $(this).closest('.provider-card');
        $card.toggleClass('selected', $(this).prop('checked'));
        
        if ($(this).attr('type') === 'radio') {
            $('.paid-providers-section .provider-card').removeClass('selected');
            $card.addClass('selected');
            // Add small delay to ensure selection is processed
            setTimeout(function() {
                updateApiKeyFields();
            }, 100);
        }
    });

    // Also handle direct radio button changes
    $('.paid-providers-section input[type="radio"]').on('change', function() {
        if ($(this).is(':checked')) {
            setTimeout(function() {
                updateApiKeyFields();
            }, 100);
        }
    });

    // Update API key fields based on selected paid provider
    function updateApiKeyFields() {
        var selectedProvider = $('.paid-providers-section input[type="radio"]:checked').val();
        
        // Hide all API key fields first
        $('.api-key-field').stop(true, true).slideUp(200);
        
        // Show the selected provider's API key field
        if (selectedProvider) {
            setTimeout(function() {
                $('.api-key-' + selectedProvider).stop(true, true).slideDown(300);
            }, 250); // Small delay to ensure hide animation completes
        }
    }

    // Initialize API key fields visibility
    function initializeApiKeyFields() {
        var currentMode = $('.provider-mode-radio:checked').val();
        var selectedProvider = $('.paid-providers-section input[type="radio"]:checked').val();
        
        if (currentMode === 'paid' && selectedProvider) {
            // Show the correct API key field without animation on page load
            $('.api-key-' + selectedProvider).show();
        } else {
            // Hide all API key fields
            $('.api-key-field').hide();
        }
    }

    // API Key validation for v1.1.0
    $(document).on('blur', '.api-key-input', function() {
        var $input = $(this);
        var apiKey = $input.val().trim();
        var provider = $input.data('provider');
        var $status = $('#status-' + provider);

        if (apiKey === '') {
            $status.text('');
            return;
        }

        $status.text('Validating...').css('color', '#666');

        // Make AJAX request to validate API key
        $.ajax({
            url: accessdefender_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessdefender_validate_api_key',
                provider: provider,
                api_key: apiKey,
                nonce: accessdefender_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $status.text('✓ Valid').css('color', '#46b450');
                } else {
                    $status.text('✗ Invalid').css('color', '#dc3232');
                }
            },
            error: function() {
                $status.text('✗ Error validating').css('color', '#dc3232');
            }
        });
    });

    // Provider status dashboard
    if ($('.provider-status').length) {
        loadProviderStatus();
        setInterval(loadProviderStatus, 60000); // Refresh every minute
    }

    function loadProviderStatus() {
        $.ajax({
            url: accessdefender_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'accessdefender_provider_status',
                nonce: accessdefender_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProviderStatus(response.data);
                }
            }
        });
    }

    function updateProviderStatus(data) {
        $.each(data, function(provider, status) {
            var $statusElement = $('.provider-status-' + provider);
            $statusElement.find('.status-indicator')
                .removeClass('healthy degraded error')
                .addClass(status.status);
            $statusElement.find('.usage-count').text(status.monthly_usage);
            $statusElement.find('.success-count').text(status.total_success);
            $statusElement.find('.failed-count').text(status.total_failed);
        });
    }

    // Initialize interface state on page load
    function initializeInterface() {
        var currentMode = $('.provider-mode-radio:checked').val();
        
        if (currentMode === 'paid') {
            $('.free-providers-section').hide();
            $('.paid-providers-section').show();
            $('#no-api-key-needed').hide();
            $('.free-providers-header').hide();
            $('.paid-providers-header').show();
        } else {
            $('.free-providers-section').show();
            $('.paid-providers-section').hide();
            $('#no-api-key-needed').show();
            $('.free-providers-header').show();
            $('.paid-providers-header').hide();
        }
        
        // Initialize API key fields after setting up sections
        initializeApiKeyFields();
    }
    
    // Call initialization
    initializeInterface();

    // Load provider status on page load
    if ($('.provider-status').length) {
        loadProviderStatus();
    }
});
