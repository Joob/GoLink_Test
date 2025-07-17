<template>
    <PageTab :is-loading="isLoading" v-if="storage">
        <!--Storage Usage-->
        <div v-if="distribution" class="card shadow-card">
            <FormLabel icon="hard-drive">
                {{ $t('storage_usage') }}
            </FormLabel>

            <b class="-mt-3 mb-0.5 block text-2xl font-extrabold sm:text-3xl">
                {{ storage.data.attributes.used }}
            </b>

            <b
                v-if="
                    config.subscriptionType === 'fixed' || (config.subscriptionType === 'none' && config.storageLimit)
                "
                class="mt-0.5 block text-sm dark:text-gray-500 text-gray-400"
            >
                {{ $t('total_of', {capacity: storage.data.attributes.capacity}) }}
                {{ $t('used') }}
            </b>

            <ProgressLine v-if="storage.data.attributes.used !== '0B'" :data="distribution" class="mt-5" />
        </div>

        <!--Upload-->
        <div v-if="distribution" class="card shadow-card">
            <FormLabel icon="hard-drive">
                {{ $t('upload') }}
            </FormLabel>

            <b class="-mt-3 mb-0.5 block text-2xl font-extrabold sm:text-3xl">
                {{ storage.data.meta.traffic.upload }}
            </b>

            <b class="mb-3 mb-5 block text-sm dark:text-gray-500 text-gray-400">
                {{ $t('in_last_x_days') }}
            </b>

            <BarChart :data="storage.data.meta.traffic.chart.upload" color="#FFBD2D" />
        </div>

        <!--Download-->
        <div v-if="distribution" class="card shadow-card">
            <FormLabel icon="hard-drive">
                {{ $t('download') }}
            </FormLabel>

            <b class="-mt-3 mb-0.5 block text-2xl font-extrabold sm:text-3xl">
                {{ storage.data.meta.traffic.download }}
            </b>

            <b class="mb-3 mb-5 block text-sm dark:text-gray-500 text-gray-400">
                {{ $t('in_last_x_days') }}
            </b>

            <BarChart :data="storage.data.meta.traffic.chart.download" color="#9d66fe" />
        </div>

        <!--Set Storage Size-->
        <div
            class="card shadow-card"
        >
            <FormLabel>
                {{ $t('user_box_storage.title') }}
            </FormLabel>
            <ValidationObserver tag="form">
              <ValidationProvider tag="div" v-slot="{ validate, errors }" mode="passive" name="Capacity" rules="required|between:1,999999999">
                  <AppInputText
                      :title="$t('admin_page_user.label_change_capacity')"
                      :description="$t('user_box_storage.description')"
                      :error="errors[0]"
                      :is-last="true"
                  >
                    <div class="space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
                      <input
                          v-model="capacity"
                          :placeholder="$t('admin_page_user.label_change_capacity')"
                          type="number"
                          min="1"
                          max="999999999"
                          class="focus-border-theme input-dark"
                          @change="validate"
                          :class="{ '!border-rose-600': errors[0] }"
                      />
                      <ButtonBase
                          :loading="isSendingRequest"
                          :disabled="isSendingRequest"
                          type="button"
                          button-style="theme"
                          class="w-full sm:w-auto"
                          @click.native="changeStorageCapacity(validate)"
                      >
                        {{ $t('change_capacity') }}
                      </ButtonBase>
                    </div>
                  </AppInputText>
              </ValidationProvider>
            </ValidationObserver>

            <br>
            
          <ValidationObserver tag="form">
            <ValidationProvider tag="div" v-slot="{ validate, errors }" mode="passive" name="Max Team Members" rules="required|between:1,9999">
              <AppInputText
                  :description="$t('zero_for_unlimited_members')"
                  :title="$t('max_team_members')"
                  :error="errors[0]"
                  :is-last="true"
              >

              <div class="space-y-4 sm:flex sm:space-x-4 sm:space-y-0">
                  <input
                      v-model="teamsDefaultMembers"
                      :is-last="true"
                      :placeholder="$t('change max team members shared')"
                      type="number"
                      min="1"
                      max="9999"
                      class="focus-border-theme input-dark"
                      :class="{ '!border-rose-600': errors[0] }"
                  />
                  <ButtonBase
                      :loading="isSendingRequest"
                      :disabled="isSendingRequest"
                      type="button"
                      button-style="theme"
                      @click.native="changeMaxTeamMembers(validate, selectedUserId)"
                      class="w-full sm:w-auto"
                  >
                    {{ $t('Change Members') }}
                  </ButtonBase>
                </div>
              </AppInputText>
            </ValidationProvider>
          </ValidationObserver>

            <br>

            <!--Set Max Team Members-->

