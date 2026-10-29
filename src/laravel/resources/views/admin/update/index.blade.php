<x-layouts.admin title="Update">

    @if(session('update_success'))
        @php $us = session('update_success'); @endphp
        <div style="background:rgba(63,185,80,0.12); border:1px solid var(--status-online); border-radius:var(--border-radius-md); padding:16px; margin-bottom:20px;">
            <div style="font-weight:600; color:var(--status-online); margin-bottom:8px;">
                {{ __('✔ Update to version :version applied successfully!', ['version' => $us['version']]) }}
            </div>
            <ul style="margin:0; padding-left:18px; font-size:var(--font-size-sm); color:var(--text-secondary);">
                @foreach($us['log'] as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Current status --}}
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; margin-bottom:20px; background:var(--bg-surface);">
        <h2 style="margin:0 0 16px; font-size:var(--font-size-lg); color:var(--text-primary);">{{ __('Version status') }}</h2>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px;">
            <div>
                <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;">{{ __('Installed version') }}</div>
                <div style="font-size:1.4rem; font-weight:700; color:var(--text-primary); font-family:var(--font-family-mono);">
                    {{ $versionInfo['installed'] }}
                </div>
            </div>

            <div>
                <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;">{{ __('Latest version (GitHub)') }}</div>
                @if($versionInfo['latest'])
                    <div style="font-size:1.4rem; font-weight:700; font-family:var(--font-family-mono);
                        color:{{ $versionInfo['upToDate'] ? 'var(--status-online)' : 'var(--accent-primary)' }}">
                        {{ $versionInfo['latestTag'] }}
                    </div>
                @else
                    <div style="font-size:var(--font-size-sm); color:var(--text-muted);">{{ __('Unable to reach GitHub') }}</div>
                    @if(! empty($versionInfo['error']))
                        <div style="font-size:var(--font-size-xs); color:var(--status-offline); margin-top:4px; line-height:1.4;">
                            {{ $versionInfo['error'] }}
                        </div>
                    @endif
                @endif
            </div>

            <div style="display:flex; align-items:center;">
                @if($versionInfo['latest'] === null)
                    <span style="padding:6px 14px; background:rgba(139,148,158,0.15); color:var(--text-muted); border-radius:20px; font-size:var(--font-size-sm);">
                        ⚠ {{ __('Offline') }}
                    </span>
                @elseif($versionInfo['upToDate'])
                    <span style="padding:6px 14px; background:rgba(63,185,80,0.15); color:var(--status-online); border-radius:20px; font-size:var(--font-size-sm);">
                        ✔ {{ __('Up to date') }}
                    </span>
                @else
                    <span style="padding:6px 14px; background:rgba(210,153,34,0.2); color:var(--accent-primary); border-radius:20px; font-size:var(--font-size-sm); font-weight:600;">
                        ↑ {{ __('Update available') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Release notes --}}
    @if($versionInfo['latest'] && ! $versionInfo['upToDate'])
        @php $release = $versionInfo['latest']; @endphp
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:20px; margin-bottom:20px; background:var(--bg-surface);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; gap:12px; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0 0 4px; font-size:var(--font-size-lg); color:var(--text-primary);">
                        {{ $release['name'] ?? ('v' . $versionInfo['latestTag']) }}
                    </h2>
                    @if(isset($release['published_at']))
                        <div style="font-size:var(--font-size-xs); color:var(--text-muted);">
                            {{ __('Published on :date', ['date' => \Carbon\Carbon::parse($release['published_at'])->format('d/m/Y')]) }}
                        </div>
                    @endif
                </div>
                <a href="{{ $release['html_url'] ?? '#' }}" target="_blank" rel="noopener"
                   class="hlx-btn-secondary" style="font-size:var(--font-size-sm);">
                    {{ __('View on GitHub') }} ↗
                </a>
            </div>

            @if(! empty($release['body']))
                <div style="background:var(--bg-surface-alt); border-radius:var(--border-radius-sm); padding:14px;
                            font-size:var(--font-size-sm); color:var(--text-secondary); white-space:pre-wrap;
                            font-family:var(--font-family-mono); max-height:300px; overflow-y:auto; line-height:1.6;">{{ $release['body'] }}</div>
            @endif
        </div>

        {{-- Apply update --}}
        <div style="border:1px solid var(--accent-primary); border-radius:var(--border-radius-md); padding:20px; background:rgba(210,153,34,0.05);">
            <h2 style="margin:0 0 10px; font-size:var(--font-size-lg); color:var(--text-primary);">{{ __('Apply update') }}</h2>

            <ul style="font-size:var(--font-size-sm); color:var(--text-secondary); margin:0 0 16px; padding-left:18px; line-height:1.8;">
                <li>{!! __('The :env file and :storage and :vendor folders will never be modified', ['env' => '<code>.env</code>', 'storage' => '<code>storage/</code>', 'vendor' => '<code>vendor/</code>']) !!}</li>
                <li>{{ __('Migrations will run automatically') }}</li>
                <li>{!! __(':command will be executed', ['command' => '<code>php artisan optimize:clear &amp;&amp; optimize</code>']) !!}</li>
                <li>{!! __(':command will be attempted if available, otherwise must be run manually', ['command' => '<code>composer install</code>']) !!}</li>
                <li>{{ __('A database backup beforehand is strongly recommended') }}</li>
            </ul>

            <button type="button" id="hlx-update-start" class="hlx-btn-gold" style="font-size:var(--font-size-base);">
                {{ __('↑ Update to :version', ['version' => $versionInfo['latestTag']]) }}
            </button>

            {{-- Progress UI --}}
            <div id="hlx-update-progress" style="display:none; margin-top:20px;">
                @php
                    $steps = [
                        'download' => __('Download'),
                        'extract'  => __('Extraction'),
                        'copy'     => __('Copying files'),
                        'migrate'  => __('Database migrations'),
                        'composer' => __('Composer'),
                        'cache'    => __('Caches'),
                    ];
                @endphp
                @foreach($steps as $key => $label)
                    <div data-step="{{ $key }}" style="margin-bottom:10px; opacity:.4;" class="hlx-step">
                        <div style="display:flex; justify-content:space-between; font-size:var(--font-size-sm); margin-bottom:4px;">
                            <span><span class="hlx-step-icon">○</span> {{ $label }}</span>
                            <span class="hlx-step-pct" style="font-family:var(--font-family-mono); color:var(--text-muted);">—</span>
                        </div>
                        <div style="height:6px; background:var(--bg-surface-alt); border-radius:3px; overflow:hidden;">
                            <div class="hlx-step-bar" style="height:100%; width:0%; background:var(--accent-primary); transition:width .3s;"></div>
                        </div>
                    </div>
                @endforeach

                <div id="hlx-update-log" style="margin-top:16px; max-height:220px; overflow-y:auto;
                            background:var(--bg-surface-alt); border-radius:var(--border-radius-sm); padding:10px;
                            font-family:var(--font-family-mono); font-size:var(--font-size-xs);
                            color:var(--text-secondary); line-height:1.6;"></div>
            </div>
        </div>

        <script>
        (function () {
            const btn = document.getElementById('hlx-update-start');
            if (!btn) return;

            const csrf = '{{ csrf_token() }}';
            const streamUrl = @json(\Route::has('admin.update.stream') ? route('admin.update.stream') : null);
            const fallbackUrl = '{{ route('admin.update.apply') }}';
            const progress = document.getElementById('hlx-update-progress');
            const logBox = document.getElementById('hlx-update-log');

            const T = {
                confirm:        @json(__('Apply update :version?', ['version' => $versionInfo['latestTag']]) . "\n\n" . __('A database backup is recommended.')),
                inProgress:     @json('⏳ ' . __('Update in progress…')),
                fallbackLog:    @json(__('Streaming mode unavailable — falling back to classic mode…')),
                reloadPage:     @json('↻ ' . __('Reload page')),
                retry:          @json('↻ ' . __('Retry')),
                errorPrefix:    @json(__('Error:')),
            };

            function appendLog(msg, level = 'info') {
                const line = document.createElement('div');
                const color = level === 'error' ? 'var(--status-offline)'
                            : level === 'done'  ? 'var(--status-online)'
                            : level === 'step'  ? 'var(--accent-primary)'
                            : 'var(--text-secondary)';
                line.style.color = color;
                line.textContent = msg;
                logBox.appendChild(line);
                logBox.scrollTop = logBox.scrollHeight;
            }

            function updateStep(key, percent) {
                const el = document.querySelector(`.hlx-step[data-step="${key}"]`);
                if (!el) return;
                el.style.opacity = '1';
                const bar = el.querySelector('.hlx-step-bar');
                const pct = el.querySelector('.hlx-step-pct');
                const icon = el.querySelector('.hlx-step-icon');
                bar.style.width = percent + '%';
                pct.textContent = percent + '%';
                if (percent >= 100) {
                    icon.textContent = '✔';
                    icon.style.color = 'var(--status-online)';
                } else {
                    icon.textContent = '●';
                    icon.style.color = 'var(--accent-primary)';
                }
            }

            function handleEvent(eventName, dataStr) {
                let data = {};
                try { data = JSON.parse(dataStr); } catch (e) {}
                if (eventName === 'step') {
                    appendLog('→ ' + data.label, 'step');
                    updateStep(data.key, 0);
                } else if (eventName === 'progress') {
                    updateStep(data.key, data.percent);
                } else if (eventName === 'log') {
                    appendLog(data.message);
                } else if (eventName === 'done') {
                    appendLog('✔ ' + data.message, 'done');
                    btn.disabled = false;
                    btn.textContent = T.reloadPage;
                    btn.removeEventListener('click', startUpdate);
                    btn.addEventListener('click', () => location.reload(), { once: true });
                } else if (eventName === 'error') {
                    appendLog('✖ ' + data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = T.retry;
                }
            }

            async function startUpdate() {
                if (!confirm(T.confirm)) {
                    return;
                }
                btn.disabled = true;
                btn.textContent = T.inProgress;
                progress.style.display = 'block';

                // Fallback to classic POST submit if streaming route is unavailable
                if (!streamUrl) {
                    appendLog(T.fallbackLog);
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = fallbackUrl;
                    form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '">';
                    document.body.appendChild(form);
                    form.submit();
                    return;
                }

                try {
                    const res = await fetch(streamUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/event-stream',
                            'X-CSRF-TOKEN': csrf,
                        },
                    });
                    if (!res.ok || !res.body) {
                        appendLog('HTTP ' + res.status, 'error');
                        return;
                    }
                    const reader = res.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const blocks = buffer.split('\n\n');
                        buffer = blocks.pop();
                        for (const block of blocks) {
                            const lines = block.split('\n');
                            let evt = 'message', data = '';
                            for (const l of lines) {
                                if (l.startsWith('event:')) evt = l.slice(6).trim();
                                else if (l.startsWith('data:')) data += l.slice(5).trim();
                            }
                            handleEvent(evt, data);
                        }
                    }
                } catch (err) {
                    appendLog(T.errorPrefix + ' ' + err.message, 'error');
                    btn.disabled = false;
                    btn.textContent = T.retry;
                }
            }

            btn.addEventListener('click', startUpdate);
        })();
        </script>
        </div>

    @elseif($versionInfo['latest'] && $versionInfo['upToDate'])
        <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:16px; background:var(--bg-surface);
                    font-size:var(--font-size-sm); color:var(--text-muted); text-align:center;">
            {{ __('You are using the latest version available. No action required.') }}
        </div>
    @endif

</x-layouts.admin>
