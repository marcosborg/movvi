@if (!empty($tvde_weeks) && count($tvde_weeks) > 0)
    <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
        <label for="weekQuickSelect" style="margin: 6px 10px 0 0;">Ir para semana</label>
        <select
            id="weekQuickSelect"
            class="form-control"
            style="max-width: 320px;"
            onchange="if (this.value) { window.location.href = this.value; }"
        >
            @foreach ($tvde_weeks as $tvde_week)
                <option
                    value="/admin/financial-statements/week/{{ $tvde_week->id }}"
                    {{ (int) $tvde_week->id === (int) $tvde_week_id ? 'selected' : '' }}
                >
                    Semana {{ $tvde_week->display_number ?? $tvde_week->number }}/{{ $tvde_week->display_year ?? '-' }}
                    · {{ \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m') }}
                    a {{ \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m') }}
                </option>
            @endforeach
        </select>
    </div>
@endif
