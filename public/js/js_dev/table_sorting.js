/*
Every sortable <th> has a 'sortable' class.
Classes 'sorted-asc' and 'sorted-desc' will be added when <th> is clicked.
*/

$(document).ready(function() {
    $("th.sortable").each(function(i, el){
        // get array of classes of this element
        // like this ["sortable", "sort_filename", "col_filename"]
        let classesArray = Array.prototype.slice.call(el.classList);

        // Get the class we will sort by. I created special classes beginning with "sort_"
        // for our <th> elements to be able to easily sniff them out
        let classToSortBy = (classesArray.toString().match(/sort_.+?\b/))[0];

        // add a listener to each column that will sort by its own criterion (classToSortBy)
        $(this).on("click", () => sortBy(classToSortBy));
    });

    function sortBy(classToSortBy){
        let sortHeader = $(`th.${classToSortBy}`).first();
        let cellsToSort = $(`td.${classToSortBy}`);

        // turn cellsToSort into a real array (ES6 style)
        cellsToSort = [...cellsToSort];

        // Make sure it's a real array now
        // console.dir(Array.isArray(cellsToSort)); // true

        // determine if <th> is, in Angular terms, 'touched' or 'pristine'
        if (sortHeader.hasClass('sorted-asc')) {
            sortHeader.removeClass("sorted-asc");
            sortHeader.addClass("sorted-desc");
        } else {
            if (sortHeader.hasClass('sorted-desc')) {
                sortHeader.removeClass("sorted-desc");
                sortHeader.addClass("sorted-asc");
            } else {
                // remove sort classes from all <th>s when 'pristine' column is clicked
                let allSortHeaders = $('th');
                allSortHeaders.each(function(){
                    $(this).removeClass('sorted-asc sorted-desc');
                });
                sortHeader.addClass("sorted-asc");
            }
        }

        // if this is our first click on 'pristine' <th>, sort in ASC order
        if (sortHeader.hasClass('sorted-asc')) {
            cellsToSort.sort(function(a, b) {

                // Do we need numeric or string sorting?
                // Check if a single non-digit character (not counting dot)
                // is present in any of the cells to sort
                if (cellsToSort.some(x => /[^\d.]/.test(x.textContent))) {
                    // This is string sorting
                    if ($(a).text().toLowerCase() < $(b).text().toLowerCase()) {
                        $(a).parent().after($(b).parent());
                        return -1;
                    }
                    if ($(a).text().toLowerCase() > $(b).text().toLowerCase()) {
                        $(a).parent().before($(b).parent());
                        return 1;
                    }
                    return 0;
                } else {
                    // this is numeric sorting
                    if (Number($(a).text()) < Number($(b).text())) {
                        $(a).parent().after($(b).parent());
                        return -1;
                    }
                    if (Number($(a).text()) > Number($(b).text())) {
                        $(a).parent().before($(b).parent());
                        return 1;
                    }
                    return 0;
                }
            });
        }

        // if we need to reverse ASC sorting, simply reverse nodes
        if (sortHeader.hasClass('sorted-desc')) {
            $(cellsToSort).each(function(){
                $(this).parent().parent().prepend($(this).parent());
            });

            // we could also use .reverse() method on cellsToSort
            // let $tableBody = $(".filesAvailable tbody");
            // $tableBody.append($tableBody.children().get().reverse());
        }
    }
});
