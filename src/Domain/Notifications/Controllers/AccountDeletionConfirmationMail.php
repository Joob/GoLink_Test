<?php
namespace Domain\Notifications\Controllers;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeletionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @param  string  $title
     * @param  string  $description
     * @return void
     */
    public function __construct($title, $description)
    {
        $this->data = [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $title = $data['title'];

        return $this->subject($title)
            ->view('mails.notifications.account-deletion-confirmation', $data);
    }
}