jQuery(document).ready(function($) {
    function performSearch(query) {
        $.ajax({
            url: search.ajaxurl,
            type: 'POST',
            data: {
                action: 'custom_search_ajax',
                term: query
            },
            success: function(response) {
                $('#search-results').html(response);
            }
        });
    }

    $('#custom-search-input').on('input', function() {
        var query = $(this).val();
        if (query.length < 3) {
            $('#autocomplete-results').empty();
            return;
        }

        $.ajax({
            url: search.ajaxurl,
            type: 'GET',
            data: {
                action: 'custom_search_autocomplete',
                term: query
            },
            success: function(response) {
                var suggestions = JSON.parse(response);
                var suggestionHtml = suggestions.map(function(suggestion) {
                    return '<div class="autocomplete-suggestion">' + suggestion + '</div>';
                }).join('');

                $('#autocomplete-results').html(suggestionHtml);
            }
        });
    });

    $(document).on('click', '.autocomplete-suggestion', function() {
        var query = $(this).text();
        $('#custom-search-input').val(query);
        $('#autocomplete-results').empty();
        performSearch(query);
    });

    $('#custom-search-button').on('click', function() {
        var query = $('#custom-search-input').val();
        performSearch(query);
    });

    $(document).on('click', '.view-more', function(event) {
        event.preventDefault(); // Prevent default anchor behavior
        console.log(this);
        window.location.href = $(this).attr('href'); // Navigate to the URL specified in href attribute
    });
});
