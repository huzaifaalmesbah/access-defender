jQuery(document).ready(function($) {
    // Tab functionality
    $('.access-defender-container .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).data('tab');
        
        // Remove active class from all tabs and tab panes
        $('.access-defender-container .nav-tab').removeClass('nav-tab-active');
        $('.access-defender-container .tab-pane').removeClass('active');
        
        // Add active class to clicked tab and corresponding pane
        $(this).addClass('nav-tab-active');
        $('.access-defender-container #' + targetTab + '-tab').addClass('active');
        
        // Store active tab in localStorage for persistence
        localStorage.setItem('accessDefenderActiveTab', targetTab);
    });
    
    // Restore active tab from localStorage
    var activeTab = localStorage.getItem('accessDefenderActiveTab');
    if (activeTab) {
        $('.access-defender-container .nav-tab[data-tab="' + activeTab + '"]').click();
    }

    // Handle switch toggle animation
    $('.access-defender-container .switch input[type="checkbox"]').on('change', function() {
        $(this).next('.slider').toggleClass('checked', this.checked);
    });

    // Provider mode switching - v1.1.0 Smart Interface
    $('.access-defender-container .provider-mode-radio').on('change', function() {
        var mode = $(this).val();
        
        if (mode === 'free') {
            $('.access-defender-container .free-providers-section').slideDown(300);
            $('.access-defender-container .paid-providers-section').slideUp(300);
            $('.access-defender-container #no-api-key-needed').slideDown(300);
            $('.access-defender-container .api-key-field').stop(true, true).slideUp(300);
            $('.access-defender-container .free-providers-header').fadeIn(200);
            $('.access-defender-container .paid-providers-header').fadeOut(200);
        } else if (mode === 'paid') {
            $('.access-defender-container .free-providers-section').slideUp(300);
            $('.access-defender-container .paid-providers-section').slideDown(300, function() {
                // Only update API key fields after paid section is fully shown
                updateApiKeyFields();
            });
            $('.access-defender-container #no-api-key-needed').slideUp(300);
            $('.access-defender-container .free-providers-header').fadeOut(200);
            $('.access-defender-container .paid-providers-header').fadeIn(200);
        }
    });

    // Provider card selection
    $('.access-defender-container .provider-card').on('click', function() {
        var $card = $(this);
        var $input = $card.find('input[type="checkbox"], input[type="radio"]');
        
        if ($input.attr('type') === 'checkbox') {
            // Free provider - toggle selection
            $input.prop('checked', !$input.prop('checked'));
            $card.toggleClass('selected', $input.prop('checked'));
        } else {
            // Paid provider - single selection
            $('.access-defender-container .paid-providers-section .provider-card').removeClass('selected');
            $card.addClass('selected');
            $input.prop('checked', true);
            updateApiKeyFields();
        }
    });

    // Prevent card click when clicking directly on input
    $('.access-defender-container .provider-card input').on('click', function(e) {
        e.stopPropagation();
        var $card = $(this).closest('.provider-card');
        $card.toggleClass('selected', $(this).prop('checked'));
        
        if ($(this).attr('type') === 'radio') {
            $('.access-defender-container .paid-providers-section .provider-card').removeClass('selected');
            $card.addClass('selected');
            updateApiKeyFields();
        }
    });

    // Also handle direct radio button changes
    $('.access-defender-container .paid-providers-section input[type="radio"]').on('change', function() {
        if ($(this).is(':checked')) {
            updateApiKeyFields();
        }
    });

    // Update API key fields based on selected paid provider
    function updateApiKeyFields() {
        var selectedProvider = $('.access-defender-container .paid-providers-section input[type="radio"]:checked').val();
        
        // Hide all API key fields first
        $('.access-defender-container .api-key-field').stop(true, true).slideUp(200);
        
        // Show the selected provider's API key field
        if (selectedProvider) {
            setTimeout(function() {
                $('.access-defender-container .api-key-' + selectedProvider).stop(true, true).slideDown(300);
            }, 250); // Small delay to ensure hide animation completes
        }
    }

    // Initialize API key fields visibility
    function initializeApiKeyFields() {
        var currentMode = $('.access-defender-container .provider-mode-radio:checked').val();
        var selectedProvider = $('.access-defender-container .paid-providers-section input[type="radio"]:checked').val();
        
        if (currentMode === 'paid' && selectedProvider) {
            // Show the correct API key field without animation on page load
            $('.access-defender-container .api-key-' + selectedProvider).show();
        } else {
            // Hide all API key fields
            $('.access-defender-container .api-key-field').hide();
        }
    }

    // API Key validation - on link click
    $(document).on('click', '.access-defender-container .api-key-validate-link', function(e) {
        e.preventDefault();
        var provider = $(this).data('provider');
        var $input = $('.access-defender-container .api-key-' + provider + ' .api-key-input');
        var apiKey = $input.val().trim();
        var $status = $('.access-defender-container #status-' + provider);

        if (apiKey === '') {
            $status.text('');
            return;
        }

        $status.text('Validating...').css('color', '#666');

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
                if (response.success && response.data === true) {
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

    // Debounced auto-validate on input with in-flight aborting and ajaxurl fallback
    var validateTimers = {};
    var inflightXhrs = {};
    $(document).on('input', '.access-defender-container .api-key-input', function() {
        var $input = $(this);
        var provider = $input.data('provider');
        var apiKey = $input.val().trim();
        var $status = $('.access-defender-container #status-' + provider);
        var ajaxUrl = (window.accessdefender_admin && accessdefender_admin.ajaxurl) ? accessdefender_admin.ajaxurl : (window.ajaxurl || '/wp-admin/admin-ajax.php');

        if (!provider) return;

        clearTimeout(validateTimers[provider]);
        if (apiKey === '') {
            $status.text('');
            return;
        }

        validateTimers[provider] = setTimeout(function() {
            $status.text('Validating...').css('color', '#666');
            // Abort any in-flight request for this provider
            if (inflightXhrs[provider] && inflightXhrs[provider].readyState !== 4) {
                try { inflightXhrs[provider].abort(); } catch (e) {}
            }

            inflightXhrs[provider] = $.ajax({
                url: ajaxUrl,
                type: 'POST',
                cache: false,
                data: {
                    action: 'accessdefender_validate_api_key',
                    provider: provider,
                    api_key: apiKey,
                    nonce: accessdefender_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data === true) {
                        $status.text('✓ Valid').css('color', '#46b450');
                    } else {
                        $status.text('✗ Invalid').css('color', '#dc3232');
                    }
                },
                error: function(xhr) {
                    if (xhr && xhr.statusText === 'abort') { return; }
                    var msg = '✗ Error validating';
                    if (xhr && xhr.status) { msg += ' (' + xhr.status + ')'; }
                    $status.text(msg).css('color', '#dc3232');
                }
            });
        }, 1000);
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
                if (response.success && response.data) {
                    updateProviderStatus(response.data);
                } else {
                    // Handle invalid response silently or show user-friendly message
                }
            },
            error: function(xhr, status, error) {
                // Handle error silently or show user-friendly message
            }
        });
    }

    function updateProviderStatus(data) {
        $.each(data, function(provider, status) {
            var $statusElement = $('.provider-status-' + provider);
            
            // Update status indicator
            $statusElement.find('.status-indicator')
                .removeClass('healthy degraded error')
                .addClass(status.status || 'degraded');
            
            // Update statistics with fallback values
            $statusElement.find('.usage-count').text(status.monthly_usage || 0);
            $statusElement.find('.success-count').text(status.total_success || 0);
            $statusElement.find('.failed-count').text(status.total_failed || 0);
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
});
