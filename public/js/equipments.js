(() => {
    const dataEndpoint = '/equipments';
    const $searchInput = $('#searchInput');
    const $tableBody = $('#tableBody');
    const $prevPage = $('#prevPage');
    const $nextPage = $('#nextPage');
    const $pageNumbers = $('#pageNumbers');
    const $searchButton = $('#searchButton');
    const $overview = $('#overview');


    const bodyCellClass = 'border-b px-4 py-2 text-sm text-gray-700';
    const emptyCellClass = 'px-4 py-4 text-sm text-gray-500';
    const pageButtonClass = 'rounded border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50';
    const pageButtonActiveClass = 'rounded border border-blue-600 bg-blue-600 px-3 py-1 text-sm text-white';

    let activePage = 1;

    function renderTotalData(payload) {
        if (!payload || payload.total === 0) {
            $overview.text('0 records');
            return;
        }

        $overview.text(`Showing ${payload.from}-${payload.to} of ${payload.total}`);
    }

    function renderTable(items) {
        $tableBody.empty();

        if (items.length === 0) {
            const $row = $('<tr></tr>');
            const $cell = $('<td></td>')
                .addClass(emptyCellClass)
                .attr('colspan', columns.length)
                .text('No equipment found.');
            $row.append($cell);
            $tableBody.append($row);
            return;
        }

        $tableBody.empty();

        $.each(items, (_, rowData) => {
            const $row = $('<tr></tr>');

            $.each(columns, (_, column) => {
                const value = rowData[column] ?? '-';
                const $td = $('<td></td>').addClass(bodyCellClass).text(value);
                $row.append($td);
            });

            $tableBody.append($row);
        });
    }

    function renderPagination(payload) {
        $pageNumbers.empty();
        const currentPage = payload && payload.current_page ? payload.current_page : 1;
        const lastPage = payload && payload.last_page ? payload.last_page : 1;

        $prevPage.prop('disabled', currentPage <= 1);
        $nextPage.prop('disabled', currentPage >= lastPage);

        for (let pageNumber = 1; pageNumber <= lastPage; pageNumber++) {
            const $pageButton = $(`<button type="button">${pageNumber}</button>`);

            if (pageNumber === currentPage) {
                $pageButton.addClass(pageButtonActiveClass);
                $pageButton.prop('disabled', true);
            } else {
                $pageButton.addClass(pageButtonClass);
                $pageButton.on('click', () => fetchRows(pageNumber));
            }

            $pageNumbers.append($pageButton);
        }
    }

    function fetchRows(page) {
        const params = {
            page: page || 1,
            search: $searchInput.val(),
        };

        $.get({
            url: dataEndpoint,
            data: params,
            dataType: 'json',
        }).done((response) => {
            activePage = response.current_page || 1;
            renderTable(response.data || []);
            renderTotalData(response);
            renderPagination(response);
        });
    }

    $searchButton.on('click', () => {
        fetchRows(1);
    });

    $prevPage.on('click', () => {
        if (activePage > 1) {
            fetchRows(activePage - 1);
        }
    });

    $nextPage.on('click', () => {
        fetchRows(activePage + 1);
    });

    fetchRows(activePage);
})();
