<template>
    <div class="lg:flex h-screen lg:overflow-hidden w-full">
        <!--On Top of App Components-->
        <FilePreview />
        <Spotlight />

        <!--Popups-->
        <ProcessingPopup v-if="!isLoading && user" />
        <ConfirmPopup v-if="!isLoading && user" />

        <CreateTeamFolderPopup v-if="!isLoading && user" />
        <EditTeamFolderPopup v-if="!isLoading && user" />

        <ShareCreatePopup v-if="!isLoading && user" />
        <ShareEditPopup v-if="!isLoading && user" />

        <CreateUploadRequestPopup v-if="!isLoading && user" />
        <NotificationsPopup v-if="!isLoading && user" />
        <RemoteUploadPopup v-if="!isLoading && user" />
        <CreateFolderPopup v-if="!isLoading && user" />
        <RenameItemPopup v-if="!isLoading && user" />
        <MoveItemPopup v-if="!isLoading && user" />

        <!--Mobile components-->
        <FileSortingMobile />
        <FileFilterMobile />

        <!--Navigations-->
        <MobileNavigation />

        <!--Others-->
        <DragUI />

        <!--2 col Sidebars-->
        <PanelNavigationFiles @click="deselect" />

        <div
            @contextmenu.prevent.capture="contextMenu($event, undefined)"
            class="lg:flex lg:flex-col lg:w-full lg:px-3.5 min-w-0"
            @click.self="deselect"
        >
            <DesktopToolbar />

            <MobileToolbar />

            <!--Google Adsense banner-->
            <div v-if="config.allowedAdsense && config.adsenseBanner01" v-html="config.adsenseBanner01" class="mb-5 min-h-[120px]"></div>

            <!--File list & info sidebar-->
            <div class="flex space-x-3 lg:overflow-hidden grow" @drop.stop.prevent="uploadDroppedItems($event)" @dragenter.prevent @dragover.prevent>
                <router-view id="file-view" class="relative w-full min-w-0" :key="$route.fullPath" />

                <InfoSidebar v-if="isVisibleSidebar" />
            </div>
        </div>
    </div>
</template>

<script>
import { getFilesFromDataTransferItems } from 'datatransfer-files-promise'
import FileSortingMobile from '../components/Menus/FileSortingMobile'
import SidebarNavigation from '../components/Sidebar/SidebarNavigation'
import FileFilterMobile from '../components/Menus/FileFilterMobile'
import CreateFolderPopup from '../components/Popups/CreateFolderPopup'
import ProcessingPopup from '../components/Popups/ProcessingPopup'
import MobileNavigation from '../components/Mobile/MobileNavigation'
import ShareCreatePopup from '../components/Popups/ShareCreatePopup'
import DesktopToolbar from '../components/Layout/Toolbars/DesktopToolbar'
import CreateTeamFolderPopup from '../components/Teams/CreateTeamFolderPopup'
import ConfirmPopup from '../components/Popups/ConfirmPopup'
import RenameItemPopup from '../components/Popups/RenameItemPopup'
import PanelNavigationFiles from '../components/EntriesView/PanelNavigationFiles'
import MobileToolbar from '../components/Layout/Toolbars/MobileToolbar'
import ShareEditPopup from '../components/Popups/ShareEditPopup'
import FilePreview from '../components/FilePreview/FilePreview'
import MoveItemPopup from '../components/Popups/MoveItemPopup'
import EditTeamFolderPopup from '../components/Teams/EditTeamFolderPopup'
import Spotlight from '../components/Spotlight/Spotlight'
import DragUI from '../components/UI/Others/DragUI'
import InfoSidebar from '../components/Layout/Sidebars/InfoSidebar'
import { events } from '../bus'
import { mapGetters } from 'vuex'
import CreateUploadRequestPopup from "../components/UploadRequest/CreateUploadRequestPopup";
import NotificationsPopup from "../components/Notifications/NotificationsPopup";
import RemoteUploadPopup from "../components/RemoteUpload/RemoteUploadPopup";

export default {
    name: 'Platform',
    components: {
        RemoteUploadPopup,
        NotificationsPopup,
        CreateUploadRequestPopup,
        CreateTeamFolderPopup,
        PanelNavigationFiles,
        EditTeamFolderPopup,
        CreateFolderPopup,
        FileSortingMobile,
        SidebarNavigation,
        FileFilterMobile,
        MobileNavigation,
        ShareCreatePopup,
        ProcessingPopup,
        RenameItemPopup,
        ShareEditPopup,
        DesktopToolbar,
        MoveItemPopup,
        MobileToolbar,
        ConfirmPopup,
        InfoSidebar,
        FilePreview,
        Spotlight,
        DragUI,
    },
    computed: {
        ...mapGetters(['isVisibleSidebar', 'config', 'currentFolder', 'user']),
    },
    data() {
        return {
            isScaledDown: false,
            isLoading: true,
        }
    },
    methods: {
        async uploadDroppedItems(event) {
            // Check if user dropped folder with files
            let files = await getFilesFromDataTransferItems(event.dataTransfer.items)

            if (files.length !== 0) {
                // Upload folder with files
                this.$uploadDraggedFolderOrFile(files, this.currentFolder?.data.id)
            }
        },
        contextMenu(event, item) {
            events.$emit('context-menu:show', event, item)
        },
        deselect() {
            // Hide context menu
            events.$emit('context-menu:hide')

            // Clear clipboard
            this.$store.commit('CLIPBOARD_CLEAR')
        },
    },
    async mounted() {
        // TODO: new scaledown effect
        events.$on('mobile-menu:show', () => (this.isScaledDown = true))
        events.$on('mobile-menu:hide', () => (this.isScaledDown = false))

        // Aguarda a inicialização completa antes de renderizar os popups
        await this.$nextTick()
        
        // Pequeno delay para garantir que tudo está carregado
        setTimeout(() => {
            this.isLoading = false
        }, 100)
    },
    watch: {
        // Observa mudanças no user para garantir que os popups sejam renderizados
        user: {
            handler(newUser) {
                if (newUser && this.isLoading) {
                    this.$nextTick(() => {
                        this.isLoading = false
                    })
                }
            },
            immediate: true
        }
    }
}
</script>