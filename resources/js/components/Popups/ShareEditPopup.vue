<template>
    <PopupWrapper name="share-edit">
        <!--Title-->
        <PopupHeader :title="popupTitle" icon="share" />

        <!--Qr Code-->
        <div v-if="pickedItem && activeSection === 'qr-code'">
            <PopupContent class="flex items-center justify-center">
                <div v-if="!qrCode" class="relative my-8">
                    <Spinner />
                </div>
                <div v-if="qrCode" v-html="qrCode" class="my-5 overflow-hidden rounded-xl"></div>
            </PopupContent>

            <PopupActions>
                <ButtonBase class="w-full" @click.native="showSection(undefined)" button-style="secondary">
                    {{ $t('show_details') }}
                </ButtonBase>
                <ButtonBase class="w-full" @click.native="$closePopup()" button-style="theme">
                    {{ $t('awesome_iam_done') }}
                </ButtonBase>
            </PopupActions>
        </div>

        <!--Share via email-->
        <div v-if="pickedItem && activeSection === 'email-sharing'">
            <PopupContent>
                <!--Item Thumbnail-->
                <ThumbnailItem class="mb-4" :item="pickedItem" />

                <ValidationObserver @submit.prevent ref="shareEmail" tag="form">
                    <ValidationProvider tag="div" mode="passive" name="Email" rules="required" v-slot="{ errors }">
                        <AppInputText title="Share with" :error="errors[0]" :is-last="true">
                            <MultiEmailInput
                                rules="required"
                                v-model="emails"
                                :label="$t('shared_form.label_send_to_recipients')"
                                :is-error="errors[0]"
                            />
                        </AppInputText>
                    </ValidationProvider>
                </ValidationObserver>
            </PopupContent>

            <PopupActions>
                <ButtonBase class="w-full" @click.native="showSection(undefined)" button-style="secondary">
                    {{ $t('show_details') }}
                </ButtonBase>
                <ButtonBase
                    class="w-full"
                    @click.native="sendViaEmail"
                    button-style="theme"
                    :loading="isLoading"
                    :disabled="isLoading"
                >
                    {{ $t('send') }}
                </ButtonBase>
            </PopupActions>
        </div>

        <!--Update sharing-->
        <div v-if="pickedItem && !activeSection">
            <PopupContent class="!overflow-initial">
                <!--Item Thumbnail-->
                <ThumbnailItem class="mb-5" :item="pickedItem" />

                <!--Get share link-->
                <AppInputText :title="$t('get_your_link')">
                    <CopyShareLink :item="pickedItem" />
                </AppInputText>

                <ValidationObserver @submit.prevent ref="shareForm" tag="form">
                    <!--Permission Select-->
                    <ValidationProvider
                        v-if="isFolder"
                        tag="div"
                        mode="passive"
                        name="Permission"
                        rules="required"
                        v-slot="{ errors }"
                    >
                        <AppInputText :title="$t('permission')" :error="errors[0]">
                            <SelectInput
                                v-model="shareOptions.permission"
                                :options="$translateSelectOptions(permissionOptions)"
                                :default="shareOptions.permission"
                                :placeholder="$t('shared_form.placeholder_permission')"
                                :isError="errors[0]"
                            />
                        </AppInputText>
                    </ValidationProvider>

                    <!--Password Switch-->
                    <div>
                        <AppInputSwitch
                            :title="$t('password_protected')"
                            :description="$t('popup.share.password_description')"
                        >
                            <SwitchInput
                                v-model="shareOptions.isProtected"
                                class="switch"
                                :state="shareOptions.isProtected"
                            />
                        </AppInputSwitch>

                        <ActionButton
                            v-if="
                                pickedItem.data.relationships.shared.data.attributes.protected &&
                                canChangePassword &&
                                shareOptions.isProtected
                            "
                            @click.native="changePassword"
                            class="mb-6 -mt-4"
                        >
                            {{ $t('popup_share_edit.change_pass') }}
                        </ActionButton>

                        <!--Set password-->
                        <ValidationProvider
                            v-if="shareOptions.isProtected && !canChangePassword"
                            tag="div"
                            mode="passive"
                            name="Password"
                            rules="required"
                            v-slot="{ errors }"
                        >
                            <AppInputText :error="errors[0]" class="-mt-2">
                                <input
                                    v-model="shareOptions.password"
                                    :class="{ '!border-rose-600': errors[0] }"
                                    type="text"
                                    class="focus-border-theme input-dark"
                                    :placeholder="$t('page_sign_in.placeholder_password')"
                                    required
                                />
                            </AppInputText>
                        </ValidationProvider>
                    </div>

                    <!--Show Password-->
                    <div v-if="shareOptions.isProtected">
                        <AppInputSwitch 
                            :title="$t('page_shared.password_sharing_tip')" 
                            :description="$t('page_shared.password_sharing_tip_desc')"
                        >
                            <SwitchInput 
                                v-model="shareOptions.AllowPasswordShow" 
                                class="switch" 
                                :state="shareOptions.AllowPasswordShow"
                            />
                        </AppInputSwitch>

                        <ValidationProvider
                            v-if="shareOptions.AllowPasswordShow"
                            tag="div"
                            mode="passive"
                            :name="$t('page_shared.password_sharing_tip')"
                            rules="required"
                            :is-last="true"
                            v-slot="{ errors }"
                        >
                            <AppInputText :error="errors[0]" class="mb-6 -mt-4">
                                <input
                                    v-model="shareOptions.passwordShow"
                                    @input="getShareOptionsData"
                                    :is-last="true"
                                    :class="{ '!border-rose-600': errors[0] }"
                                    type="text"
                                    :maxlength="40"
                                    class="focus-border-theme input-dark"
                                    :placeholder="showPasswordPlaceholder"
                                    required
                                />
                            </AppInputText>
                        </ValidationProvider>
                    </div>

                    <div v-if="!shareOptions.isProtected">
                        <div :state="shareOptions.AllowPasswordShow = false"></div>
                    </div>

                    <!--Expiration switch-->
                    <div>
                        <AppInputSwitch
                            :title="$t('expiration')"
                            :description="$t('popup.share.expiration_description')"
                            :is-last="!shareOptions.expiration"
                        >
                            <SwitchInput
                                v-model="shareOptions.expiration"
                                class="switch"
                                :state="shareOptions.expiration ? 1 : 0"
                            />
                        </AppInputSwitch>

                        <!--Set expiration-->
                        <AppInputText v-if="shareOptions.expiration" class="-mt-2" :is-last="true">
                            <SelectBoxInput
                                v-model="shareOptions.expiration"
                                :data="$translateSelectOptions(expirationList)"
                                :value="shareOptions.expiration"
                                class="box"
                            />
                        </AppInputText>
                    </div>
                </ValidationObserver>
            </PopupContent>

            <PopupActions>
                <ButtonBase
                    class="w-full"
                    @click.native="destroySharing"
                    :button-style="destroyButtonStyle"
                    :loading="isDeleting"
                    :disabled="isDeleting"
                >
                    {{ destroyButtonText }}
                </ButtonBase>
                <ButtonBase
                    class="w-full"
                    @click.native="updateShareOptions"
                    button-style="theme"
                    :loading="isLoading"
                    :disabled="isLoading"
                >
                    {{ $t('store_changes') }}
                </ButtonBase>
            </PopupActions>
        </div>
    </PopupWrapper>
