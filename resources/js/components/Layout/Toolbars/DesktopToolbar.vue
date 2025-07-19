<template>
    <div class="hidden lg:block">
        <div class="flex items-center justify-between py-3">
            <NavigationBar />

            <div class="flex items-center">
                <!--Create button-->
                <PopoverWrapper>
                    <ToolbarButton
                        v-if="!$isThisRoute($route, ['Trash'])"
                        @click.stop.native="showCreateMenu"
                        source="cloud-plus"
                        :action="$t('create_something')"
                        :class="{
                            'is-inactive': canEmptyTrash,
                        }"
                    />
                    <PopoverItem name="desktop-create" side="left">
                        <OptionGroup
                            :title="$t('frequently_used')"
                        >
                            <OptionUpload
								:title="$t('upload_files')"
								type="file"
								:class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || canUploadInView || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
							/>
                            <OptionUpload
								:class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || canUploadFolderInView || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
								:title="$t('upload_folder')"
								type="folder"
							/>
                            <Option
								@click.stop.native="$openRemoteUploadPopup"
								:title="$t('remote_upload')"
								icon="remote-upload"
                                :class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || canUploadInView || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
							/>
                        </OptionGroup>
                        <OptionGroup :title="$t('others')">
                            <Option
                                @click.native="$createFolder"
                                :class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || canCreateFolder || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
                                :title="$t('create_folder')"
                                icon="folder-plus"
                            />
                            <Option
                                @click.stop.native="$createTeamFolder"
                                :title="$t('create_team_folder')"
                                icon="users"
                                :class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || canCreateTeamFolder || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
                            />
							<Option
								@click.native="$createFileRequest"
								:title="$t('create_file_request')"
								icon="upload-cloud"
                                :class="{
                                    'is-inactive': (isSharedWithMe && !canEdit) || isTeamFolderHomepage || isSharedWithMeHomepage,
                                }"
							/>
                        </OptionGroup>
                    </PopoverItem>
                </PopoverWrapper>

                <!--Search bar-->
                <SearchBarButton class="ml-5 hidden lg:block xl:ml-8" />

                <!--File Controls-->
                <div class="ml-5 flex items-center xl:ml-8">
                    <!--Team Heads-->
                    <PopoverWrapper v-if="$isThisRoute($route, ['TeamFolders', 'SharedWithMe'])">
                        <TeamMembersButton
                            @click.stop.native="showTeamFolderMenu"
                            size="32"
                            class="cursor-pointer rounded-lg py-0.5 pl-2 pr-0.5 hover:bg-light-background dark:hover:bg-dark-foreground"
                        />

                        <PopoverItem name="team-folder" side="left">
                            <TeamFolderPreview />

                            <OptionGroup v-if="$isThisRoute($route, ['TeamFolders'])" :title="$t('options')">
                                <Option
                                    @click.native="$updateTeamFolder(teamFolder)"
                                    :title="$t('edit_members')"
                                    icon="rename"
                                />
                                <Option
                                    @click.native="$dissolveTeamFolder(teamFolder)"
                                    :title="$t('dissolve_team')"
                                    icon="trash"
                                />
                            </OptionGroup>

                            <OptionGroup v-if="$isThisRoute($route, ['SharedWithMe'])" :title="$t('options')">
                                <Option
                                    @click.native="$detachMeFromTeamFolder(teamFolder)"
                                    :title="$t('leave_team_folder')"
                                    icon="user-minus"
                                />
                            </OptionGroup>
                        </PopoverItem>
                    </PopoverWrapper>

                    <!--Action buttons-->
                    <div v-if="!$isMobile()" class="flex items-center">
                        <ToolbarButton
                            v-if="canShowConvertToTeamFolder"
                            @click.native="$convertAsTeamFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canCreateTeamFolder,
                            }"
                            source="user-plus"
                            :action="$t('convert_into_team_folder')"
                        />
                        <ToolbarButton
                            v-if="!$isThisRoute($route, ['SharedWithMe', 'Public']) && !$isThisRoute($route, ['Trash'])"
                            @click.native="$shareFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': canShareInView,
                            }"
                            source="share"
                            :action="$t('share_item')"
                        />
                        <ToolbarButton
                            v-if="$isThisRoute($route, ['SharedWithMe', 'Public'])"
                            @click.native="$moveFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canEdit || isTeamFolderHomepage || isSharedWithMeHomepage,
                            }"
                            source="move"
                            :action="$t('move')"
                        />
                        <ToolbarButton
                            v-if="$isThisRoute($route, ['SharedWithMe', 'Public'])"
                            @click.native="$deleteFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canEdit || isTeamFolderHomepage || isSharedWithMeHomepage,
                            }"
                            source="trash"
                            :action="$t('delete')"
                        />

                        <ToolbarButton
                            v-if="$isThisRoute($route, ['Files', 'Trash', 'RecentUploads'])"
                            @click.native="$moveFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !showMoveDeleteButton,
                            }"
                            source="move"
                            :action="$t('move')"
                        />
                        <ToolbarButton
                            v-if="$isThisRoute($route, ['Files', 'RecentUploads', 'MySharedItems', 'TeamFolders', 'SharedWithMe']) && 
                                    !$isThisRoute($route, ['Trash'])"
                            @click.native="$downloadSelection(item)"
                            :class="{
                                'is-inactive': !isButtonDownload
                            }"
                            source="download"
                            :action="$t('download')"
                        />
                        <ToolbarButton
                            v-if="$isThisRoute($route, ['Files', 'RecentUploads', 'MySharedItems', 'TeamFolders', 'SharedWithMe', 'Trash'])"
                            @click.native="$deleteFileOrFolder(clipboard[0])"
                            :class="{
                                'is-inactive': !canDeleteInView
                            }"
                            source="trash"
                            :action="$t('delete')"
                        />
                        <ToolbarButton
                            v-if="$isThisRoute($route, ['Trash'])"
                            @click.native="$emptyTrash(clipboard[0])" 
                            :class="{
                                'is-inactive': !canEmptyTrash,
                            }"
                            source="empty-trash" 
                            :title="$t('empty_trash')" 
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
    </div>
