@props([
  'title' => '',
  'subtitle' => null,
])
<header class="bg-white border-b">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl font-bold tracking-tight">
      {{ $title }}
    </h1>
    @if($subtitle)
      <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
    @endif
  </div>
</header>
