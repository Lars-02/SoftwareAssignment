<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipments</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold">Equipments</h1>

    <form method="GET" action="{{ route('equipments.index') }}" id="equipment-search-form" class="mt-6">
        <label for="search" class="sr-only">Search equipment</label>
        <input
            type="text"
            name="search"
            id="search"
            value="{{ old('search', $search) }}"
            placeholder="Search by equipment, material, description or room"
            class="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            autocomplete="off"
        >
        <p id="search-error" class="mt-1 text-sm text-red-600">
            @error('search') {{ $message }} @enderror
        </p>
    </form>

    <div id="equipments-results" class="mt-4">
        @include('equipments.partials.table')
    </div>
</div>

<script>
    const form = document.getElementById('equipment-search-form');
    const searchInput = document.getElementById('search');
    const resultsContainer = document.getElementById('equipments-results');
    const errorContainer = document.getElementById('search-error');

    if (form && searchInput && resultsContainer) {
        let debounceTimer;

        const buildUrl = (search) => {
            const url = new URL(form.action);
            if (search) {
                url.searchParams.set('search', search);
            }
            return url;
        };

        const runSearch = async (url) => {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json, text/html',
                },
            });

            if (response.status === 422) {
                const data = await response.json();
                errorContainer.textContent = data.errors?.search?.[0] ?? 'Invalid search.';

                return;
            }

            if (!response.ok) {
                return;
            }

            resultsContainer.innerHTML = await response.text();
            errorContainer.textContent = '';
            window.history.pushState({}, '', url);
        };

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                runSearch(buildUrl(searchInput.value));
            }, 300);
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(debounceTimer);
            runSearch(buildUrl(searchInput.value));
        });

        resultsContainer.addEventListener('click', (event) => {
            const link = event.target.closest('a');

            if (!link || !resultsContainer.contains(link)) {
                return;
            }

            event.preventDefault();
            runSearch(new URL(link.href));
        });

        window.addEventListener('popstate', () => {
            runSearch(new URL(window.location.href));
        });
    }
</script>
</body>
</html>
