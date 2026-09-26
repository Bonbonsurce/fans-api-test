@extends('layouts.app')

@use(Src\Application\Enums\ProfileRefreshStatus)

@section('title', '@'.$profile->username)

@section('nav')
    <a class="back" href="{{ route('profiles.index') }}">← All profiles</a>
@endsection

@section('content')
    <article class="panel">
        <div class="hero">
            <h1><span class="handle">@</span>{{ $profile->username }}</h1>
            <p class="lede">Refresh window: every {{ $profile->refreshIntervalHours() }} hours.</p>
            <code class="uuid">{{ $profile->uuid }}</code>
        </div>

        <div class="stats">
            <div class="stat">
                <span>Likes</span>
                <strong class="mono">{{ $profile->likes === null ? '—' : number_format($profile->likes) }}</strong>
            </div>
            <div class="stat">
                <span>Revision</span>
                <strong class="mono">{{ $profile->revision === null ? '—' : $profile->revision }}</strong>
            </div>
            <div class="stat">
                <span>Status</span>
                @php($status = $profile->refreshStatus())
                <strong>
                    <span class="pill pill-{{ $status->value }}">
                        {{ match ($status) {
                            ProfileRefreshStatus::Ok => 'Fresh',
                            ProfileRefreshStatus::Failed => 'Failed',
                            ProfileRefreshStatus::Pending => 'Pending',
                        } }}
                    </span>
                </strong>
            </div>
        </div>

        <div class="meta">
            <div>
                <span>Last attempt</span>
                {{ $profile->last_attempt_at?->format('M j, Y H:i') ?? '—' }}
            </div>
            <div>
                <span>Last successful refresh</span>
                {{ $profile->last_successful_refresh_at?->format('M j, Y H:i') ?? '—' }}
            </div>
            @if ($profile->last_failure)
                <div class="failure">
                    <span>Last failure</span>
                    {{ $profile->last_failure['message'] ?? json_encode($profile->last_failure) }}
                </div>
            @endif
        </div>
    </article>
@endsection
