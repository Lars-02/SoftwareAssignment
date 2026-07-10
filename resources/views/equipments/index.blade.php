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

        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Equipment</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Material</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Description</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Room</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">System status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($equipments as $equipment)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ $equipment->Equipment }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $equipment->Material }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $equipment->Description }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $equipment->Room }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $equipment->Location }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $equipment->UserStatus }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $equipment->SystemStatus }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                No equipments imported yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $equipments->links() }}
        </div>
    </div>
</body>
</html>
