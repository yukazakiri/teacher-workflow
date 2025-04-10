<script setup>
const props = defineProps({
    wire: {},
    mingleData: {},
})

const {wire, mingleData} = props

const message = mingleData.message

console.log(message) // 'Message in a bottle 🍾'

</script>

<template>
    <div>
Hellow
    </div>
</template>
