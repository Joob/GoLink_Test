<template>
    <div>
        <ContextMenu>
            <template v-slot:empty-select>
                <OptionGroup>
                    <Option @click.native="$emptyTrash" :title="$t('empty_trash')" icon="empty-trash" />
                </OptionGroup>
            </template>

            <template v-slot:single-select v-if="item">
                <OptionGroup>
                    <Option
                        @click.native="$restoreFileOrFolder(item)"
                        v-if="item"
                        :title="$t('restore')"
                        icon="restore"
                    />
                    <Option
                        @click.native="$deleteFileOrFolder(item)"
                        v-if="item"
                        :title="$t('delete')"
                        icon="trash"
                    />
                    <!--<Option @click.native="$emptyTrash" :title="$t('empty_trash')" icon="empty-trash" />-->
                </OptionGroup>
                <OptionGroup>
                    <Option @click.native="$openInDetailPanel(item)" :title="$t('detail')" icon="detail" />
                    <!--<Option
                        @click.native="$downloadSelection(item)"
                        :title="$t('download')"
                        icon="download"
                    />-->
                </OptionGroup>
            </template>

            <template v-slot:multiple-select v-if="item">
                <OptionGroup>
                    <Option
                        @click.native="$restoreFileOrFolder(item)"
                        v-if="item"
                        :title="$t('restore')"
                        icon="restore"
                    />
                    <Option @click.native="$deleteFileOrFolder(item)" :title="$t('delete')" icon="trash" />
                    <!--<Option @click.native="$emptyTrash" :title="$t('empty_trash')" icon="empty-trash" />-->
                </OptionGroup>
                <!--<OptionGroup>
                    <Option @click.native="$downloadSelection()" :title="$t('download')" icon="download" />
                </OptionGroup>-->
            </template>
        </ContextMenu>

        <MobileContextMenu>
            <OptionGroup v-if="item">
                <Option
                    @click.native="$restoreFileOrFolder(item)"
                    v-if="item"
                    :title="$t('restore')"
                    icon="restore"
                />
                <Option @click.native="$deleteFileOrFolder(item)" :title="$t('delete')" icon="trash" />
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
            <MobileActionButton @click.native="$emptyTrash" icon="trash">
                {{ $t('empty_trash') }}
            </MobileActionButton>
            <MobileActionButton @click.native="$enableMultiSelectMode" icon="check-square">
                {{ $t('select') }}
            </MobileActionButton>
            <MobileActionButton @click.native="$showMobileMenu('file-sorting')" icon="preview-sorting">
                {{ $t('preview_sorting.preview_sorting_button') }}
            </MobileActionButton>
        </FileActionsMobile>

        <EmptyFilePage>
            <h1 class="title">{{ $t('your_trash_is_empty') }}</h1>
			<p class="description">
				{{ $t('your_trash_is_empty_desc') }}
			</p>
        </EmptyFilePage>

        <FileBrowser />

        <MobileMultiSelectToolbar>
            <ToolbarButton
                @click.native="$deleteFileOrFolder()"
                class="mr-4"
                source="trash"
                :class="{ 'is-inactive': clipboard.length < 1 }"
                :action="$t('delete')"
            />
            <!--<ToolbarButton
                @click.native="$downloadSelection(item)"
                source="download"
                :action="$t('download_item')"
            />-->
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
    name: 'Trash',
    components: {
        MobileActionButtonUpload,
        MobileMultiSelectToolbar,
        MobileActionButton,
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
        ...mapGetters(['clipboard']),
    },
    data() {
        return {
            item: undefined,
        }
    },
    created() {
		this.$store.dispatch('getTrash', {page: 1, id: this.$route.params.id})

        //this.$getDataByLocation(1)

        events.$on('context-menu:show', (event, folder) => (this.item = folder))
        events.$on('mobile-context-menu:show', (folder) => (this.item = folder))

        events.$on('action:confirmed', (data) => {
            if (data.operation === 'remove-permanent-folders-files')

                this.$store.dispatch('emptyTrash')

                //this.$store.dispatch('getAppData')
                //this.$store.dispatch('getFolderTree')

                events.$emit('toaster', {
                    type: 'danger',
                    message: this.$t('toaster.permanently_removing_files'),
                })
        })
    },
    destroyed() {
        events.$off('action:confirmed')
    },
}
</script>
