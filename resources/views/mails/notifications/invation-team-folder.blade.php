@extends('mails.layout.mail')


@section('content')
    @include('mails.partials.image-hero', [
        'image' => ('https://images.golink.co/mail/notification-reminder.png'),
        'style' => 'padding: 5px 0;',
    ])

    <tr>
        <td align="center" valign="top" style="padding-left:20px;padding-right:20px;" class="containtTable">

            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableDescription">

                <tr>
                    <td align="center" valign="top" style="padding-bottom:5px;padding-left:20px;padding-right:20px;" class="mainTitle">
                        <!-- Info Text // -->
                        <h2 class="text" style="color:#000000; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:28px; font-weight:600; font-style:normal; letter-spacing:normal; line-height:36px; text-transform:none; text-align:center; padding:0; margin:0">
                        {{ __t('team_invitation_notify_greeting') }}
                        </h2>
                    </td>
                </tr>
                
                <tr>
                    <td align="center" valign="top" style="padding-bottom: 10px;" class="description">
                        <!-- Description Text// -->
                        <p class="text" style="color:#666666; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:15px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
                        {{ __t('team_invitation_notify_desc') }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td align="center" valign="top" style="padding-bottom:10px;">
                        @include('mails.partials.button-table', ['title' => __t('join_into_team_folder'), 'link' => $url])
                    </td>
                </tr>
            </table>

        </td>
    </tr>

@endsection
