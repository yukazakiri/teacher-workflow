import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import './echo'

Alpine.plugin(collapse)

window.Alpine = Alpine

// Wait for Livewire to load before starting Alpine
document.addEventListener('livewire:init', () => {
    Alpine.start()
})
