<template>
    <div
        :class="{
            'bg-light-background dark:bg-dark-foreground': isClicked && highlight,
            'hover:bg-light-background dark:hover:bg-dark-foreground': highlight,
        }"
        class="flex select-none items-center rounded-xl border-2 border-dashed border-transparent px-2.5 py-2 cursor-pointer"
        :draggable="canDrag"
        spellcheck="false"
        style="margin-bottom: 1rem;"
    >
        <!--MultiSelecting for the mobile version-->
        <CheckBox v-if="isMultiSelectMode" v-model="isChecked" :is-clicked="isClicked" class="mr-5" />

        <!--Item thumbnail-->
        <div class="relative w-16 shrink-0">
            <!--Member thumbnail for team folders-->
            <MemberAvatar
                v-if="canShowAuthor"
                :size="28"
                :is-border="true"
                :member="entry.data.relationships.creator"
                class="absolute right-1.5 -bottom-2 z-10"
            />

            <!--Emoji Icon-->
            <Emoji
                v-if="hasEmoji"
                :emoji="entry.data.attributes.emoji"
                class="ml-1 scale-110 transform text-5xl"
            />

            <!--Folder Icon-->
            <FolderIcon v-else-if="isFolder" :item="entry" />

            <!--File Icon-->
            <FileIconThumbnail
                v-else-if="!isImage || !hasThumbnail"
                :entry="entry"
                class="pr-2"
            />

            <!--Image thumbnail-->
            <img
                v-else-if="isImage && hasThumbnail"
                class="ml-0.5 h-12 w-12 rounded object-cover pointer-events-none"
                :src="imageSrc"
                :alt="itemName"
                loading="lazy"
                @error="replaceByOriginal"
            />
        </div>

        <!--Item Info-->
        <div class="pl-2 min-w-0">
            <!--Item Title-->
            <span
                class="item-name mb-0.5 block overflow-hidden text-ellipsis whitespace-nowrap text-sm font-bold lg:pr-0 pr-4"
                :class="{
                    'hover:underline cursor-text': canEditName,
                    'lg:!pr-16': isMobileHandler,
                }"
                ref="name"
                @input="renameItem"
                @keydown.delete.stop
                @click.stop
                @keydown.enter="$refs.name.blur()"
                :contenteditable="canEditName"
            >
                {{ itemName }}
            </span>

            <!--Item sub line-->
            <div class="flex items-center">
                <!--Shared Icon-->
                <link-icon 
                    v-if="isShared" 
                    size="12" 
                    class="text-theme dark-text-theme vue-feather mr-1.5" 
                />

                <!--File & Image sub line-->
                <small v-if="!isFolder" class="block text-xs text-gray-500 dark:text-gray-500">
                    {{ entry.data.attributes.filesize }}, {{ timeStamp }}
                </small>

                <!--Folder sub line-->
                <small v-else class="block text-xs text-gray-500 dark:text-gray-500">
                    {{ folderSubline }}, {{ timeStamp }}
                </small>
            </div>
        </div>

        <!-- Mobile item action button-->
        <div v-if="isMobileHandler && !isMultiSelectMode" class="relative flex-grow pr-1 text-right">
            <div
                v-if="!isMobile"
                @mouseup.stop="$openInDetailPanel(entry)"
                class="absolute right-10 -mr-4 -translate-y-2/4 transform p-2.5"
            >
                <eye-icon size="18" class="vue-feather inline-block opacity-30" />
            </div>
            <div @mouseup.stop="showItemActions" class="absolute right-0 -mr-4 -translate-y-2/4 transform p-2.5">
                <MoreVerticalIcon size="18" class="vue-feather text-theme dark-text-theme inline-block" />
            </div>
        </div>
    </div>
</template>

<script>
import Emoji from '../../Emoji/Emoji'
import FolderIcon from '../../Icons/FolderIcon'
import { LinkIcon, MoreVerticalIcon, EyeIcon } from 'vue-feather-icons'
import FileIconThumbnail from '../../Icons/FileIconThumbnail'
import MemberAvatar from '../Others/MemberAvatar'
import CheckBox from '../../Inputs/CheckBox'
import { debounce } from 'lodash'
import { mapGetters } from 'vuex'
import { events } from '../../../bus'

