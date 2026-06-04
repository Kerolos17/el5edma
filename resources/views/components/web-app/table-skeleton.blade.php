{{--
    Table Skeleton Loading Rows
    Usage:
        <x-web-app.table-skeleton :cols="7" :rows="6" />
    Renders `rows` skeleton rows with `cols` columns.
--}}
@props(['cols' => 5, 'rows' => 5])
@for ($r = 0; $r < $rows; $r++)
    <tr class="app-table-skeleton" aria-hidden="true">
        @for ($c = 0; $c < $cols; $c++)
            <td>
                <div class="app-skeleton" style="height:1rem; width:{{ $c === 0 ? '2.25rem' : ($c % 3 === 0 ? '4rem' : '7rem') }}; border-radius:0.4rem"></div>
            </td>
        @endfor
    </tr>
@endfor
