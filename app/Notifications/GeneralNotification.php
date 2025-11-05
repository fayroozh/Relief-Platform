<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;

    public function __construct($title, $body, $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('View', $this->url ?? url('/'))
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
