<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipments</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 text-gray-900">

<div class="max-w-7xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-semibold mb-6">Equipments</h1>

    <form method="GET" action="{{ route('equipments.index') }}" class="mb-6 flex gap-3">

        <div class="flex-1">
            <label for="search" class="sr-only">Search</label>
            <input type="text" name="search" id="search" value="{{ request('search') }}" maxlength="191"
                   placeholder="Search by equipment, material, description or room..."
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm px-3 py-2 border">
        </div>

        <button type="submit"
                class="rounded-md bg-gray-900 text-white text-sm font-medium px-4 py-2 hover:bg-gray-700">
            Search
        </button>

        <a href="{{ route('equipments.index') }}"
           class="rounded-md border border-gray-300 text-sm font-medium px-4 py-2 hover:bg-gray-50">
            Clear
        </a>

    </form>

    <div id="equipment-results">
        @include('equipments.partials.table')
    </div>

</div>

<script>
    const searchInput = document.getElementById('search');
    const resultsContainer = document.getElementById('equipment-results');
    let debounceTimer;

    function performSearch(url) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Search failed (${response.status})`);
                }
                return response.text();
            })
            .then(html => {
                resultsContainer.innerHTML = html;
                window.history.pushState({}, '', url);
            })
            .catch(() => {
                // Validation error or network failure: keep the current results.
            });
    }

    function searchUrl() {
        const params = new URLSearchParams({ search: searchInput.value });
        return `{{ route('equipments.index') }}?${params.toString()}`;
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => performSearch(searchUrl()), 300);
    });

    document.querySelector('form').addEventListener('submit', function (e) {
        e.preventDefault();
        performSearch(searchUrl());
    });

    // Keep pagination on the AJAX path too: intercept clicks on the
    // pagination links that arrive inside the swapped-in results.
    resultsContainer.addEventListener('click', function (e) {
        const link = e.target.closest('a[href*="page="]');
        if (!link) {
            return;
        }
        e.preventDefault();
        performSearch(link.href);
    });

    // Back/forward buttons: the URL was changed via pushState, so reload
    // to render the state that URL represents.
    window.addEventListener('popstate', () => window.location.reload());
</script>

</body>
</html>
