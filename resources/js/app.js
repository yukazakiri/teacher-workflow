import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import formbricks from "@formbricks/js";
// Set Alpine globally immediately
window.Alpine = Alpine;

formbricks.setup({
    environmentId: "cma82f7r15cpgxy01kc6ojjvm",
    appUrl: "https://app.formbricks.com",
});

// Apply Alpine plugins
Alpine.plugin(collapse);

// Import and initialize Echo (should set window.Echo)
// import "./echo"; // Removed - Filament handles this now

// No longer delay Alpine start until livewire:init
// Alpine will start automatically or be started by other integrations (like Filament)
