<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Trade;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class TradeExecuted extends Notification
{
    use Queueable;

    protected $trade;

    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        
        // Always send email if user has an email
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        // Send telegram if they have a chat ID configured
        if ($notifiable->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $botName = $this->trade->botInstance->name ?? 'Unknown Bot';
        $side = $this->trade->side;
        $price = number_format($this->trade->price, 2);
        
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Trade Executed: {$side} {$this->trade->symbol}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your trading bot **{$botName}** has just executed a new trade on the market.")
            ->line("**Trading Pair:** {$this->trade->symbol}")
            ->line("**Action:** {$side}")
            ->line("**Execution Price:** $" . $price)
            ->action('View Live Dashboard', config('app.url') . '/dashboard')
            ->line('Thank you for using Capital First!');
    }

    public function toTelegram($notifiable)
    {
        $botName = $this->trade->botInstance->name ?? 'Unknown Bot';
        $sideIcon = $this->trade->side === 'BUY' ? '🟢' : '🔴';
        
        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content("{$sideIcon} *Trade Executed!*\n\n")
            ->line("*Bot:* {$botName}")
            ->line("*Pair:* {$this->trade->symbol}")
            ->line("*Side:* {$this->trade->side}")
            ->line("*Price:* $" . number_format($this->trade->price, 2))
            ->line("*Quantity:* " . rtrim(rtrim(number_format($this->trade->quantity, 8), '0'), '.'))
            ->button('View Dashboard', config('app.url') . '/dashboard');
    }

}