</template>

<script>
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import MultiEmailInput from '../Inputs/MultiEmailInput'
import SelectBoxInput from '../Inputs/SelectBoxInput'
import CopyShareLink from '../Inputs/CopyShareLink'
import PopupWrapper from './Components/PopupWrapper'
import PopupActions from './Components/PopupActions'
import PopupContent from './Components/PopupContent'
import PopupHeader from './Components/PopupHeader'
import SwitchInput from '../Inputs/SwitchInput'
import SelectInput from '../Inputs/SelectInput'
import ThumbnailItem from '../UI/Entries/ThumbnailItem'
import ActionButton from '../UI/Buttons/ActionButton'
import ButtonBase from '../UI/Buttons/ButtonBase'
import AppInputSwitch from '../Forms/Layouts/AppInputSwitch'
import AppInputText from '../Forms/Layouts/AppInputText'
import { required } from 'vee-validate/dist/rules'
import Spinner from '../UI/Others/Spinner'
import { events } from '../../bus'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'ShareEditPopup',
    components: {
        ValidationProvider,
        ValidationObserver,
        MultiEmailInput,
        AppInputSwitch,
        SelectBoxInput,
        ThumbnailItem,
        CopyShareLink,
        ActionButton,
        PopupWrapper,
        PopupActions,
        AppInputText,
        PopupContent,
        PopupHeader,
        SelectInput,
        SwitchInput,
        ButtonBase,
        required,
        Spinner,
    },
    props: {
        itemId: {
            type: Number,
            required: false, // Mude para false ou remova completamente
            default: null
        },
        showpassword_in: {
            type: [Boolean, String],
            default: false
        },
    },
    computed: {
        ...mapGetters(['permissionOptions', 'expirationList', 'user']),
        popupTitle() {
            return (
                {
                    'qr-code': this.$t('get_qr_code'),
                    'email-sharing': this.$t('share_with_multiple_emails'),
                }[this.activeSection] || this.$t('popup_share_edit.title')
            )
        },
        isFolder() {
            return this.pickedItem && this.pickedItem.data.type === 'folder'
        },
        destroyButtonText() {
            return this.isConfirmedDestroy ? this.$t('popup_share_edit.confirm') : this.$t('popup_share_edit.stop')
        },
        destroyButtonStyle() {
            return this.isConfirmedDestroy ? 'danger-solid' : 'danger'
        },
        showPasswordPlaceholder() {
            return this.showpassword_in 
                ? this.$t('page_shared.password_tip_is') + this.showpassword_in
                : this.$t('page_shared.password_tip_words');
        },
    },
    watch: {
        'shareOptions.expiration': function (val) {
            if (!val) {
                this.shareOptions.expiration = undefined
            }
        },
        showpassword_in: {
            handler(newVal) {
                if (newVal && this.shareOptions) {
                    this.shareOptions.passwordShow = newVal;
                }
            },
            immediate: true
        },
    },
    data() {
        return {
            password: undefined,
            passwordShow: undefined,
            activeSection: undefined,
            shareOptions: undefined,
            pickedItem: undefined,
            emails: undefined,
            qrCode: undefined,
            isConfirmedDestroy: false,
            canChangePassword: false,
            isMoreOptions: false,
            isDeleting: false,
            isLoading: false,
            passwordTip: {},
        }
    },
    methods: {   
        async fetchShowpasswordIn() {
            // Use o ID do item selecionado em vez da prop
            if (!this.pickedItem) return;
            
            try {
                const response = await axios.post('/api/getShowPW', { 
                    itemId: this.pickedItem.data.id 
                });
                this.$emit('update:showpassword_in', response.data.showpassword_in);
            } catch (error) {
                console.error('Error fetching showpassword_in:', error);
            }
        },
        
        getShareOptionsData() {
            axios.post('/api/getShowPW')
                .then(response => {
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        // Emit to parent to update the prop
                        this.$emit('update:showpassword_in', response.data[0].showpassword_in);
                    }
                })
                .catch(error => {
                    console.error('Error getting share options data:', error);
                });
        },
        
        getQrCode() {
            axios
                .get(`/api/share/${this.shareOptions.token}/qr`)
                .then((response) => {
                    this.qrCode = response.data.data.svg;
                })
                .catch(() => this.$isSomethingWrong());
        },
        
        showSection(section = undefined) {
            this.activeSection = section;
        },
        
        changePassword() {
            this.canChangePassword = false;
        },
        
        async sendViaEmail() {
            // Validate email field
            const isValid = await this.$refs.shareEmail.validate();

            if (!isValid) return;

            this.isLoading = true;

            axios
                .post(`/api/share/${this.shareOptions.token}/email`, {
                    emails: this.emails,
                })
                .then(() => {
                    this.$closePopup();
                })
                .catch(() => {
                    this.$isSomethingWrong();
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        
        async destroySharing() {
            // Set confirm button
            if (!this.isConfirmedDestroy) {
                this.isConfirmedDestroy = true;
                return;
            }

            // Start deleting spinner button
            this.isDeleting = true;

            // Send delete request
            await this.$store
                .dispatch('shareCancel', this.pickedItem)
                .catch(() => {
                    // End deleting spinner button
                    this.isDeleting = false;
                })
                .finally(() => this.$closePopup());
        },
        
        async updateShareOptions() {
            // Validate fields
            const isValid = await this.$refs.shareForm.validate();

            if (!isValid) return;

            this.isLoading = true;

            // Send request to update share options
            axios
                .post('/api/share/' + this.shareOptions.token, {
                    permission: this.shareOptions.permission,
                    protected: this.shareOptions.isProtected,
                    protectedPasswordShow: this.shareOptions.AllowPasswordShow,
                    expiration: this.shareOptions.expiration,
                    passwordShow: this.shareOptions.passwordShow,
                    password: this.shareOptions.password ? this.shareOptions.password : undefined,
                    _method: 'patch',
                })
                .then((response) => {
                    // Update shared data
                    this.$store.commit('UPDATE_SHARED_ITEM', response.data);
                    events.$emit('popup:close');
                    this.$getDataByLocation(1);
                })
                .catch(() => {
                    this.$isSomethingWrong();
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        
        resetPopupData() {
            this.isDeleting = false;
            this.isConfirmedDestroy = false;
            this.canChangePassword = false;
            this.shareOptions = undefined;
            this.pickedItem = undefined;
            this.activeSection = undefined;
            this.qrCode = undefined;
            this.emails = undefined;
            this.isLoading = false;
        }
    },
    
    mounted() {
        events.$on('emailsInputValues', (emails) => (this.emails = emails));

        // Show popup
        events.$on('popup:open', (args) => {
            if (args.name !== 'share-edit') return;

            // Store picked item
            this.pickedItem = args.item;

            // Store shared options
            this.shareOptions = {
                id: args.item.data.relationships.shared.data.id,
                token: args.item.data.relationships.shared.data.attributes.token,
                AllowPasswordShow: args.item.data.relationships.shared.data.attributes.protectedPasswordShow,
                passwordShow: args.item.data.relationships.shared.data.attributes.showpassword_in,
                expiration: args.item.data.relationships.shared.data.attributes.expire_in,
                isProtected: args.item.data.relationships.shared.data.attributes.protected,
                permission: args.item.data.relationships.shared.data.attributes.permission,
                password: undefined,
            };

            if (args.section) this.activeSection = args.section;

            if (args.section === 'qr-code') this.getQrCode();

            this.canChangePassword = args.item.data.relationships.shared.data.attributes.protected;
            
            // Fetch showpassword_in value after popup opens
            this.fetchShowpasswordIn();
            this.getShareOptionsData();
        });

        events.$on('popup:close', () => {
            // Reset data
            setTimeout(() => {
                this.resetPopupData();
            }, 150);
        });
    },
    
    beforeDestroy() {
        // Clean up event listeners
        events.$off('emailsInputValues');
        events.$off('popup:open');
        events.$off('popup:close');
    }
}
</script>