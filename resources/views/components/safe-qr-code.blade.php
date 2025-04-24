<div class="flex justify-center">
    {{-- Directly use the helper to avoid potential 'this' context issues --}}
    {!! \App\Helpers\QrCodeHelper::generateSvg($url, $size ?? 200) !!}
</div> 