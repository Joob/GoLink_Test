@extends('mails.layout.mail')


@section('content')
    @include('mails.partials.image-hero', [
        'image' => ('https://images.golink.co/mail/user-shared.png'),
        'style' => 'padding: 5px 0;',
    ])
    @include('mails.partials.title', [
        'title' => __t('shared_link_email_greeting'),
    ])
    @include('mails.partials.subtitle', [
         'subtitle' => __t('shared_link_email_user', compact('user', 'email')),
     ])

     <br>

    <tr>
        <td align="center" valign="top" style="padding-left:20px;padding-right:20px;" class="containtTable">
            @include('mails.partials.button-table', ['title' => __t('shared_link_email_link'), 'link' => $url])
        </td>
    </tr>

@endsection