</template>

<script>
import TeamMembersButton from '../../Teams/Components/TeamMembersButton'
import TeamFolderPreview from '../../Teams/Components/TeamFolderPreview'
import LanguageSwitcher from '../../Others/LanguageSwitcherDashboard'
import PopoverWrapper from '../../UI/Popover/PopoverWrapper'
import FileSortingOptions from '../../Menus/FileSortingOptions'
import PopoverItem from '../../UI/Popover/PopoverItem'
import NavigationBar from './NavigationBar'
import ToolbarButton from '../../UI/Buttons/ToolbarButton'
import OptionUpload from '../../Menus/Components/OptionUpload'
import OptionGroup from '../../Menus/Components/OptionGroup'
import SearchBarButton from '../../UI/Buttons/SearchBarButton'
import { events } from '../../../bus'
import { mapGetters } from 'vuex'
import Option from '../../Menus/Components/Option'
import Trash from '../../../views/FileView/Trash.vue'

export default {
    name: 'DesktopToolbar',
    components: {
        FileSortingOptions,
        TeamMembersButton,
        TeamFolderPreview,
        LanguageSwitcher,
		SearchBarButton,
        PopoverWrapper,
        NavigationBar,
        ToolbarButton,
        OptionUpload,
        OptionGroup,
        PopoverItem,
        Option,
    },
    computed: {
        ...mapGetters([
            'isVisibleNavigationBars',
            'currentTeamFolder',
            'currentFolder',
            'sharedDetail',
            'clipboard',
            'user',
        ]),
        canEdit() {
            if (this.currentTeamFolder && this.user && this.clipboard.length === 1) {
                let member = this.currentTeamFolder.data.relationships.members.data.find(
                    (member) => member.data.id === this.user.data.id
                )

                return member.data.attributes.permission === 'can-edit'
            }

            return false
        },
        showMoveDeleteButton() {
                let routes = ['Files', 'Trash', 'RecentUploads']
                return !this.$isThisRoute(this.$route, routes) || this.clipboard.length > 1 || this.clipboard.length === 1
        },
        isButtonDownload() {
            let routes = ['Files', 'RecentUploads', 'MySharedItems', 'Public', 'TeamFolders', 'SharedWithMe']
            return !this.$isThisRoute(this.$route, routes) || this.clipboard.length > 1 || this.clipboard.length === 1
        },
        isFolder() {
            return this.item && this.item.data.type === 'folder'
        },
        teamFolder() {
            return this.currentTeamFolder ? this.currentTeamFolder : this.clipboard[0]
        },
        isTeamFolderHomepage() {
            return this.$isThisRoute(this.$route, ['TeamFolders']) && !this.$route.params.id
        },
        isSharedWithMe() {
            return this.$isThisRoute(this.$route, ['SharedWithMe'])
        },
        canEmptyTrash() {
            return this.$isThisRoute(this.$route, ['Trash']) 
        },
        isSharedWithMeHomepage() {
            return this.$isThisRoute(this.$route, ['SharedWithMe']) && !this.$route.params.id
        },
        canCreateFolder() {
            return !this.$isThisRoute(this.$route, ['Files', 'Public', 'TeamFolders', 'SharedWithMe'])
        },
        canShowConvertToTeamFolder() {
            return this.$isThisRoute(this.$route, ['Files', 'MySharedItems'])
        },
        canUploadInView() {
            return !this.$isThisRoute(this.$route, ['Files', 'RecentUploads', 'TeamFolders', 'SharedWithMe'])
        },
        canUploadFolderInView() {
            return !this.$isThisRoute(this.$route, ['Files', 'Public', 'TeamFolders', 'SharedWithMe'])
        },
        canDeleteInView() {
            let routes = ['TeamFolders', 'SharedWithMe', 'RecentUploads', 'MySharedItems', 'Trash', 'Files']
            return !this.$isThisRoute(this.$route, routes) || this.clipboard.length > 1 || this.clipboard.length === 1
        },
        canMoveInView() {
            let routes = ['SharedWithMe', 'RecentUploads', 'MySharedItems', 'Public', 'Files', 'TeamFolders']
            return !this.$isThisRoute(this.$route, routes) || this.clipboard.length === 0
        },
        canShareInView() {
            let routes = ['TeamFolders', 'RecentUploads', 'MySharedItems', 'Public', 'Files']
            return !this.$isThisRoute(this.$route, routes) || this.clipboard.length > 1 || this.clipboard.length === 0
        },
        canCreateTeamFolder() {
            return (
                this.$isThisRoute(this.$route, ['MySharedItems', 'Files']) &&
                this.clipboard.length === 1 &&
                this.clipboard[0].data.type === 'folder'
            )
        },
    },
    data () {
        return {
            item: undefined,
        }
    },
    methods: {
        showTeamFolderMenu() {
            if (this.teamFolder) events.$emit('popover:open', 'team-folder')
        },
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
    mounted () {
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
    created() {
        events.$on('context-menu:show', (event, folder) => (this.item = folder))
        events.$on('mobile-context-menu:show', (folder) => (this.item = folder))

        events.$on('action:confirmed', (data) => {
            if (data.operation === 'remove-permanent-folders-files')

                this.$store.dispatch('emptyTrash')
        })
    },
    destroyed() {
        events.$off('action:confirmed')
    },
}
</script>
