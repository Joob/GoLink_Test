<template>
    <div class="flex select-none items-center rounded-xl" spellcheck="false" v-if="item && item.data">
        <!--Item thumbnail-->
        <div class="relative w-16 shrink-0">
            <!--Member thumbnail for team folders-->
            <MemberAvatar
                v-if="user && canShowAuthor && item.data.relationships && item.data.relationships.creator"
                :size="28"
                :is-border="true"
                :member="item.data.relationships.creator"
                class="absolute right-1.5 -bottom-2 z-10"
            />

            <!--Emoji Icon-->
            <Emoji
                v-if="item.data.attributes && item.data.attributes.emoji"
                :emoji="item.data.attributes.emoji"
                class="ml-1 scale-110 transform text-5xl"
            />

            <!--Folder Icon-->
            <FolderIcon v-if="isFolder && (!item.data.attributes || !item.data.attributes.emoji)" :item="item" />

            <!--File Icon-->
            <FileIconThumbnail
                v-if="isFile || isVideo || isAudio || (isImage && (!item.data.attributes || !item.data.attributes.thumbnail))"
                :entry="item"
                class="pr-2"
            />

            <!--Image thumbnail-->
            <img
                v-if="isImage && item.data.attributes && item.data.attributes.thumbnail"
                class="ml-0.5 h-12 w-12 rounded object-cover"
                :src="item.data.attributes.thumbnail.xs"
                :alt="item.data.attributes.name || 'Image'"
                loading="lazy"
            />
        </div>

        <!--Item Info-->
        <div class="pl-2 min-w-0">
            <!--Item Title-->
            <b class="mb-0.5 block overflow-hidden text-ellipsis whitespace-nowrap text-sm hover:underline">
                {{ item.data.attributes ? item.data.attributes.name : 'Unknown' }}
            </b>

            <!--Item sub line-->
            <div class="flex items-center">
                <!--Shared Icon-->
                <div v-if="$checkPermission('master') && item.data.relationships && item.data.relationships.shared">
                    <link-icon size="12" class="text-theme dark-text-theme vue-feather mr-1.5" />
                </div>

                <!--File & Image sub line-->
                <small v-if="!isFolder" class="block text-xs text-gray-500">
                    {{ item.data.attributes ? item.data.attributes.filesize : '' }}, {{ timeStamp }}
                </small>

                <!--Folder sub line-->
                <small v-if="isFolder" class="block text-xs text-gray-500">
                    {{ folderItems === 0 ? $t('empty') : $tc('folder.item_counts', folderItems) }},
                    {{ timeStamp }}
                </small>
            </div>
        </div>
    </div>
</template>

<script>
import { LinkIcon, EyeIcon } from 'vue-feather-icons'
import FileIconThumbnail from '../../Icons/FileIconThumbnail'
import MemberAvatar from '../Others/MemberAvatar'
import Emoji from '../../Emoji/Emoji'
import { mapGetters } from 'vuex'
import FolderIcon from '../../Icons/FolderIcon'

export default {
    name: 'ThumbnailItem',
    props: ['setFolderIcon', 'item', 'info'],
    components: {
        FileIconThumbnail,
        MemberAvatar,
        FolderIcon,
        Emoji,
        LinkIcon,
        EyeIcon,
    },
    computed: {
        ...mapGetters(['isMultiSelectMode', 'clipboard', 'user']),
        isClicked() {
            return this.clipboard.some((element) => element.data.id === this.item.data.id)
        },
        isFolder() {
            return this.item && this.item.data && this.item.data.type === 'folder'
        },
        isVideo() {
            return this.item && this.item.data && this.item.data.type === 'video'
        },
        isAudio() {
            return this.item && this.item.data && this.item.data.type === 'audio'
        },
        isFile() {
            return this.item && this.item.data && this.item.data.type === 'file'
        },
        isImage() {
            return this.item && this.item.data && this.item.data.type === 'image'
        },
        timeStamp() {
            return this.item.data.attributes.deleted_at
                ? this.$t('item_thumbnail.deleted_at', {
                      time: this.item.data.attributes.deleted_at,
                  })
                : this.item.data.attributes.created_at
        },
        canEditName() {
            return (
                !this.$isMobile() &&
                !this.$isThisRoute(this.$route, ['Trash']) &&
                !this.$checkPermission('visitor') &&
                !(this.sharedDetail && this.sharedDetail.attributes.type === 'file')
            )
        },
        folderItems() {
            return this.item.data.attributes.deleted_at
                ? this.item.data.attributes.trashed_items
                : this.item.data.attributes.items
        },
        canShowAuthor() {
            return !this.isFolder && (this.item.data.relationships.creator && this.user.data.id !== this.item.data.relationships.creator.data.id)
        },
        canDrag() {
            return !this.isDeleted && this.$checkPermission(['master', 'editor'])
        },
    },
}
</script>
