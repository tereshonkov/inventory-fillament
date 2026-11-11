@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\AssetComponent>> $groups */
@endphp

<div class="space-y-4" data-testid="asset-composition">
    @forelse ($groups as $kindValue => $components)
        @php($kind = \App\Enums\ComponentKind::from($kindValue))

        <div data-kind="{{ $kindValue }}">
            <div class="mb-1 text-sm font-semibold text-gray-950 dark:text-white">
                {{ $kind->getLabel() }}
                <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $components->count() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($components as $unit)
                            <tr data-component="{{ $unit->getKey() }}">
                                <td class="py-2 pe-4 font-mono">{{ filled($unit->serial_number) ? $unit->serial_number : '—' }}</td>
                                <td class="py-2 pe-4">
                                    {{ $unit->effectiveHolderName() ?? '—' }}
                                    @if ($unit->usesStationHolder() && $unit->effectiveHolderName())
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(як у станції)</span>
                                    @endif
                                </td>
                                <td class="py-2 pe-4">
                                    {{ $unit->effectiveLocationName() ?? '—' }}
                                    @if ($unit->usesStationLocation() && $unit->effectiveLocationName())
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(як у станції)</span>
                                    @endif
                                </td>
                                <td class="py-2 pe-4">
                                    <x-filament::badge :color="$unit->is_working ? 'success' : 'danger'">
                                        {{ $unit->is_working ? 'Справна' : 'Несправна' }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2 text-gray-600 dark:text-gray-300">{{ filled($unit->description) ? $unit->description : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Комплектуючих ще не додано.</p>
    @endforelse
</div>
