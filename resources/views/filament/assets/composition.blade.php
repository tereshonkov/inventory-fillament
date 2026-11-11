@php
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, \App\Models\AssetComponent>> $groups */

    // Layout-critical styles are inline on purpose: they must not depend on the Tailwind build
    // (public/build is not committed, and a stale build on the server lost the spacing before).
    $cell = 'padding: 0.5rem 1rem 0.5rem 0; vertical-align: top; text-align: start;';
    $muted = 'color: rgb(107 114 128); font-size: 0.75rem;';
@endphp

<div data-testid="asset-composition" style="display: flex; flex-direction: column; gap: 1.5rem;">
    @forelse ($groups as $kindValue => $units)
        @php($kind = \App\Enums\ComponentKind::from($kindValue))

        <div data-kind="{{ $kindValue }}">
            <div style="font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem;" class="text-gray-950 dark:text-white">
                {{ $kind->getLabel() }}
                <span style="font-weight: 400; color: rgb(107 114 128);">· {{ $units->count() }}</span>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; min-width: 40rem; table-layout: fixed; border-collapse: collapse; font-size: 0.875rem;">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 22%;">
                        <col style="width: 18%;">
                        <col style="width: 12%;">
                        <col style="width: 28%;">
                    </colgroup>
                    <thead>
                        <tr style="{{ $muted }}">
                            <th style="{{ $cell }} font-weight: 500;">Серійний номер</th>
                            <th style="{{ $cell }} font-weight: 500;">У кого</th>
                            <th style="{{ $cell }} font-weight: 500;">Локація</th>
                            <th style="{{ $cell }} font-weight: 500;">Стан</th>
                            <th style="{{ $cell }} font-weight: 500;">Опис</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr data-component="{{ $unit->getKey() }}" style="border-top: 1px solid rgba(128, 128, 128, 0.25);">
                                <td style="{{ $cell }} font-family: ui-monospace, monospace; word-break: break-all;">{{ filled($unit->serial_number) ? $unit->serial_number : '—' }}</td>
                                <td style="{{ $cell }}">
                                    {{ $unit->effectiveHolderName() ?? '—' }}
                                    @if ($unit->usesStationHolder() && $unit->effectiveHolderName())
                                        <div style="{{ $muted }}">як у станції</div>
                                    @endif
                                </td>
                                <td style="{{ $cell }}">
                                    {{ $unit->effectiveLocationName() ?? '—' }}
                                    @if ($unit->usesStationLocation() && $unit->effectiveLocationName())
                                        <div style="{{ $muted }}">як у станції</div>
                                    @endif
                                </td>
                                <td style="{{ $cell }}">
                                    <x-filament::badge :color="$unit->is_working ? 'success' : 'danger'">
                                        {{ $unit->is_working ? 'Справна' : 'Несправна' }}
                                    </x-filament::badge>
                                </td>
                                <td style="{{ $cell }} overflow-wrap: anywhere;">{{ filled($unit->description) ? $unit->description : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p style="font-size: 0.875rem; color: rgb(107 114 128);">Комплектуючих ще не додано.</p>
    @endforelse
</div>
