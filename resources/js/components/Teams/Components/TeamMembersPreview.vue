<template>
    <div class="team-folder">
        <span v-if="limit && membersCount > 3" class="member-count"> +{{ membersCount - 3 }} </span>
        <div class="members">
            <div
                v-for="(member, index) in members"
                :key="`${member.type}-${member.id}-${index}`"
                :title="getMemberEmail(member)"
                class="member-preview z-10"
            >
                <MemberAvatar :is-border="true" :size="34" :member="member" />
            </div>
        </div>
    </div>
</template>

<script>
import MemberAvatar from '../../UI/Others/MemberAvatar'

export default {
    name: 'TeamMembersPreview',
    props: ['folder', 'limit', 'avatarSize'],
    components: {
        MemberAvatar,
    },
    computed: {
        membersCount() {
            // Remove duplicatas antes de contar
            const uniqueMembers = this.getUniqueMembers()
            return uniqueMembers.length
        },
        members() {
            const uniqueMembers = this.getUniqueMembers()

            if (this.limit) {
                return uniqueMembers.slice(0, 3)
            }

            return uniqueMembers
        },
    },
    methods: {
        getUniqueMembers() {
            // Check if folder and its nested properties exist
            if (!this.folder || !this.folder.data || !this.folder.data.relationships) {
                return []
            }

            const relationships = this.folder.data.relationships
            const members = (relationships.members && relationships.members.data) || []
            const invitations = (relationships.invitations && relationships.invitations.data) || []
            
            // Combina arrays e remove duplicatas baseando-se no ID
            const allMembers = [...members, ...invitations]
            const uniqueMap = new Map()
            
            allMembers.forEach(member => {
                // Usa uma chave única combinando tipo e ID
                const key = `${member.type}-${member.id}`
                if (!uniqueMap.has(key)) {
                    uniqueMap.set(key, member)
                }
            })
            
            return Array.from(uniqueMap.values())
        },
        getMemberEmail(member) {
            // Handle different possible data structures
            if (member.attributes && member.attributes.email) {
                return member.attributes.email
            }
            if (member.data && member.data.attributes && member.data.attributes.email) {
                return member.data.attributes.email
            }
            return ''
        }
    }
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/vuefilemanager/_variables';
@import 'resources/sass/vuefilemanager/_mixins';

.team-folder {
    display: flex;
    align-items: center;

    .member-count {
        @include font-size(12);
        color: $text-muted;
        margin-right: 3px;
        opacity: 0.7;
        min-width: 14px;
        text-align: left;
    }

    .members {
        display: flex;

        .member-preview {
            margin-left: -10px;

            &:first-child {
                margin-left: 0;
            }
        }

        .member {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid white;
            vertical-align: middle;
        }
    }
}

.dark {
    .team-folder {
        .member-count {
            color: $dark_mode_text_secondary;
        }

        .members .member {
            border-color: $dark_mode_foreground;
        }
    }
}
</style>