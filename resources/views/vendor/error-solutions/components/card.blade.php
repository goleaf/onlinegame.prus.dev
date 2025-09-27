@props(['class' => ''])

<div
     {{ $attributes->merge(['class' => 'bg-white dark:bg-white/[3%] border border-neutral-200 dark:border-white/10 rounded-md shadow-xs p-6 ' . $class]) }}>
    {{ $slot }}
</div>
