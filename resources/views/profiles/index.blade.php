@extends('layouts.app')

@use(Src\Application\Enums\ProfileRefreshStatus)

@section('title', 'Profiles')

@section('content')
    <section class="panel">
        <div class="page-head">
            <div>
                <h1>Watched accounts</h1>
                <p class="lede">Latest stored snapshot for each OnlyFans profile.</p>
            </div>
            <div class="muted">{{ $profiles->count() }} {{ \Illuminate\Support\Str::plural('profile', $profiles->count()) }}</div>
        </div>

        @if ($profiles->isEmpty())
            <div class="empty">
                <strong>No profiles yet</strong>
                <p class="muted">Refresh jobs will land here once accounts are queued.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Likes</th>
                            <th>Revision</th>
                            <th>Last refresh</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profiles as $profile)
                            <tr>
                                <td>
                                    <a class="username" href="{{ route('profiles.show', $profile) }}">
                                        <span>@</span>{{ $profile->username }}
                                    </a>
                                </td>
                                <td class="mono">{{ $profile->likes === null ? '—' : number_format($profile->likes) }}</td>
                                <td class="mono">{{ $profile->revision === null ? '—' : $profile->revision }}</td>
                                <td class="muted">
                                    {{ $profile->last_successful_refresh_at?->format('M j, Y H:i') ?? 'Never' }}
                                </td>
                                <td>
                                    @php($status = $profile->refreshStatus())
                                    <span class="pill pill-{{ $status->value }}">
                                        {{ match ($status) {
                                            ProfileRefreshStatus::Ok => 'Fresh',
                                            ProfileRefreshStatus::Failed => 'Failed',
                                            ProfileRefreshStatus::Pending => 'Pending',
                                        } }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
