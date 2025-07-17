<template>
    <section
        class="content-sidebar z-[9] hidden w-52 flex-none select-none overflow-y-auto bg-light-background pt-6 dark:bg-dark-background lg:block xl:w-56"
        id="content-sidebar"
        @click.self="deselect"
    >
        <slot />
    </section>
</template>

<script>
import ItemHandler from '../EntriesView/ItemHandler'
import { events } from '../../bus'

export default {
    name: 'ContentSidebar',
    components: {
        ItemHandler,
    },
    methods: {
        deselect() {
            // Hide context menu
            events.$emit('context-menu:hide')

            // Clear clipboard
            this.$store.commit('CLIPBOARD_CLEAR')
        },
    },
    
}
</script>
