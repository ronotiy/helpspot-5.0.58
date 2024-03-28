<?php

namespace HS\Notifications;

use HS\Domain\Workspace\Request;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailSendError extends Notification
{
    use Queueable;
    /**
     * @var Request
     */
    private $request;

    /**
     * Create a new notification instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'style' => 'error',
            'route' => route('admin', ['pg' => 'request', 'reqid' => $this->request->xRequest]),
            'request' => $this->request->xRequest,
            'customer' => $this->request->customerFullName(),
            'email' => $this->request->sEmail,
        ];
    }
}
