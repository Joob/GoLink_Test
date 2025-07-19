<template>
    <div class="hidden lg:block">
        <div class="flex items-center justify-between py-3">
            <NavigationBar />

            <div class="flex items-center">
                <!--Create button-->
                <PopoverWrapper v-if="canEdit">
                    <ToolbarButton
                        @click.stop.native="showCreateMenu"
                        source="cloud-plus"
                        :action="$t('create_something')"
                    />
                    <PopoverItem name="desktop-create" side="left">
                        <OptionGroup :title="$t('frequently_used')">
                            <OptionUpload :title="$t('upload_files')" type="file" />
                            <OptionUpload :title="$t('upload_folder')" type="folder" />
							<Option
								@click.stop.native="$openRemoteUploadPopup"
								:title="$t('remote_upload')"
								icon="remote-upload"
							/>
                        </OptionGroup>
                        <OptionGroup :title="$t('others')">
                            <Option
                                @click.native="$createFolder"
                                :title="$t('create_folder')"
                                icon="folder-plus"
                            />
                        </OptionGroup>
                    </PopoverItem>
                </PopoverWrapper>

                <!--Search bar-->
                <SearchBarButton class="ml-5 hidden lg:block xl:ml-8" />

                <!--File Controls-->
                <div class="ml-5 flex items-center xl:ml-8">    
                    <!-- GoLink CHANGE HERE -->
                        <!--Toggle Dark/Light mode-->
                        <div @click="$store.dispatch('toggleThemeMode')" :title="$t('dark_mode_toggle')">
                            <div
                                class="button-icon rounded-xl p-3 hover:bg-light-background dark:hover:bg-4x-dark-foreground"
                            >
                                <sun-icon v-if="isDarkMode" size="20" />
                                <moon-icon v-if="!isDarkMode" size="20" />
                            </div>
                        </div>
                    <!-- GoLink CHANGE HERE -->

                    <!--Action buttons-->
                    <div v-if="canEdit && !$isMobile()" class="flex items-center">
                        <ToolbarButton
                            @click.native="$moveFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canManipulate,
                            }"
                            source="move"
                            :action="$t('move')"
                        />
                        <ToolbarButton
                            @click.native="$deleteFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canManipulate,
                            }"
                            source="trash"
                            :action="$t('delete')"
                        />
                    </div>
                    <div v-if="!canEdit && !$isMobile()" class="flex items-center">
                        <ToolbarButton
                            @click.native="$downloadSelection(item)"
                            :class="{
                                'is-inactive': !canManipulate
                            }"
                            source="download"
                            :action="$t('download')"
                        />
                    </div>
                </div>

                <div class="ml-5 flex items-center xl:ml-8">
                    <div class="button-icon inline-block cursor-pointer rounded-xl p-2 hover:bg-light-background dark:hover:bg-4x-dark-foreground">
                        <language-switcher />
                    </div>
                </div>

                <!--View Controls-->
                <div class="ml-5 flex items-center xl:ml-8">
                    <PopoverWrapper>
                        <ToolbarButton
                            @click.stop.native="showSortingMenu"
                            source="preview-sorting"
                            :action="$t('sorting_view')"
                        />
                        <PopoverItem name="desktop-sorting" side="left">
                            <FileSortingOptions />
                        </PopoverItem>
                    </PopoverWrapper>
                    <ToolbarButton
                        @click.native="$store.dispatch('fileInfoToggle')"
                        :action="$t('info_panel')"
                        source="info"
                    />
                </div>
            </div>
        </div>

        <UploadProgress />
    </div>
</template>

<script>
import LanguageSwitcher from '../../Others/LanguageSwitcherDashboard'
import PopoverWrapper from '../../UI/Popover/PopoverWrapper'
import FileSortingOptions from '../../Menus/FileSortingOptions'
import PopoverItem from '../../UI/Popover/PopoverItem'
import UploadProgress from '../../UI/Others/UploadProgress'
import NavigationBar from './NavigationBar'
import ToolbarButton from '../../UI/Buttons/ToolbarButton'
import OptionUpload from '../../Menus/Components/OptionUpload'
import OptionGroup from '../../Menus/Components/OptionGroup'
import SearchBarButton from '../../UI/Buttons/SearchBarButton'
import { MoonIcon, SunIcon,} from 'vue-feather-icons'
import { events } from '../../../bus'
import { mapGetters } from 'vuex'
import Option from '../../Menus/Components/Option'

export default {
    name: 'DesktopSharepageToolbar',
    components: {
        FileSortingOptions,
        LanguageSwitcher,
		SearchBarButton,
        UploadProgress,
        PopoverWrapper,
        NavigationBar,
        ToolbarButton,
        OptionUpload,
        OptionGroup,
        PopoverItem,
        MoonIcon,
        SunIcon,
        Option,
    },
    computed: {
        ...mapGetters(['isVisibleNavigationBars', 'currentTeamFolder', 'currentFolder', 'sharedDetail', 'clipboard', 'isDarkMode']),
        canEdit() {
            return this.sharedDetail && this.sharedDetail.data.attributes.permission === 'editor'
        },
        canManipulate() {
            return this.clipboard[0]
        },
    },
    methods: {
        showCreateMenu() {
            events.$emit('popover:open', 'desktop-create')
        },
        showSortingMenu() {
            events.$emit('popover:open', 'desktop-sorting')
        },
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
    data() {
        return {
            item: null
        }
    },
    created() {
        events.$on('context-menu:show', this.contextMenuShow)
        events.$on('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$on('mobile-context-menu:show', this.mobileContextMenuShow)
        events.$on('action:confirmed', this.actionConfirmed)
    },
    beforeDestroy() {
        events.$off('context-menu:show', this.contextMenuShow)
        events.$off('context-menu:current-folder', this.contextMenuCurrentFolder)
        events.$off('mobile-context-menu:show', this.mobileContextMenuShow)
        events.$off('action:confirmed', this.actionConfirmed)
    },
}
</script>