export default {
    name: 'ItemList',
    components: {
        FileIconThumbnail,
        MoreVerticalIcon,
        MemberAvatar,
        FolderIcon,
        CheckBox,
        LinkIcon,
        EyeIcon,
        Emoji,
    },
    props: {
        mobileHandler: Boolean,
        highlight: Boolean,
        entry: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            itemName: '',
            isChecked: false,
            imageSrc: null,
            imageError: false,
        }
    },
    computed: {
        ...mapGetters(['isMultiSelectMode', 'clipboard', 'user', 'sharedDetail']),
        
        // Cache para tipo de item
        itemType() {
            return this.entry.data.type
        },
        isVideo() {
            return this.itemType === 'video'
        },
        isAudio() {
            return this.itemType === 'audio'
        },
        isFile() {
            return this.itemType === 'file'
        },
        isImage() {
            return this.itemType === 'image'
        },
        isFolder() {
            return this.itemType === 'folder'
        },
        
        // Cache para atributos
        attributes() {
            return this.entry.data.attributes
        },
        hasEmoji() {
            return !!this.attributes.emoji
        },
        hasThumbnail() {
            return !!this.attributes.thumbnail
        },
        
        // Otimização de condições complexas
        isClicked() {
            const id = this.entry.data.id
            return this.clipboard.some(element => element.data.id === id)
        },
        isMobileHandler() {
            return this.mobileHandler && this.$isMobile()
        },
        isMobile() {
            return this.$isMobile()
        },
        isShared() {
            return this.$checkPermission('master') && this.entry.data.relationships.shared
        },
        isDeleted() {
            return !!this.attributes.deleted_at
        },
        
        timeStamp() {
            return this.isDeleted
                ? this.$t('item_thumbnail.deleted_at', { time: this.attributes.deleted_at })
                : this.attributes.created_at
        },
        
        canEditName() {
            if (this.isMobile) return false
            if (this.$isThisRoute(this.$route, ['Trash'])) return false
            if (this.$checkPermission('visitor')) return false
            return !(this.sharedDetail?.attributes.type === 'file')
        },
        
        folderItems() {
            return this.isDeleted
                ? this.attributes.trashed_items
                : this.attributes.items
        },
        
        folderSubline() {
            return this.folderItems === 0 
                ? this.$t('empty') 
                : this.$tc('folder.item_counts', this.folderItems)
        },
        
        canShowAuthor() {
            if (this.isFolder) return false
            const creator = this.entry.data.relationships.creator
            return creator && this.user.data.id !== creator.data.id
        },
        
        canDrag() {
            return !this.isDeleted && this.$checkPermission(['master', 'editor'])
        },
    },
    watch: {
        isChecked(val) {
            if (val) {
                this.$store.commit('ADD_ITEM_TO_CLIPBOARD', this.entry)
            } else {
                this.$store.commit('REMOVE_ITEM_FROM_CLIPBOARD', this.entry.data.id)
            }
        },
        
        // Observa mudanças no entry para atualizar dados
        'entry.data.attributes.name'(newName) {
            this.itemName = newName
        }
    },
    methods: {
        getImageSrc() {
            if (!this.isImage) return
            
            const mimetype = this.attributes.mimetype
            if (mimetype === 'svg' || mimetype === 'image/svg+xml') {
                this.imageSrc = this.attributes.file_url
            } else if (this.hasThumbnail) {
                this.imageSrc = this.attributes.thumbnail.xs
            } else {
                this.imageSrc = this.attributes.file_url
            }
        },
        
        replaceByOriginal() {
            if (!this.imageError) {
                this.imageError = true
                this.imageSrc = this.attributes.file_url
            }
        },
        
        showItemActions() {
            this.$store.commit('CLIPBOARD_CLEAR')
            this.$store.commit('ADD_ITEM_TO_CLIPBOARD', this.entry)
            this.$showMobileMenu('file-menu')
            events.$emit('mobile-context-menu:show', this.entry)
        },
        
        // Debounce já otimizado
        renameItem: debounce(function(e) {
            const newName = e.target.innerText.trim()
            if (!newName || newName === this.itemName) return
            
            this.$store.dispatch('renameItem', {
                id: this.entry.data.id,
                type: this.itemType,
                name: newName,
            })
        }, 300),
    },
    
    created() {
        // Inicializa dados
        this.itemName = this.attributes.name
        
        if (this.isImage) {
            this.getImageSrc()
        }
        
        // Event listeners
        this._listeners = {
            changeName: (item) => {
                if (this.entry.data.id === item.id) {
                    this.itemName = item.name
                }
            },
            newFolderFocus: (id) => {
                if (!this.isMobile && this.entry.data.id === id) {
                    this.$nextTick(() => {
                        this.$refs.name?.focus()
                        document.execCommand('selectAll')
                    })
                }
            }
        }
        
        events.$on('change:name', this._listeners.changeName)
        events.$on('newFolder:focus', this._listeners.newFolderFocus)
    },
    
    beforeDestroy() {
        // Limpa event listeners
        if (this._listeners) {
            events.$off('change:name', this._listeners.changeName)
            events.$off('newFolder:focus', this._listeners.newFolderFocus)
        }
    }
}
</script>