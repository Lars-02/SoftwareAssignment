@php
    $headers = [
        'id',
        'Equipment',
        'Material',
        'MaterialWithoutFet',
        'Description',
        'IH09Description',
        'Room',
        'Plant',
        'Location',
        'Sloc',
        'ManufactSerialNumber',
        'SerNo',
        'UserStatus',
        'SystemStatus',
        'Dimensions',
        'CleaningCounter_limit',
        'CleaningCounter_current',
        'GrossWeight',
        'current_status',
        'workcenter',
        'StockType',
        'CreatedOn',
        'CreatedBy',
        'ChangedOn',
        'ChangedBy',
    ];

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
<div class="mx-auto max-w-6xl p-4">
    <div class="border bg-white">
        <div class="border-b p-4">
            <div class="flex gap-2">
                <input
                id="searchInput"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                type="text"
                name="search"
                value=""
                placeholder="Type to search Equipment, Material, Description, Room"
                autocomplete="off"
                >
                <button
                    id="searchButton"
                    type="button"
                    class="rounded border border-blue-600 bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                >
                    Search
                </button>
            </div>
            <div id="overview" class="mt-2 text-sm text-gray-600"></div>
        </div>

        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr id="tableHead">
                        @foreach($headers as $header)
                            <th class="bg-gray-100 px-4 py-2 text-left text-xs uppercase text-gray-600">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td class="px-4 py-4 text-sm text-gray-500" colspan="{{ count($headers) }}">Loading equipment data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="pagination" class="flex items-center justify-between gap-3 border-t p-3">
            <button
                id="prevPage"
                type="button"
                class="rounded border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                disabled
            >
                Prev
            </button>
            <div id="pageNumbers" class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                <button
                    type="button"
                    class="rounded border border-blue-600 bg-blue-600 px-3 py-1 text-sm text-white"
                    disabled
                >
                    1
                </button>
            </div>
            <button
                id="nextPage"
                type="button"
                class="rounded border border-gray-300 bg-white px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                disabled
            >
                Next
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    const columns = @json($headers);
</script>
<script src="{{ asset('js/equipments.js') }}" defer></script>
</body>
</html>