<!--            <ValidationObserver-->
<!--                ref="teamsDefaultMembers"-->
<!--                @submit.prevent="teamsDefaultMembers"-->
<!--                v-slot="{ invalid }"-->
<!--                tag="form"-->
<!--            >-->
<!--                <ValidationProvider tag="div" v-slot="{ errors }" mode="passive" name="Max Team Members" rules="required" >-->
<!--                    <AppInputText-->
<!--                        :description="$t('zero_for_unlimited_members')" -->
<!--                        :title="$t('max_team_members')"-->
<!--                        :error="errors[0]"-->
<!--                        :is-last="true"-->
<!--                    >-->
<!--                      {{ teamsDefaultMembers }}-->
<!--                        <div class="space-y-4 sm:flex sm:space-x-4 sm:space-y-0">-->
<!--                            <input-->
<!--                                v-model="teamsDefaultMembers"-->
<!--                                :placeholder="$t('change max team members shared')"-->
<!--                                type="number"-->
<!--                                min="1"-->
<!--                                max="9999"-->
<!--                                class="focus-border-theme input-dark"-->
<!--                                :class="{ '!border-rose-600': errors[0] }"-->
<!--                            />-->
<!--                            <ButtonBase-->
<!--                                :loading="isSendingRequest"-->
<!--                                :disabled="isSendingRequest"-->
<!--                                type="submit"-->
<!--                                button-style="theme"-->
<!--                                @click.native="$updateText('/user/set', 'max_team_members', teamsDefaultMembers, true)"-->
<!--                                class="w-full sm:w-auto"-->
<!--                            >-->
<!--                                {{ $t('Change Members') }}-->
<!--                            </ButtonBase>-->
<!--                        </div>-->
<!--                    </AppInputText>-->
<!--                </ValidationProvider>-->
<!--           </ValidationObserver>-->

            <!--<br>

            <AppInputText :description="$t('zero_for_unlimited_members')" :is-last="true" :title="$t('max_team_members')" v-if="app != undefined">
				<input
					v-model="app.teamsDefaultMembers"
					:placeholder="$t('admin_settings.others.default_storage_plac')"
					class="focus-border-theme input-dark"
					max="999999999"
					min="1"
					type="number"
					@input="$updateText('/user/set', 'max_team_members', app.teamsDefaultMembers)"
				/>
			</AppInputText>-->
        </div>
    </PageTab>
</template>

<script>
import ProgressLine from '../../../../components/UI/ProgressChart/ProgressLine'
import AppInputText from '../../../../components/Forms/Layouts/AppInputText'
import FormLabel from '../../../../components/UI/Labels/FormLabel'
import InfoBox from '../../../../components/UI/Others/InfoBox'
import PageTabGroup from '../../../../components/Layout/PageTabGroup'
import PageTab from '../../../../components/Layout/PageTab'
import { ValidationProvider, ValidationObserver } from 'vee-validate/dist/vee-validate.full'
import ButtonBase from '../../../../components/UI/Buttons/ButtonBase'
import { required, between } from 'vee-validate/dist/rules'
import BarChart from '../../../../components/UI/BarChart/BarChart'
import { events } from '../../../../bus'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'UserStorage',
    props: ['user'],
    components: {
        ProgressLine,
        AppInputText,
        PageTabGroup,
        FormLabel,
        PageTab,
        InfoBox,
        ValidationProvider,
        ValidationObserver,
        ButtonBase,
        required,
        BarChart,
    },
    computed: {
        ...mapGetters(['config']),
    },
    data() {
        return {
            isLoading: true,
            isSendingRequest: false,
            isSendingRequestMaxTeam: false,
            capacity: undefined,
            storage: undefined,
            distribution: undefined,
            teamsDefaultMembers: '',
        }
    },

    methods: {
      async changeStorageCapacity(validateEvent) {
          // Validate fields
          const isValid = await validateEvent()

          if (!isValid.valid) return

          this.isSendingRequest = true

          // Send request to get user reset link
          axios
              .post(this.$store.getters.api + '/admin/users/' + this.$route.params.id + '/capacity', {
                  attributes: {
                      max_storage_amount: this.capacity,
                  },
                  _method: 'patch',
              })
              .then(() => {
                this.isSendingRequest = false

                this.getStorageDetails()

                events.$emit('toaster', {
                    type: 'success',
                    message: this.$t('toaster.changed_capacity'),
                })
              })
              .catch((error) => {
                  this.isSendingRequest = false

                  if (error.response.status == 422) {
                      // Password validation error
                      if (error.response.data.errors['attributes.max_storage_amount']) {
                          this.$refs.changeStorageCapacity.setErrors({
                              Capacity: this.$t('errors.capacity_digit'),
                          })
                      }
                  } else {
                      events.$emit('alert:open', {
                          title: this.$t('popup_error.title'),
                          message: this.$t('popup_error.message'),
                      })
                  }
              })
      },
      async changeMaxTeamMembers(validateEvent) {
            const isValid = await validateEvent()

            if (!isValid.valid) return

            try {
                await axios.patch(`/api/user/set/${this.$route.params.id}`, {
                    name: 'max_team_members',
                    value: this.teamsDefaultMembers
                });

                events.$emit('toaster', {
                    type: 'success',
                    message: 'Max team members changed successfully',
                });

            } catch (error) {
                events.$emit('alert:open', {
                    title: this.$t('popup_error.title'),
                    message: this.$t('popup_error.message'),
                });
            }
      },
      getStorageDetails() {
          axios.get('/api/admin/users/' + this.$route.params.id + '/storage').then((response) => {
              this.distribution = this.$mapStorageUsage(response.data)

              this.storage = response.data

              this.isLoading = false
          })
      },
    },
    mounted() {
        this.isSendingRequestMaxTeam = true

		axios
			.post('/api/user/info', {id:this.$route.params.id})
			.then((response) => {
                this.teamsDefaultMembers = response.data.max_team_members
                this.isSendingRequestMaxTeam = false
			})
	},
    created() {
        this.getStorageDetails()
    },
}
</script>
