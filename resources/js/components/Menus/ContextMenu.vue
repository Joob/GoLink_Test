<template>
    <div
        v-show="isVisible"
        :style="{ top: positionY + 'px', left: positionX + 'px' }"
        @click="closeAndResetContextMenu"
        class="fixed z-20 w-60 select-none overflow-hidden rounded-xl bg-white shadow-lg dark:bg-2x-dark-foreground"
        ref="contextmenu"
    >
        <div class="w-full">
            <!--Show empty select contextmenu-->
            <slot name="empty-select" v-if="!item" />

            <!--Show single select contextmenu-->
            <slot name="single-select" v-if="isMultiSelectContextMenu" />

            <!--Show multiple select contextmenu-->
            <slot name="multiple-select" v-if="!isMultiSelectContextMenu" />
        </div>
    </div>
</template>

<script>
import { events } from '../../bus'
import { mapGetters } from 'vuex'

export default {
    name: 'ContextMenu',
    computed: {
        ...mapGetters(['clipboard', 'user']),
        isMultiSelectContextMenu() {
            // If is context Menu open on multi selected items open just options for the multi selected items
            if (this.clipboard.length > 1 && this.clipboard.includes(this.item)) return false

            // If is context Menu open for the non selected item open options for the single item
            if (this.clipboard.length < 2 || !this.clipboard.includes(this.item)) return true
        },
    },
    data() {
        return {
            item: undefined,
            isVisible: false,
            positionX: 0,
            positionY: 0,
        }
    },
    methods: {
        closeAndResetContextMenu() {
            // Close context menu
            this.isVisible = false

            // Reset item container
            this.item = undefined
        },
        handleClickOutside(event) {
            // Close menu if clicking outside of it
            if (this.isVisible && !this.$refs.contextmenu.contains(event.target)) {
                this.closeAndResetContextMenu()
            }
        },
        showContextMenu(event) {

            // Show context menu
            this.isVisible = true

            let menu = this.$refs.contextmenu

            let hiddenAreaX = window.innerWidth - event.clientX - menu.clientWidth - 25
            let hiddenAreaY = window.innerHeight - event.clientY - menu.clientHeight - 25

            this.positionX = hiddenAreaX < 0 ? event.clientX + hiddenAreaX : event.clientX
            this.positionY = hiddenAreaY < 0 ? event.clientY + hiddenAreaY : event.clientY
        },
        contextMenuShow: function(event,item){
            // Store item
            this.item = item

            // Show context menu
            setTimeout(() => this.showContextMenu(event, item), 10)
        },
        contextMenuCurrentFolder: function(folder){
            this.item = folder

            // Always show the menu when called (don't toggle)
            this.isVisible = true

            let container = document.getElementById('folder-actions')
            if (container) {
                let containerRect = container.getBoundingClientRect()
                this.positionX = containerRect.x
                this.positionY = containerRect.y + 20
            }
        }
    },
    created() {
        events.$on('context-menu:hide', this.closeAndResetContextMenu)
        events.$on('context-menu:show', this.contextMenuShow)
        events.$on('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$on('context-menu:close-all', this.closeAndResetContextMenu)
        
        // Add global click listener to close menu when clicking outside
        document.addEventListener('click', this.handleClickOutside)
    },
    beforeDestroy () {
        events.$off('context-menu:hide', this.closeAndResetContextMenu)
        events.$off('context-menu:show', this.contextMenuShow)
        events.$off('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$off('context-menu:close-all', this.closeAndResetContextMenu)
        
        // Remove global click listener
        document.removeEventListener('click', this.handleClickOutside)
    }
}
</script>
