<section class="max-w-2xl mx-auto mt-10 mb-16">
  <div class="rounded-2xl border border-gray-200 bg-white/90 shadow-sm">
    <div class="px-6 py-5 border-b border-gray-100">
      <h3 class="text-lg font-semibold">この求人に応募する</h3>
      <p class="mt-1 text-sm text-gray-500">別ページの応募フォームからお申し込みください。</p>
    </div>

    <div class="px-6 py-6 text-center">
      <a
        href="{{ route('front.jobs.apply.show', ['slugOrId' => $job->slug ?? $job->id]) }}"
        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-white font-medium hover:bg-indigo-700 transition"
      >
        応募フォームへ進む
      </a>
    </div>
  </div>
</section>
