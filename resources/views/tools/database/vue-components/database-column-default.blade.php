<template id="voyager-db-column-default-template">
    <input
        :value="column.default"
        :type="options.type"
        :step="options.step"
        :min="options.min"
        :max="options.max"
        :class="options.class"
        :disabled="options.disabled"
        @input="onDefaultInput"
        class="form-control">
</template>

