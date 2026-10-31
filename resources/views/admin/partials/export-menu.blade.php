@php($exportQuery = array_merge($query ?? request()->except('page', 'format'), ['format' => '']))
<div class="export-menu" data-export-menu>
    <button class="btn btn-secondary export-menu-trigger" type="button" data-export-toggle aria-expanded="false" aria-haspopup="true"><x-icon name="download" />Ekspor <x-icon name="chevron-down" size="14" /></button>
    <div class="export-menu-panel" data-export-panel hidden>
        <p class="export-menu-scope">{{ $scope ?? 'Seluruh data sesuai filter aktif' }}</p>
        @foreach(['xlsx' => ['Excel (.xlsx)', 'file-spreadsheet'], 'csv' => ['CSV (.csv)', 'file-text'], 'pdf' => ['PDF (.pdf)', 'file-text']] as $format => [$label, $icon])
            <a href="{{ route('admin.export', array_merge(['module' => $module], $exportQuery, ['format' => $format])) }}" data-no-transition data-export-link><x-icon :name="$icon" size="17" />{{ $label }}</a>
        @endforeach
    </div>
</div>
