@props(['job'])

@php
  $isFav    = auth()->check() ? auth()->user()->favorites()->where('recruit_jobs.id',$job->id)->exists() : false;
  $favCount = $job->favored_by_count ?? $job->favoredBy()->count();
@endphp

@if(auth()->check())
  <button type="button"
          data-job-id="{{ $job->id }}"
          data-favorited="{{ $isFav ? '1':'0' }}"
          class="js-fav-toggle inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded-md border transition
                 {{ $isFav ? 'border-rose-200 bg-rose-50 hover:bg-rose-100' : 'border-amber-200 bg-amber-50 hover:bg-amber-100' }}">
    <span class="js-fav-star">{{ $isFav ? '★' : '☆' }}</span>
    <span class="js-fav-text">{{ $isFav ? '解除' : '追加' }}</span>
    <span class="js-fav-count text-gray-600 ml-1">({{ $favCount }})</span>
  </button>
@else
  <a href="{{ route('login') }}"
     class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50">
     ☆ 追加（ログイン）
  </a>
@endif

@once
  @push('scripts')
    <script>
      document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-fav-toggle');
        if (!btn) return;

        const jobId = btn.dataset.jobId;
        try {
          const res = await fetch(`/recruit_jobs/${jobId}/favorite/toggle`, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json'
            }
          });
          if (!res.ok) throw new Error('Network');
          const json = await res.json();

          btn.dataset.favorited = json.favorited ? '1' : '0';
          btn.querySelector('.js-fav-star').textContent = json.favorited ? '★' : '☆';
          btn.querySelector('.js-fav-text').textContent = json.favorited ? '解除' : '追加';
          btn.querySelector('.js-fav-count').textContent = `(${json.count})`;

          btn.classList.toggle('border-rose-200', json.favorited);
          btn.classList.toggle('bg-rose-50', json.favorited);
          btn.classList.toggle('hover:bg-rose-100', json.favorited);
          btn.classList.toggle('border-amber-200', !json.favorited);
          btn.classList.toggle('bg-amber-50', !json.favorited);
          btn.classList.toggle('hover:bg-amber-100', !json.favorited);
        } catch (err) {
          alert('通信に失敗しました。時間をおいて再度お試しください。');
        }
      });
    </script>
  @endpush
@endonce
