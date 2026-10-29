<x-layouts.admin title="Options">
    @if(session('success'))
        <div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.options.update') }}">
        @csrf @method('PUT')

        @foreach(\App\Http\Controllers\Admin\AdminOptionsController::groups() as $groupName => $groupOptions)
        <div style="margin-bottom:24px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <div style="background-color:var(--bg-surface-alt); padding:8px 14px; font-size:12px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.06em;">
                {{ $groupName }}
            </div>
            <table class="hlx-table" style="margin:0;">
                @foreach($groupOptions as $key => $meta)
                <tr>
                    <td style="width:40%; color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">
                        {{ $meta['label'] }}
                    </td>
                    <td style="padding:6px 14px;">
                        @if($meta['type'] === 'select')
                            <select name="{{ $key }}" style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 8px; font-size:var(--font-size-sm); min-width:180px;">
                                @foreach($meta['options'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($options[$key] ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="{{ $key }}" value="{{ $options[$key] ?? '' }}"
                                   style="width:100%; max-width:400px; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 8px; font-size:var(--font-size-sm);">
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        @endforeach

        <div style="padding:4px 0 16px;">
            <button type="submit" class="hlx-btn-gold">Save Options</button>
        </div>
    </form>
</x-layouts.admin>
