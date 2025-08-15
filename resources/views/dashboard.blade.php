<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
    {{-- Sales Card --}}
    <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 flex flex-col justify-between shadow-md">
        <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sales</div>
            <div class="text-blue-500 bg-blue-100 dark:bg-blue-900/20 p-2 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3v18h18M3 9h18M9 3v6M15 3v6" />
                </svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-4">৳ 25,000</div>
        <div class="text-xs text-green-600 dark:text-green-400 mt-1">↑ 12% from last month</div>
    </div>

    {{-- Purchase Card --}}
    <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 flex flex-col justify-between shadow-md">
        <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Purchases</div>
            <div class="text-red-500 bg-red-100 dark:bg-red-900/20 p-2 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007.5 17h9a1 1 0 00.9-.6L20 13M7 13l-1-5h16" />
                </svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-4">৳ 18,500</div>
        <div class="text-xs text-red-600 dark:text-red-400 mt-1">↓ 5% from last month</div>
    </div>

    {{-- Accounts Card --}}
    <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 flex flex-col justify-between shadow-md">
        <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Accounts</div>
            <div class="text-purple-500 bg-purple-100 dark:bg-purple-900/20 p-2 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v16m8-8H4" />
                </svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-4">৳ 40,300</div>
        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Updated just now</div>
    </div>
</div>

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
           <flux:avatar src="https://pbs.twimg.com/profile_images/1669240972327387137/38Ba-mJj_400x400.jpg" />
        </div>
        
    </div>
</x-layouts.app>
