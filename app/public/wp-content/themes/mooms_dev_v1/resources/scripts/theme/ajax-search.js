/**
 * AJAX Live Search
 * Handles real-time search functionality in header
 */

// Debug: Verify script is loading
console.log('AJAX Search script loaded!', typeof jQuery !== 'undefined' ? 'jQuery available' : 'jQuery NOT available');

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('AJAX Search: Document ready');
        const $searchInput = $('.header__bottom-search input[type="text"]');
        const $searchForm = $('.header__bottom-search .search-box');
        let $searchResults;
        let searchTimeout;
        
        console.log('Search input found:', $searchInput.length);
        console.log('ajaxData available:', typeof ajaxData !== 'undefined', ajaxData);
        
        if (!$searchInput.length) {
            console.error('AJAX Search: No search input found!');
            return;
        }
        
        // Create search results container if it doesn't exist
        if (!$searchForm.find('.search-results').length) {
            $searchForm.append('<div class="search-results"></div>');
        }
        $searchResults = $searchForm.find('.search-results');
        console.log('Search results container ready:', $searchResults.length);
        
        /**
         * Debounced search handler
         */
        $searchInput.on('input', function() {
            const searchQuery = $(this).val().trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Clear results if query is too short
            if (searchQuery.length < 2) {
                $searchResults.html('').removeClass('active');
                return;
            }
            
            // Debounce search request
            searchTimeout = setTimeout(function() {
                performSearch(searchQuery);
            }, 300); // Wait 300ms after user stops typing
        });
        
        /**
         * Perform AJAX search
         */
        function performSearch(query) {
            console.log('Performing search for:', query);
            console.log('AJAX URL:', ajaxData.ajaxurl);
            
            $.ajax({
                url: ajaxData.ajaxurl,
                type: 'GET',
                data: {
                    action: 'ajax_search',
                    s: query
                },
                beforeSend: function() {
                    console.log('AJAX: Sending request...');
                    $searchResults.html('<div class="search-results__loading">Đang tìm kiếm...</div>').addClass('active');
                },
                success: function(response) {
                    console.log('AJAX: Success! Response:', response);
                    if (response && response.trim() !== '') {
                        $searchResults.html(response).addClass('active');
                    } else {
                        $searchResults.html('<div class="search-results__empty"><p>Không tìm thấy kết quả</p></div>').addClass('active');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX: Error!', {xhr, status, error});
                    $searchResults.html('<div class="search-results__error">Có lỗi xảy ra. Vui lòng thử lại.</div>').addClass('active');
                }
            });
        }
        
        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.header__bottom-search').length) {
                $searchResults.removeClass('active');
            }
        });
        
        // Show results when focusing on input (if there are results)
        $searchInput.on('focus', function() {
            if ($searchResults.html().trim() !== '') {
                $searchResults.addClass('active');
            }
        });
        
        // Clear search on form reset
        $searchForm.on('reset', function() {
            setTimeout(function() {
                $searchResults.html('').removeClass('active');
            }, 10);
        });
    });
    
})(jQuery);
