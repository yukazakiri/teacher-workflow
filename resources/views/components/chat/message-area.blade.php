{{-- Scrollable message container --}}
<div class="flex-1 overflow-y-auto p-4 space-y-4 scrollable">
    {{-- Message Group (Same User, Recent) --}}
    <div class="flex items-start space-x-3 group relative pt-1 pb-1 pr-10 hover:bg-gray-50 dark:hover:bg-white/5 rounded">
        {{-- Avatar --}}
        @php 
            $username = 'Username1'; 
            $bgColor = 'eebebe'; // Your primary color hex without #
            $textColor = 'ffffff'; // White text for contrast
        @endphp
        <img class="w-10 h-10 rounded-full flex-shrink-0 mt-1" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=40" alt="{{ $username }}">
        {{-- Message Content --}}
        <div class="flex-1">
            {{-- User Info & Timestamp --}}
            <div class="flex items-baseline space-x-2 mb-0.5">
                <span class="font-semibold text-gray-950 dark:text-white">{{ $username }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">Today at 10:30 AM</span>
            </div>
            {{-- Message Text --}}
            <p class="text-gray-800 dark:text-gray-200">This is the first message from {{ $username }}. Lorem ipsum dolor sit amet.</p>
        </div>
        {{-- Add reactions/options on hover - Align with Filament card actions --}}
        <div class="absolute top-0 right-0 -mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 flex space-x-0.5">
            {{-- Use Filament icon button styles --}}
            <button title="Add Reaction" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" /></svg>
            </button>
             <button title="Edit" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </button>
            <button title="More" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            </button>
        </div>
    </div>

    {{-- Subsequent Message (Same User, Condensed) --}}
    <div class="group relative pl-[52px] pr-10 -mt-2 pb-1 hover:bg-gray-50 dark:hover:bg-white/5 rounded">
         {{-- Hover timestamp --}}
         <span class="absolute left-0 top-0 bottom-0 w-12 text-[10px] text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 flex items-center justify-center pointer-events-none">10:31</span>
         {{-- Message Text --}}
        <p class="text-gray-800 dark:text-gray-200">This is a subsequent message, so no avatar or username needed.</p>
         {{-- Add reactions/options on hover --}}
         <div class="absolute top-0 right-0 -mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 flex space-x-0.5">
             <button title="Add Reaction" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" /></svg>
            </button>
             <button title="Edit" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </button>
            <button title="More" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            </button>
        </div>
    </div>

    {{-- Message Group (Different User) --}}
    <div class="flex items-start space-x-3 group relative pt-4 pb-1 pr-10 hover:bg-gray-50 dark:hover:bg-white/5 rounded">
        {{-- Avatar --}}
        @php 
            $username = 'AnotherUser'; 
            $bgColor = 'eebebe'; // Your primary color hex without #
            $textColor = 'ffffff'; // White text for contrast
        @endphp
        <img class="w-10 h-10 rounded-full flex-shrink-0 mt-1" src="https://ui-avatars.com/api/?name={{ urlencode($username) }}&color={{ $textColor }}&background={{ $bgColor }}&size=40" alt="{{ $username }}">
        {{-- Message Content --}}
        <div class="flex-1">
            {{-- User Info & Timestamp --}}
            <div class="flex items-baseline space-x-2 mb-0.5">
                {{-- Use a theme color for username emphasis if desired --}}
                <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $username }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">Today at 10:35 AM</span>
            </div>
            {{-- Message Text --}}
            <p class="text-gray-800 dark:text-gray-200">This is a message from a different user.</p>
        </div>
         {{-- Add reactions/options on hover --}}
        <div class="absolute top-0 right-0 mt-3 mr-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 flex space-x-0.5">
             <button title="Add Reaction" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" /></svg>
            </button>
             <button title="Edit" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </button>
            <button title="More" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            </button>
        </div>
    </div>

     {{-- Message with Embed --}}
    <div class="group relative pl-[52px] pr-10 -mt-2 pb-1 hover:bg-gray-50 dark:hover:bg-white/5 rounded">
        {{-- Hover timestamp --}}
         <span class="absolute left-0 top-0 bottom-0 w-12 text-[10px] text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 flex items-center justify-center pointer-events-none">10:36</span>
        {{-- Message Text --}}
        <p class="text-gray-800 dark:text-gray-200">Check out this link:</p>
        {{-- Embed Example - Style like a subtle card --}}
        <div class="mt-1.5 p-3 bg-gray-100 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700/50 max-w-md">
            <a href="#" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">Cool Website Title</a>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">This is a short description of the website content. It might be fetched automatically from the link meta tags.</p>
            {{-- Optional Image --}}
            {{-- <img src="https://via.placeholder.com/400x200" alt="Website Preview" class="mt-2 rounded max-w-full h-auto"> --}}
        </div>
         {{-- Add reactions/options on hover --}}
        <div class="absolute top-0 right-0 -mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-white dark:bg-gray-800 shadow-md rounded-lg border border-gray-200 dark:border-gray-700 p-0.5 flex space-x-0.5">
             <button title="Add Reaction" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm4.5 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" /></svg>
            </button>
             <button title="Edit" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
            </button>
            <button title="More" class="fi-icon-btn inline-flex items-center justify-center rounded-full disabled:pointer-events-none disabled:opacity-70 w-6 h-6 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:text-gray-500 dark:focus:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 focus:outline-none">
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
            </button>
        </div>
    </div>

</div>

</rewritten_file>