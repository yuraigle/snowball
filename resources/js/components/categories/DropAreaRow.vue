<template>
    <tr class="drop-area border-bottom"
        :class="{ 'drag-over': isShown }"
        @dragover.prevent
        @dragenter="isShown = true"
        @dragleave="isShown = false"
        @drop="onDrop"
    >
        <td colspan="4" :data-id="id"></td>
    </tr>
</template>

<script>
export default {
    props: ['id', 'shown'],
    data() {
        return {
            isShown: false
        }
    },
    methods: {
        onDrop(e) {
            console.log(e.dataTransfer.getData('text/plain') + ' dropped to ' + e.target.dataset.id);
            this.isShown = false;
            this.$emit('dropped', {
                transfer: e.dataTransfer.getData('text/plain'),
                to: e.target.dataset.id
            });
        }
    }
}
</script>


<style scoped>
.drop-area {
    height: 3px !important;
}

.drop-area.drag-over {
    background: #3f8db9;
}
</style>
