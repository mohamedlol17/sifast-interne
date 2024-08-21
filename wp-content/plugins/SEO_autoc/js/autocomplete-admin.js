jQuery(document).ready(function ($) {
    var apiData = search.apiData || [];

    // Function to handle autocomplete input
    function handleAutocompleteInput(inputField, resultsContainer) {
        inputField.on('input', function () {
            var searchTerm = $(this).val().toLowerCase();
            resultsContainer.empty();
            if (searchTerm.length > 0) {
                var results = apiData.filter(function (item) {
                    return item.title && item.title.toLowerCase().includes(searchTerm);
                });

                if (results.length > 0) {
                    results.forEach(function (item) {
                        resultsContainer.append('<div class="autocomplete-item">' + item.title + '</div>');
                    });
                } else {
                    resultsContainer.append('<div class="autocomplete-item">No results found</div>');
                }
            }
        });
    }

    // Function to handle form submission
    function handleFormSubmission(form, resultsContainer) {
        form.on('submit', function (e) {
            e.preventDefault();
            var searchTerm = form.find('input[type="text"]').val().toLowerCase();
            resultsContainer.empty();
            var results = apiData.filter(function (item) {
                return item.title && item.title.toLowerCase().includes(searchTerm);
            });

            if (results.length > 0) {
                results.forEach(function (item) {
                    var itemBlock = '<div class="search-result-block">' +
                        '<h2>' + item.title + '</h2>' +
                        '<img src="' + (item.pictures && item.pictures[0] ? item.pictures[0].url : 'default-image-url.jpg') + '" alt="' + item.title + '" style="width:100px;height:auto;">' +
                        '<p>' + item.address + ', ' + item.city + '</p>' +
                        '<p>Starting from: €' + item.preview.rent_amount_from + '</p>' +
                        '<a href="details.php?residence_id=' + item.id + '" class="view-more">View More</a>' +
                        '</div>';
                    resultsContainer.append(itemBlock);
                });
            } else {
                resultsContainer.append('<p>No results found.</p>');
            }
        });
    }

    // Autocomplete and form submission handling for admin
    handleAutocompleteInput($('#admin-custom-search-input'), $('#admin-autocomplete-results'));
    handleFormSubmission($('#admin-custom-search-form'), $('#admin-search-results'));

    // Handle click on autocomplete item
    $(document).on('click', '.autocomplete-item', function () {
        var inputField = $(this).closest('form').find('input[type="text"]');
        inputField.val($(this).text());
        $('#admin-autocomplete-results').empty();
    });

    // Display initial items
    function displayInitialItems(container) {
        container.empty();
        if (apiData.length > 0) {
            apiData.forEach(function (item) {
                var itemBlock = '<div class="search-result-block">' +
                    '<h2>' + item.title + '</h2>' +
                    '<img src="' + (item.pictures && item.pictures[0] ? item.pictures[0].url : 'default-image-url.jpg') + '" alt="' + item.title + '" style="width:100px;height:auto;">' +
                    '<p>' + item.address + ', ' + item.city + '</p>' +
                    '<p>Starting from: €' + item.preview.rent_amount_from + '</p>' +
                    '<a href="details.php?residence_id=' + item.id + '" class="view-more">View More</a>' +
                    '</div>';
                container.append(itemBlock);
            });
        } else {
            container.append('<p>No initial items found.</p>');
        }
    }

    // Display initial items for admin
    displayInitialItems($('#admin-search-results'));

    // Handle click on "View More" button
    $(document).on('click', '.view-more', function (e) {
        e.preventDefault();
        var residenceId = $(this).data('residence-id');
        window.location.href = 'details.php?residence_id=' + residenceId;
    });
});
