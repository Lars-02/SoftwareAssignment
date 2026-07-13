@if ($equipments->isEmpty())

    <div class="rounded-lg border border-gray-200 bg-white p-10 text-center text-gray-500">
        No equipment found.
    </div>

@else

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            @foreach ($equipments as $equipment)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $equipment->Equipment }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $equipment->Material ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $equipment->Description ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $equipment->Room ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $equipment->Location ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $equipment->SystemStatus ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $equipments->links() }}
    </div>

@endif
