<template>
    <div class="page-wrapper medium get-started" style="padding: 5% 0;" v-if="index.section_get_started === '1'">

        <div style="padding-top: -20%;"></div>
         
        <div class="mx-auto mb-5 md:flex md:max-w-[840px] md:items-center allTextNumbers">
            <div class="font-bold md:text-left">
                <h2>{{ $t('started_title_left') }}</h2>
                <br>
                {{ $t('started_title_left_text_1') }}
                <br>
                <br>

                <div v-if="!config.isAuthenticated">
                    <!--User registration button-->
                    <router-link v-if="config.userRegistration" class="sign-up-button" :to="{ name: 'SignUp' }">
                        <button class="btn btn-primary">{{ $t('page_index.sign_up_button') }}</button>
                    </router-link>

                    <!--User login button-->
                    <router-link v-if="!config.userRegistration" class="sign-up-button" :to="{ name: 'SignIn' }">
                        <button class="btn btn-primary">{{ $t('log_in') }}</button>
                    </router-link>
                </div>
            </div>
            
            <div class="spaceFromTextNumbers" style="padding-left: 100px;"></div>

            <div class="dark:placeholder:text-gray-600 focus-border-theme w-full appearance-none rounded-lg border border-transparent bg-light-background px-5 py-3.5 font-bold dark:bg-2x-dark-foreground">
                <div class="counter-item ">
                    <h2>{{ $t('started_upload_info_right') }}</h2>
                    <div class="counter-number">{{ fileCount }}</div>
                </div>

                <div class="counter-item ">
                    <h2>{{ $t('started_register_info_right') }}</h2>
                    <div class="counter-number">{{ userCount }}</div>
                </div>
            </div>
        </div>

        <cloud-icon size="590" class="cloud-bg svg-color-theme" />

        <div class="icons">
            <hard-drive-icon size="42" class="icon"></hard-drive-icon>
            <settings-icon size="22" class="icon"></settings-icon>
            <image-icon size="50" class="icon"></image-icon>
            <link-icon size="24" class="icon"></link-icon>
            <trash2-icon size="40" class="icon"></trash2-icon>
            <search-icon size="18" class="icon"></search-icon>
            <eye-icon size="36" class="icon"></eye-icon>
            <star-icon size="34" class="icon"></star-icon>
            <folder-plus-icon size="20" class="icon"></folder-plus-icon>
            <grid-icon size="28" class="icon"></grid-icon>
            <share-icon size="32" class="icon"></share-icon>
            <folder-plus-icon size="48" class="icon"></folder-plus-icon>
            <search-icon size="34" class="icon"></search-icon>
            <star-icon size="22" class="icon"></star-icon>
            <upload-cloud-icon size="42" class="icon"></upload-cloud-icon>
            <grid-icon size="18" class="icon"></grid-icon>
            <settings-icon size="32" class="icon"></settings-icon>
            <link-icon size="36" class="icon"></link-icon>
            <hard-drive-icon size="22" class="icon"></hard-drive-icon>
            <info-icon size="36" class="icon"></info-icon>
        </div>

        <div style="padding-top: 10%"></div>

    </div>

</template>

<script>
import PageTitle from './Components/PageTitle'
import AuthButton from '../UI/Buttons/AuthButton'
import {
    ChevronRightIcon,
    UploadCloudIcon,
    FolderPlusIcon,
    HardDriveIcon,
    SettingsIcon,
    Trash2Icon,
    SearchIcon,
    ShareIcon,
    CloudIcon,
    ImageIcon,
    InfoIcon,
    GridIcon,
    LinkIcon,
    StarIcon,
    EyeIcon,
} from 'vue-feather-icons'
import { mapGetters } from 'vuex'
import axios from 'axios';

