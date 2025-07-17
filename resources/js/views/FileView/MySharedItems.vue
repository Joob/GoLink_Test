<template>
    <div>
        <ContextMenu>
            <template v-slot:single-select v-if="item">
                <OptionGroup v-if="isFolder">
                    <Option
                        @click.native="$toggleFavourites(item)"
                        :title="
                            isInFavourites
                                ? $t('remove_favourite')
                                : $t('add_to_favourites')
                        "
                        icon="favourites"
                    />
                </OptionGroup>
                <OptionGroup>
                    <Option
                        @click.native="$renameFileOrFolder(item)"
                        :title="$t('rename')"
                        icon="rename"
                    />
                    <Option @click.native="$deleteFileOrFolder(item)" :title="$t('delete')" icon="trash" />
                </OptionGroup>
                <OptionGroup>
                    <Option
                        @click.native="$shareFileOrFolder(item)"
                        :title="
                            item.data.relationships.shared ? $t('edit_sharing') : $t('share')
                        "
                        icon="share"
                    />
                </OptionGroup>
                <OptionGroup>
                    <Option @click.native="$openInDetailPanel(item)" :title="$t('detail')" icon="detail" />
                    <Option
                        @click.native="$downloadSelection(item)"
                        :title="$t('download')"
                        icon="download"
                    />
                </OptionGroup>
            </template>

            <template v-slot:multiple-select v-if="item">
                <OptionGroup v-if="!hasFile">
                    <Option
                        @click.native="$toggleFavourites(item)"
                        :title="
                            isInFavourites
                                ? $t('remove_favourite')
                                : $t('add_to_favourites')
                        "
                        icon="favourites"
                    />
                </OptionGroup>
                <OptionGroup>
                    <Option @click.native="$shareCancel" :title="$t('cancel_sharing')" icon="share" />
                    <Option @click.native="$deleteFileOrFolder(item)" :title="$t('delete')" icon="trash" />
                </OptionGroup>
                <OptionGroup>
                    <Option @click.native="$downloadSelection()" :title="$t('download')" icon="download" />
                </OptionGroup>
            </template>
        </ContextMenu>

        <MobileContextMenu>
            <OptionGroup>
                <Option @click.native="$renameFileOrFolder(item)" :title="$t('rename')" icon="rename" />
                <Option @click.native="$deleteFileOrFolder(item)" :title="$t('delete')" icon="trash" />
            </OptionGroup>
            <br>
            <OptionGroup>
                <Option
                    @click.native="$shareFileOrFolder(item)"
                    :title="
                        item && item.data.relationships.shared
                            ? $t('edit_sharing')
                            : $t('share')
                    "
                    icon="share"
                />
            </OptionGroup>
            <!--<OptionGroup>
                <Option @click.native="$downloadSelection(item)" :title="$t('download')" icon="download" />
            </OptionGroup>-->
        </MobileContextMenu>

        <FileActionsMobile>
            <MobileActionButton @click.native="$openSpotlight()" icon="search">
                {{ $t('spotlight') }}
            </MobileActionButton>
            <MobileActionButton @click.native="$showMobileMenu('file-filter')" icon="filter">
                {{ $getCurrentSectionName() }}
            </MobileActionButton>
            <MobileActionButton @click.native="$enableMultiSelectMode" icon="check-square">
                {{ $t('select') }}
            </MobileActionButton>
            <MobileActionButton @click.native="$showMobileMenu('file-sorting')" icon="preview-sorting">
                {{ $t('preview_sorting.preview_sorting_button') }}
            </MobileActionButton>
        </FileActionsMobile>

        <EmptyFilePage>
            <h1 class="title">
				{{ $t('shared.empty_shared') }}
			</h1>
			<p class="description">
                {{ $t('shared.empty_shared_desc') }}
            </p>
        </EmptyFilePage>

        <FileBrowser />

        <MobileMultiSelectToolbar>
            <!--<ToolbarButton
                @click.native="$downloadSelection(item)"
                source="download"
                :action="$t('download_item')"
				class="mr-4"
            />-->
            <ToolbarButton @click.native="$shareCancel" source="shared-off" />
        </MobileMultiSelectToolbar>
    </div>
</template>

<script>
import EmptyFilePage from '../../components/EntriesView/EmptyFilePage'
import FileActionsMobile from '../../components/Mobile/FileActionsMobile'
import MobileActionButtonUpload from '../../components/UI/Buttons/MobileActionButtonUpload'
import MobileActionButton from '../../components/UI/Buttons/MobileActionButton'
import MobileMultiSelectToolbar from '../../components/Layout/Toolbars/MobileMultiSelectToolbar'
import MobileContextMenu from '../../components/Menus/MobileContextMenu'
import ToolbarButton from '../../components/UI/Buttons/ToolbarButton'
import FileBrowser from '../../components/EntriesView/FileBrowser'
import ContextMenu from '../../components/Menus/ContextMenu'
import OptionGroup from '../../components/Menus/Components/OptionGroup'
import Option from '../../components/Menus/Components/Option'
import { mapGetters } from 'vuex'
import { events } from '../../bus'

export default {
    name: 'MySharedItems',
    components: {
        MobileActionButtonUpload,
        MobileActionButton,
        MobileMultiSelectToolbar,
        MobileContextMenu,
        ToolbarButton,
        OptionGroup,
        FileBrowser,
        ContextMenu,
        Option,
        FileActionsMobile,
        EmptyFilePage,
    },
    computed: {
        ...mapGetters(['clipboard', 'user']),
        isFolder() {
            return this.item && this.item.type === 'folder'
        },
        isInFavourites() {
            return this.user.data.relationships.favourites.find((el) => el.id === this.item.id)
        },
        hasFile() {
            return this.clipboard.find((item) => item.type !== 'folder')
        },
    },
    data() {
        return {
            item: undefined,
        }
    },
    methods: {
        contextMenuShow: function(event, item) {this.item = item},
        contextMenuCurrentFolder: function(folder){this.item = folder},
        mobileContextMenuShow: function(item){this.item = item},

        actionConfirmed: function(data, entry, store) {
            if (data.operation === 'remove-folders-files') {
                if (!this.$store.getters.clipboard.includes(entry)) {
                    this.$store.dispatch('deleteItem', entry)
                    //this.$getDataByLocation(1)
                } else {
                    this.$store.dispatch('deleteItem')
                    //this.$getDataByLocation(1)
                }
            }
        },
    },
    created() {
        this.$store.dispatch('getMySharedItems', 1)

        events.$on('context-menu:show', this.contextMenuShow)
        events.$on('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$on('mobile-context-menu:show', this.mobileContextMenuShow)
        events.$on('action:confirmed', this.actionConfirmed)
    },
    beforeUnmount () {
        events.$off('context-menu:show', this.contextMenuShow)
        events.$off('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$off('mobile-context-menu:show', this.mobileContextMenuShow)
        events.$off('action:confirmed', this.actionConfirmed)
    },
    destroyed() {
        events.$off('action:confirmed')
    },
}
</script>
