<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
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
                        @if ($search)
                            No equipments match "{{ $search }}".
                        @else
                            No equipments imported yet.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $equipments->links() }}
</div>