export default {
    name: 'IndexGetStarted',
    components: {
        InfoIcon,
        UploadCloudIcon,
        AuthButton,
        ShareIcon,
        ChevronRightIcon,
        FolderPlusIcon,
        HardDriveIcon,
        SettingsIcon,
        Trash2Icon,
        SearchIcon,
        CloudIcon,
        PageTitle,
        ImageIcon,
        GridIcon,
        LinkIcon,
        StarIcon,
        EyeIcon,
    },
    computed: {
        ...mapGetters(['index', 'config']),
    },
    data() {
        return {
            userCount: 0,
            fileCount: 0,
            isLoading: true,
        }
    },
    methods: {
        async getCounters() {
            try {
                const response = await axios.get('/api/counters');
                this.userCount = response.data.total_users.toLocaleString();
                this.fileCount = response.data.total_files.toLocaleString();
            } catch (error) {
                //console.error(error);
                // Set default values or handle the error appropriately
                this.userCount = 0;
                this.fileCount = 0;
            }
        },
    },
    mounted() {
        this.getCounters();

        // Call updateCounter every 10 seconds
        setInterval(() => {
            this.getCounters();
        }, 10000);

        // Call updateCounter every 15 seconds
        setInterval(() => {
            this.getCounters();
        }, 15000);

        // Call updateCounter every 30 seconds
        setInterval(() => {
            this.getCounters();
        }, 30000);
    }
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/landing-page';
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';


/* Set background color and padding */
.container {
  padding: 50px 0;
}

/* Center content vertically and horizontally */
.row {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

/* Set max-width and margin */
.col-md-4 {
  max-width: 850px;
  margin: 0 auto;
}

/* Style uploaded files and registered users counters */
.counter-item {
  text-align: center;
}

.counter-item h2 {
  font-size: 24px;
  margin-top: 20px;
  margin-bottom: 10px;
}

.counter-number {
  font-size: 48px;
  font-weight: bold;
  margin-bottom: 20px;
  color: $theme;
}

/* Style the sign up button */
.btn {
  display: inline-block;
  padding: 10px 20px;
  font-size: 20px;
  font-weight: bold;
  text-transform: uppercase;
  border-radius: 25px;
  background-color: $theme;
  color: #fff;
  transition: background-color 0.2s ease;
}

.btn:hover {
  background-color: $theme;
}

/* Style headings */
h2 {
  font-size: 36px;
  font-weight: bold;
  margin-top: 0;
}

/* Style paragraph text */
p {
  font-size: 18px;
  line-height: 1.5;
  margin-bottom: 20px;
}

/* Media queries for phone screens */
@media (max-width: 767px) {
  /* Set padding for container */
  .container {
    padding: 30px 0;
  }

  .allTextNumbers {
    padding: 10px 50px 20px;
    z-index: 0;
  }

  .spaceFromTextNumbers {
    padding: 3em;
  }

  /* Set max-width and margin for column */
  .col-md-4 {
    max-width: 100%;
    margin: 0;
  }

  /* Set font sizes for headings and paragraph text */
  h2 {
    font-size: 24px;
  }

  p {
    font-size: 16px;
    margin-bottom: 10px;
  }

  /* Set font size for sign up button */
  .btn {
    font-size: 16px;
    padding: 8px 16px;
  }

  /* Set font size for counter items */
  .counter-item h2 {
    font-size: 20px;
    margin-top: 10px;
    margin-bottom: 5px;
  }

  .counter-number {
    font-size: 36px;
    margin-bottom: 10px;
  }
}

/* Media queries for tablet screens */
@media (min-width: 768px) and (max-width: 991px) {

  .allTextNumbers {
    padding: 10px 50px 20px;
    z-index: 0;
  }

  .spaceFromTextNumbers {
    padding: 3em;
  }

  /* Set max-width and margin for column */
  .col-md-4 {
    max-width: 80%;
  }

  /* Set font sizes for headings and paragraph text */
  h2 {
    font-size: 30px;
  }

  p {
    font-size: 18px;
    margin-bottom: 15px;
  }

  /* Set font size for sign up button */
  .btn {
    font-size: 18px;
    padding: 10px 20px;
  }

  /* Set font size for counter items */
  .counter-item h2 {
    font-size: 24px;
    margin-top: 15px;
    margin-bottom: 8px;
  }

  .counter-number {
    font-size: 42px;
    margin-bottom: 15px;
  }
}

.icons {
    .icon {
        position: absolute;

        &:nth-child(20) {
            bottom: -37%;
            left: 37%;
            transform: rotate(0deg);

            circle,
            line {
                stroke: $yellow;
            }
        }

        &:nth-child(19) {
            bottom: -21%;
            left: 23.5%;
            transform: rotate(-20deg);

            path,
            line {
                stroke: $purple;
            }
        }

        &:nth-child(18) {
            bottom: -4%;
            left: 26.5%;
            transform: rotate(0deg);

            path {
                stroke: $theme;
            }
        }

        &:nth-child(17) {
            bottom: -5%;
            left: 8.5%;
            transform: rotate(0deg);
        }

        &:nth-child(16) {
            top: 86%;
            left: 17%;
            transform: rotate(18deg);
        }

        &:nth-child(15) {
            top: 64%;
            left: 17%;
            transform: rotate(0deg);

            polyline,
            line,
            path {
                stroke: $red;
            }
        }

        &:nth-child(14) {
            top: 44%;
            left: 28%;
            transform: rotate(0deg);

            polygon {
                stroke: $purple;
            }
        }

        &:nth-child(13) {
            top: 33%;
            left: 16%;
            transform: rotate(0deg);
        }

        &:nth-child(12) {
            top: 23%;
            left: 32%;
            transform: rotate(13deg);

            line,
            path {
                stroke: $yellow;
            }
        }

        &:nth-child(1) {
            top: 35%;
            right: 49%;
            transform: rotate(-11deg);

            line,
            path {
                stroke: $theme;
            }
        }

        &:nth-child(2) {
            top: 12%;
            right: 45%;
            transform: rotate(0);

            circle,
            path {
                stroke: $red;
            }
        }

        &:nth-child(3) {
            top: 30%;
            right: 30%;
            transform: rotate(20deg);
        }

        &:nth-child(4) {
            top: 14%;
            right: 14.5%;
            transform: rotate(-1deg);
        }

        &:nth-child(5) {
            top: 62%;
            right: 15.5%;
            transform: rotate(21deg);

            polyline,
            path,
            line {
                stroke: $red;
            }
        }

        &:nth-child(6) {
            top: 66%;
            right: 26.5%;
            transform: rotate(0deg);
        }

        &:nth-child(7) {
            bottom: 3%;
            right: 21.5%;
            transform: rotate(16deg);
        }

        &:nth-child(8) {
            bottom: -13%;
            right: 16.5%;
            transform: rotate(0deg);

            polygon {
                stroke: $yellow;
            }
        }

        &:nth-child(9) {
            bottom: -32%;
            right: 27%;
            transform: rotate(-20deg);
        }

        &:nth-child(10) {
            bottom: -5%;
            right: 34%;
            transform: rotate(16deg);

            rect {
                stroke: $purple;
            }
        }

        &:nth-child(11) {
            bottom: -28%;
            right: 44%;
            transform: rotate(-12deg);

            polyline,
            line,
            path {
                stroke: $red;
            }
        }
    }
}

.cloud-bg {
    z-index: 0;
    position: absolute;
    top: 70px;
    right: 60px;
    transform: scale(-1, 1) rotate(13deg);
    opacity: 0.1;

    path {
        stroke: none;
    }
}

.page-title {
    padding-top: 340px;
}

.get-started-button {
    display: flex;
    align-items: center;
    outline: none;
    border: none;
    margin-left: auto;
    margin-right: auto;
    cursor: pointer;
    padding: 20px 36px;
    border-radius: 6px;
    //box-shadow: 0 5px 10px 2px rgba($theme, 0.34);
    margin-bottom: 395px;
    @include transition(150ms);
    position: relative;
    z-index: 1;

    .content {
        @include font-size(19);
        font-weight: 700;
        margin-right: 8px;
        color: white;
    }

    polyline {
        stroke: white;
    }
}

@media only screen and (max-width: 1190px) {
    .get-started-button {
        margin-bottom: 280px;
    }
}

@media only screen and (max-width: 960px) {
    .page-title {
        padding-top: 20px;
    }

    .get-started-button {
        margin-bottom: 30px;
    }

    .cloud-bg,
    .icons {
        display: none;
    }
}
</style>
