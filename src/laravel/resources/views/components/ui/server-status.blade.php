@props(['online' => false])

<span class="hlx-status-dot {{ $online ? 'hlx-status-online' : 'hlx-status-offline' }}"
      title="{{ $online ? 'Online' : 'Offline' }}"></span>
