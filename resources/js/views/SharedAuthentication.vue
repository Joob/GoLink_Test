<template>

<div class="landing-page">

    <!--Navigation-->
    <Navigation class="page-wrapper medium"/>

    <br><br>

    <AuthContentWrapper>
        <AuthContent name="password" :visible="true">
            <Headline :title="$t('page_shared.title')" :description="$t('page_shared.subtitle')" />
            <ValidationObserver
                @submit.prevent="authenticateProtected"
                ref="authenticateProtected"
                tag="form"
                class="mb-12 items-start space-y-4 md:flex md:space-x-4 md:space-y-0"
            >
                <ValidationProvider
                    tag="div"
                    mode="passive"
                    class="w-full text-left"
                    name="Password"
                    rules="required"
                    v-slot="{ errors }"
                >
                    <input
                        v-model="password"
                        :placeholder="$t('page_shared.placeholder_pass')"
                        type="password"
                        class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                        :class="{ '!border-rose-600': errors[0] }"
                    />
                    <span class="text-left text-xs text-red-600" v-if="errors[0]">{{ errors[0] }}</span>
                </ValidationProvider>
                <AuthButton
                    class="w-full justify-center md:w-min"
                    icon="chevron-right"
                    :text="$t('submit')"
                    :loading="isLoading"
                    :disabled="isLoading"
                />
            </ValidationObserver>

            <br>
                
            <div v-if="items.allow_showpassword_in" class="fetch-profile">
                <ButtonBase
                    class="w-full"
                    @click.native="fetchUsers"
                    button-style="theme"
                    :loading="isLoading"
                    :disabled="isLoading"
                >
                    {{ $t('page_shared.password_sharing_tip') }}
                </ButtonBase>
                <!--<br>
                <h4 v-if="isLoading">Loading...</h4>-->

                <br>

                <div 
                    class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground"
                    v-if="isAllow"
                    >
                    {{items.showpassword_in}}
                    <ShareEditPopup :itemId="typeof items.id === 'number' ? items.id : 0" :showpassword_in="!!items.showpassword_in"></ShareEditPopup>

                </div>
                
            </div>

        </AuthContent>
    </AuthContentWrapper>

    <br><br>

    <!--Footer-->
    <PageFooter/>

    </div>
</template>

<script>
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import AuthContentWrapper from '../components/Layout/AuthPages/AuthContentWrapper'
import AuthContent from '../components/Layout/AuthPages/AuthContent'
import PageFooter from '../components/IndexPage/IndexPageFooter'
import Navigation from '../components/IndexPage/IndexNavigation'
import AuthButton from '../components/UI/Buttons/AuthButton'
import Headline from '../components/UI/Labels/LogoHeadline'
import ButtonBase from '../components/UI/Buttons/ButtonBase'
import ShareEditPopup from '../components/Popups/ShareEditPopup'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
        name: 'SharedAuthentication',
        components: {
            ValidationObserver,
            ValidationProvider,
            AuthContentWrapper,
            ShareEditPopup,
            AuthContent,
            AuthButton,
            Navigation,
            PageFooter,
            ButtonBase,
            Headline,
        },
        computed: {
            ...mapGetters(['config', 'user']),
        },
        data() {
            return {
                password: '',
                isLoading: false,
                users: [],
                items: {},
                isAllow:false,
                owner:false,
                shareOptions: {
                    id: undefined,
                    isPassword: undefined,
                    expiration: undefined,
                    password: undefined,
                    isPasswordShow: false,
                    passwordShow: undefined,
                },
            }
        },
        watch: {
            'shareOptions.expiration': function (val) {
                if (this.shareOptions) {
                    if (!val) {
                        this.shareOptions.expiration = undefined;
                    }
                }
            },
        },
        created(){
            axios.post('/api/allowed',{token:this.$route.params.token})
                .then((response)=>{
                    this.items = response.data;
                    this.isLoading = false
                //do a console.log(res.data) to ensure you are getting the users collection
                })
                .catch((err) => {
                    console.log(err)
            });
        },
        methods: {
            fetchUsers() {
                let vm = this;
                this.isLoading = true;
                this.users = [];
                axios.post('/api/showPW',{token:vm.$route.params.token})
                    .then((response)=>{
                        this.isAllow = !this.isAllow;
                        this.users = [response.data];
                        this.isLoading = false;
                    })
                    .catch((err) => {
                        console.log(err)
                });
            },
    
            async authenticateProtected() {
                // Validate fields
                const isValid = await this.$refs.authenticateProtected.validate();

                if (!isValid) return;

                // Start loading
                this.isLoading = true;

                try {
                    const response = await axios.post('/api/sharing/authenticate/' + this.$route.params.token, {
                        password: this.password,
                    });

                    // Show file browser
                    if (response.data.data.attributes.type === 'folder') {
                        this.$router.replace({
                            name: 'Public',
                            params: {
                                token: this.$route.params.token,
                                id: response.data.data.attributes.item_id,
                            },
                        });
                    } else {
                        // Show single file
                        this.$router.push({ name: 'SharedSingleFile' });
                    }
                } catch (error) {
                    if (error.response && error.response.status === 401) {
                        this.$refs.authenticateProtected.setErrors({
                            Password: [error.response.data.message || 'Invalid password.'],
                        });
                    } else {
                        // Handle other errors
                        console.error(error);
                        this.$toast.error('An error occurred. Please try again.'); // Example of user feedback
                    }
                } finally {
                    this.isLoading = false;
                }
            }
    
        },
    
    }
    </script>
    
    <style>
        .leading-5 label{
            display:none;
        }
    </style>
